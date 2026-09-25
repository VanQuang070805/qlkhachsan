from __future__ import annotations

import uvicorn

from app.config import PiConfig


if __name__ == "__main__":
    config = PiConfig.from_env()
    uvicorn.run("app.main:create_app", factory=True, host=config.host, port=config.port)

