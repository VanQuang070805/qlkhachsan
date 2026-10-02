from __future__ import annotations

import hmac
import logging
from contextlib import asynccontextmanager

from fastapi import Depends, FastAPI, Header, HTTPException, Request, status
from pydantic import BaseModel, Field

from .cache import FaceCache
from .cleaning_switch import CleaningApiClient, CleaningSwitchService
from .config import PiConfig
from .database import FaceDatabase, FaceRecord
from .recognition_worker import RecognitionWorker


LOGGER = logging.getLogger("hotel-face-pi")


class FacePayload(BaseModel):
    customer_id: str = Field(min_length=1, max_length=64)
    booking_id: int = Field(gt=0)
    user_id: int | None = None
    name: str = Field(min_length=1, max_length=255)
    room: str = Field(default="", max_length=255)
    embedding: list[float] = Field(min_length=16, max_length=4096)
    embedding_model: str = Field(default="sface_2021dec", max_length=80)
    version: int = Field(gt=0)
    valid_until: str | None = None

    def to_record(self) -> FaceRecord:
        return FaceRecord(**self.model_dump())


class FullSyncPayload(BaseModel):
    complete: bool
    faces: list[FacePayload] = Field(max_length=10000)


def create_app(config: PiConfig | None = None) -> FastAPI:
    settings = config or PiConfig.from_env()
    database = FaceDatabase(settings.database_path)
    cache = FaceCache(database)
    worker = RecognitionWorker(settings, cache)
    cleaning_switch: CleaningSwitchService | None = None
    if settings.cleaning_switch_enabled:
        cleaning_switch = CleaningSwitchService(
            client=CleaningApiClient(
                base_url=settings.hotel_api_base_url,
                api_key=settings.iot_device_api_key,
                room_number=settings.iot_cleaning_room_number,
                timeout=settings.iot_request_timeout,
            ),
            gpio=settings.iot_cleaning_switch_gpio,
            bounce_time=settings.iot_switch_bounce_time,
            retry_seconds=settings.iot_retry_seconds,
        )

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        database.initialize()
        count = cache.reload()
        LOGGER.info("Face database initialized; cache_count=%d", count)
        if settings.start_recognition:
            worker.start()
        if cleaning_switch:
            try:
                cleaning_switch.start()
            except Exception as exc:
                cleaning_switch.last_error = str(exc)
                LOGGER.exception("Cleaning switch failed to start; Face ID will continue")
        yield
        if cleaning_switch:
            cleaning_switch.stop()
        worker.stop()

    app = FastAPI(title="Hotel Face ID Pi API", version="1.0", lifespan=lifespan)
    app.state.config = settings
    app.state.database = database
    app.state.cache = cache
    app.state.worker = worker
    app.state.cleaning_switch = cleaning_switch

    def require_api_key(x_api_key: str | None = Header(default=None)) -> None:
        if not x_api_key or not hmac.compare_digest(x_api_key, settings.api_key):
            raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid API key")

    @app.get("/api/health")
    def health(request: Request):
        switch = request.app.state.cleaning_switch
        return {
            "status": "ok",
            "faces": request.app.state.cache.size,
            "recognition_running": bool(
                request.app.state.worker.thread and request.app.state.worker.thread.is_alive()
            ),
            "cleaning_switch": switch.health() if switch else {"enabled": False},
        }

    @app.get("/api/faces", dependencies=[Depends(require_api_key)])
    def list_faces(request: Request):
        return {"faces": request.app.state.database.list_metadata()}

    @app.post("/api/faces", dependencies=[Depends(require_api_key)])
    def add_face(payload: FacePayload, request: Request):
        action = request.app.state.database.upsert(payload.to_record())
        count = request.app.state.cache.reload()
        return {"status": action, "customer_id": payload.customer_id, "cache_count": count}

    @app.put("/api/faces/{customer_id}", dependencies=[Depends(require_api_key)])
    def update_face(customer_id: str, payload: FacePayload, request: Request):
        if customer_id != payload.customer_id:
            raise HTTPException(status_code=422, detail="customer_id does not match path")
        action = request.app.state.database.upsert(payload.to_record())
        count = request.app.state.cache.reload()
        return {"status": action, "customer_id": customer_id, "cache_count": count}

    @app.delete("/api/faces/{customer_id}", dependencies=[Depends(require_api_key)])
    def delete_face(customer_id: str, request: Request):
        deleted = request.app.state.database.delete(customer_id)
        count = request.app.state.cache.reload()
        return {"status": "deleted" if deleted else "already_absent", "customer_id": customer_id, "cache_count": count}

    @app.post("/api/faces/full-sync", dependencies=[Depends(require_api_key)])
    def full_sync(payload: FullSyncPayload, request: Request):
        if payload.complete is not True:
            raise HTTPException(status_code=422, detail="A complete snapshot is required")
        try:
            result = request.app.state.database.full_sync([face.to_record() for face in payload.faces])
        except ValueError as error:
            raise HTTPException(status_code=422, detail=str(error)) from error
        request.app.state.cache.reload()
        return result

    return app


try:
    app = create_app()
except ValueError:
    # Keep imports and tests usable; uvicorn startup still requires valid configuration.
    app = None
