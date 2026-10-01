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
    detection_threshold: float
    recognition_threshold: float
    process_every_n_frames: int
    request_timeout: float
    camera_index: int = 0
    camera_width: int = 640
    camera_height: int = 480
    camera_framerate: float = 15.0
    camera_warmup_seconds: float = 1.0
    start_recognition: bool = False
    reconnect_initial: float = 1.0
    reconnect_max: float = 15.0
    show_preview: bool = True
    servo_enabled: bool = False
    servo_gpio_pin: int = 18
    servo_closed_angle: float = 0.0
    servo_open_angle: float = 180.0
    servo_move_seconds: float = 1.2
    servo_hold_seconds: float = 3.0
    servo_cooldown_seconds: float = 8.0
    servo_min_pulse_width: float = 0.0005
    servo_max_pulse_width: float = 0.0025
    servo_detach_after_move: bool = True

    @classmethod
    def from_env(cls) -> "PiConfig":
        config = cls(
            database_path=_env_path("FACE_DB_PATH", MODULE_DIR / "data" / "faces.db"),
            api_key=os.getenv("FACE_API_KEY", ""),
            host=os.getenv("FACE_API_HOST", "0.0.0.0"),
            port=int(os.getenv("FACE_API_PORT", "8002")),
            yunet_model_path=_env_path("YUNET_MODEL_PATH", MODULE_DIR / "models" / "face_detection_yunet_2023mar.onnx"),
            sface_model_path=_env_path("SFACE_MODEL_PATH", MODULE_DIR / "models" / "face_recognition_sface_2021dec.onnx"),
            detection_threshold=float(os.getenv("DETECTION_THRESHOLD", "0.9")),
            recognition_threshold=float(os.getenv("RECOGNITION_THRESHOLD", "0.363")),
            process_every_n_frames=int(os.getenv("PROCESS_EVERY_N_FRAMES", "3")),
            request_timeout=float(os.getenv("REQUEST_TIMEOUT", "5")),
            camera_index=int(os.getenv("PI_CAMERA_INDEX", "0")),
            camera_width=int(os.getenv("PI_CAMERA_WIDTH", "640")),
            camera_height=int(os.getenv("PI_CAMERA_HEIGHT", "480")),
            camera_framerate=float(os.getenv("PI_CAMERA_FRAMERATE", "15")),
            camera_warmup_seconds=float(os.getenv("PI_CAMERA_WARMUP_SECONDS", "1")),
            start_recognition=os.getenv("START_RECOGNITION", "false").lower() in {"1", "true", "yes"},
            reconnect_initial=float(os.getenv("CAMERA_RECONNECT_INITIAL", "1")),
            reconnect_max=float(os.getenv("CAMERA_RECONNECT_MAX", "15")),
            show_preview=os.getenv("SHOW_PREVIEW", "true").lower() in {"1", "true", "yes"},
            servo_enabled=os.getenv("SERVO_ENABLED", "false").lower() in {"1", "true", "yes"},
            servo_gpio_pin=int(os.getenv("SERVO_GPIO_PIN", "18")),
            servo_closed_angle=float(os.getenv("SERVO_CLOSED_ANGLE", "0")),
            servo_open_angle=float(os.getenv("SERVO_OPEN_ANGLE", "180")),
            servo_move_seconds=float(os.getenv("SERVO_MOVE_SECONDS", "1.2")),
            servo_hold_seconds=float(os.getenv("SERVO_HOLD_SECONDS", "3")),
            servo_cooldown_seconds=float(os.getenv("SERVO_COOLDOWN_SECONDS", "8")),
            servo_min_pulse_width=float(os.getenv("SERVO_MIN_PULSE_WIDTH", "0.0005")),
            servo_max_pulse_width=float(os.getenv("SERVO_MAX_PULSE_WIDTH", "0.0025")),
            servo_detach_after_move=os.getenv("SERVO_DETACH_AFTER_MOVE", "true").lower() in {"1", "true", "yes"},
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
        if self.camera_index < 0:
            raise ValueError("PI_CAMERA_INDEX must be at least 0")
        if not 160 <= self.camera_width <= 1920 or not 120 <= self.camera_height <= 1080:
            raise ValueError("Pi camera resolution is invalid")
        if not 0 < self.camera_framerate <= 60:
            raise ValueError("PI_CAMERA_FRAMERATE must be between 0 and 60")
        if not 0 <= self.camera_warmup_seconds <= 30:
            raise ValueError("PI_CAMERA_WARMUP_SECONDS must be between 0 and 30")
        if not 0 < self.reconnect_initial <= self.reconnect_max <= 300:
            raise ValueError("Invalid camera reconnect interval")
        if not 2 <= self.servo_gpio_pin <= 27:
            raise ValueError("SERVO_GPIO_PIN must be a valid BCM GPIO number")
        if not 0 <= self.servo_closed_angle <= 180 or not 0 <= self.servo_open_angle <= 180:
            raise ValueError("Servo angles must be between 0 and 180 degrees")
        if self.servo_closed_angle == self.servo_open_angle:
            raise ValueError("Servo open and closed angles must be different")
        if not 0.1 <= self.servo_move_seconds <= 10:
            raise ValueError("SERVO_MOVE_SECONDS must be between 0.1 and 10 seconds")
        if not 0.1 <= self.servo_hold_seconds <= 60:
            raise ValueError("SERVO_HOLD_SECONDS must be between 0.1 and 60")
        if not 0 <= self.servo_cooldown_seconds <= 300:
            raise ValueError("SERVO_COOLDOWN_SECONDS must be between 0 and 300")
        if not 0.0001 <= self.servo_min_pulse_width < self.servo_max_pulse_width <= 0.003:
            raise ValueError("Servo pulse widths are invalid")
