from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv


MODULE_DIR = Path(__file__).resolve().parents[1]
load_dotenv(MODULE_DIR / ".env")


def _env_path(name: str, default: Path) -> Path:
    value = Path(os.getenv(name, str(default)))
    return value if value.is_absolute() else MODULE_DIR / value


@dataclass(frozen=True)
class PiConfig:
    database_path: Path
    api_key: str
    host: str
    port: int
    yunet_model_path: Path
    sface_model_path: Path
    esp32_cam_url: str
    detection_threshold: float
    recognition_threshold: float
    process_every_n_frames: int
    request_timeout: float
    start_recognition: bool = False
    reconnect_initial: float = 1.0
    reconnect_max: float = 15.0
    show_preview: bool = True

    @classmethod
    def from_env(cls) -> "PiConfig":
        config = cls(
            database_path=_env_path("FACE_DB_PATH", MODULE_DIR / "data" / "faces.db"),
            api_key=os.getenv("FACE_API_KEY", ""),
            host=os.getenv("FACE_API_HOST", "0.0.0.0"),
            port=int(os.getenv("FACE_API_PORT", "8002")),
            yunet_model_path=_env_path("YUNET_MODEL_PATH", MODULE_DIR / "models" / "face_detection_yunet_2023mar.onnx"),
            sface_model_path=_env_path("SFACE_MODEL_PATH", MODULE_DIR / "models" / "face_recognition_sface_2021dec.onnx"),
            esp32_cam_url=os.getenv("ESP32_CAM_URL", "http://172.20.10.3/stream"),
            detection_threshold=float(os.getenv("DETECTION_THRESHOLD", "0.9")),
            recognition_threshold=float(os.getenv("RECOGNITION_THRESHOLD", "0.363")),
            process_every_n_frames=int(os.getenv("PROCESS_EVERY_N_FRAMES", "3")),
            request_timeout=float(os.getenv("REQUEST_TIMEOUT", "5")),
            start_recognition=os.getenv("START_RECOGNITION", "false").lower() in {"1", "true", "yes"},
            reconnect_initial=float(os.getenv("CAMERA_RECONNECT_INITIAL", "1")),
            reconnect_max=float(os.getenv("CAMERA_RECONNECT_MAX", "15")),
            show_preview=os.getenv("SHOW_PREVIEW", "true").lower() in {"1", "true", "yes"},
        )
        config.validate()
        return config

    def validate(self) -> None:
        if not self.api_key:
            raise ValueError("FACE_API_KEY is required")
        if not 1 <= self.port <= 65535:
            raise ValueError("FACE_API_PORT is invalid")
        if not 0 < self.detection_threshold <= 1 or not 0 < self.recognition_threshold <= 1:
            raise ValueError("Face thresholds must be between 0 and 1")
        if self.process_every_n_frames < 1:
            raise ValueError("PROCESS_EVERY_N_FRAMES must be at least 1")
        if not 0 < self.reconnect_initial <= self.reconnect_max <= 300:
            raise ValueError("Invalid camera reconnect interval")
