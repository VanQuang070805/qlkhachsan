from __future__ import annotations

import json
import logging

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from pydantic import BaseModel, Field, TypeAdapter, ValidationError

from .config import FaceConfig
from .enrollment import EnrollmentManager
from .face_detector import FaceDetector
from .face_recognizer import FaceRecognizer


LOGGER = logging.getLogger("hotel-face-pc")


class RecognitionCandidate(BaseModel):
    customer_id: str = Field(min_length=1, max_length=64)
    embedding: list[float] = Field(min_length=16, max_length=4096)


def create_app(config: FaceConfig | None = None, manager: EnrollmentManager | None = None) -> FastAPI:
    settings = config or FaceConfig.from_env()
    settings.validate()
    enrollment = manager or EnrollmentManager(
        settings,
        FaceDetector(settings.yunet_model_path, settings.detection_threshold),
        FaceRecognizer(settings.sface_model_path),
    )
    app = FastAPI(title="Hotel Face ID PC Service", version="1.0")
    app.state.config = settings
    app.state.enrollment = enrollment

    @app.get("/api/health")
    def health():
        return {"status": "ok", "models": "loaded"}

    @app.post("/api/enrollment-sessions")
    def create_session():
        session = enrollment.create()
        return {"session_id": session.session_id, "target": settings.registration_samples}

    @app.post("/api/enrollment-sessions/{session_id}/samples")
    async def add_sample(session_id: str, frame: UploadFile = File(...)):
        if frame.content_type not in {"image/jpeg", "image/png"}:
            raise HTTPException(status_code=415, detail="JPEG or PNG image required")
        try:
            return enrollment.add_sample(session_id, await frame.read())
        except KeyError as error:
            raise HTTPException(status_code=404, detail=str(error)) from error
        except ValueError as error:
            raise HTTPException(status_code=422, detail=str(error)) from error

    @app.delete("/api/enrollment-sessions/{session_id}")
    def cancel_session(session_id: str):
        enrollment.cancel(session_id)
        return {"status": "cancelled"}

    @app.post("/api/recognize")
    async def recognize(frame: UploadFile = File(...), candidates: str = Form(...)):
        if frame.content_type not in {"image/jpeg", "image/png"}:
            raise HTTPException(status_code=415, detail="JPEG or PNG image required")
        try:
            parsed = TypeAdapter(list[RecognitionCandidate]).validate_python(json.loads(candidates))
            if not parsed:
                raise ValueError("At least one candidate is required")
            payload = [candidate.model_dump() for candidate in parsed]
            return enrollment.recognize(await frame.read(), payload)
        except (json.JSONDecodeError, ValidationError, ValueError) as error:
            raise HTTPException(status_code=422, detail=str(error)) from error

    return app


app = create_app()
