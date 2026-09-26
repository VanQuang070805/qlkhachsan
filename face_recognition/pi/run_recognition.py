"""Run the Pi API and CSI camera recognition worker in one process."""
from __future__ import annotations

from dataclasses import replace

import uvicorn

from app.config import PiConfig
from app.main import create_app


if __name__ == "__main__":
    config = replace(PiConfig.from_env(), start_recognition=True)
    uvicorn.run(create_app(config), host=config.host, port=config.port)
