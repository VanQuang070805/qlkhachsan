from __future__ import annotations

import uvicorn

from app.config import FaceConfig


if __name__ == "__main__":
    config = FaceConfig.from_env()
    uvicorn.run("app.main:app", host=config.api_host, port=config.api_port)

