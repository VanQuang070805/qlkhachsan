from __future__ import annotations

import os
import hashlib
import shutil
import tempfile
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv


MODULE_DIR = Path(__file__).resolve().parents[1]
load_dotenv(MODULE_DIR / ".env")


def _env_float(name: str, default: float) -> float:
    value = float(os.getenv(name, str(default)))
    if not 0 < value <= 1:
        raise ValueError(f"{name} must be between 0 and 1")
    return value


def _env_int(name: str, default: int, minimum: int = 0) -> int:
    value = int(os.getenv(name, str(default)))
    if value < minimum:
        raise ValueError(f"{name} must be at least {minimum}")
    return value


def _env_path(name: str, default: Path) -> Path:
    value = Path(os.getenv(name, str(default)))
    return value if value.is_absolute() else MODULE_DIR / value


@dataclass(frozen=True)
class FaceConfig:
    yunet_model_path: Path
    sface_model_path: Path
    camera_index: int
    detection_threshold: float
    recognition_threshold: float
    registration_samples: int
    sample_interval_seconds: float
    minimum_face_size: int
    process_every_n_frames: int
    api_host: str = "127.0.0.1"
    api_port: int = 8001
    session_ttl_seconds: int = 600

    @classmethod
    def from_env(cls) -> "FaceConfig":
        return cls(
            yunet_model_path=_env_path("YUNET_MODEL_PATH", MODULE_DIR / "models" / "face_detection_yunet_2023mar.onnx"),
            sface_model_path=_env_path("SFACE_MODEL_PATH", MODULE_DIR / "models" / "face_recognition_sface_2021dec.onnx"),
            camera_index=_env_int("CAMERA_INDEX", 0),
            detection_threshold=_env_float("DETECTION_THRESHOLD", 0.9),
            recognition_threshold=_env_float("RECOGNITION_THRESHOLD", 0.363),
            registration_samples=_env_int("REGISTRATION_SAMPLES", 15, 3),
            sample_interval_seconds=float(os.getenv("SAMPLE_INTERVAL_SECONDS", "0.35")),
            minimum_face_size=_env_int("MINIMUM_FACE_SIZE", 90, 20),
            process_every_n_frames=_env_int("PROCESS_EVERY_N_FRAMES", 2, 1),
            api_host=os.getenv("FACE_PC_HOST", "127.0.0.1"),
            api_port=_env_int("FACE_PC_PORT", 8001, 1),
            session_ttl_seconds=_env_int("FACE_SESSION_TTL", 600, 60),
        )

    def validate(self) -> None:
        if self.sample_interval_seconds <= 0:
            raise ValueError("SAMPLE_INTERVAL_SECONDS must be greater than 0")
        if self.api_port > 65535:
            raise ValueError("FACE_PC_PORT is invalid")


def ensure_model_exists(path: Path, label: str) -> None:
    if not path.is_file() or path.stat().st_size == 0:
        raise FileNotFoundError(f"{label} model not found: {path}")


def opencv_model_path(path: Path, label: str) -> str:
    """Return an ASCII path because OpenCV DNN on Windows rejects Unicode paths."""
    ensure_model_exists(path, label)
    try:
        str(path).encode("ascii")
        return str(path)
    except UnicodeEncodeError:
        digest = hashlib.sha256(path.read_bytes()).hexdigest()[:16]
        cache_dir = Path(tempfile.gettempdir()) / "hotel_face_models"
        cache_dir.mkdir(parents=True, exist_ok=True)
        cached = cache_dir / f"{label.lower()}-{digest}.onnx"
        if not cached.is_file() or cached.stat().st_size != path.stat().st_size:
            temporary = cached.with_suffix(".tmp")
            shutil.copyfile(path, temporary)
            temporary.replace(cached)
        return str(cached)
