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
    servo_open_angle: float = 90.0
    servo_move_seconds: float = 1.2
    servo_hold_seconds: float = 3.0
    servo_cooldown_seconds: float = 8.0
    servo_min_pulse_width: float = 0.0005
    servo_max_pulse_width: float = 0.0025
    servo_detach_after_move: bool = True
    servo_pwm_backend: str = "pigpio"
    cleaning_switch_enabled: bool = False
    hotel_api_base_url: str = ""
    iot_device_api_key: str = ""
    iot_cleaning_room_number: str = "501"
    iot_cleaning_switch_gpio: int = 17
    iot_switch_bounce_time: float = 0.15
    iot_request_timeout: float = 5.0
    iot_retry_seconds: float = 3.0

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
            servo_open_angle=float(os.getenv("SERVO_OPEN_ANGLE", "90")),
            servo_move_seconds=float(os.getenv("SERVO_MOVE_SECONDS", "1.2")),
            servo_hold_seconds=float(os.getenv("SERVO_HOLD_SECONDS", "3")),
            servo_cooldown_seconds=float(os.getenv("SERVO_COOLDOWN_SECONDS", "8")),
            servo_min_pulse_width=float(os.getenv("SERVO_MIN_PULSE_WIDTH", "0.0005")),
            servo_max_pulse_width=float(os.getenv("SERVO_MAX_PULSE_WIDTH", "0.0025")),
            servo_detach_after_move=os.getenv("SERVO_DETACH_AFTER_MOVE", "true").lower() in {"1", "true", "yes"},
            servo_pwm_backend=os.getenv("SERVO_PWM_BACKEND", "pigpio").strip().lower(),
            cleaning_switch_enabled=os.getenv("CLEANING_SWITCH_ENABLED", "false").lower() in {"1", "true", "yes"},
            hotel_api_base_url=os.getenv("HOTEL_API_BASE_URL", "").rstrip("/"),
            iot_device_api_key=os.getenv("IOT_DEVICE_API_KEY", ""),
            iot_cleaning_room_number=os.getenv("IOT_CLEANING_ROOM_NUMBER", "501"),
            iot_cleaning_switch_gpio=int(os.getenv("IOT_CLEANING_SWITCH_GPIO", "17")),
            iot_switch_bounce_time=float(os.getenv("IOT_SWITCH_BOUNCE_TIME", "0.15")),
            iot_request_timeout=float(os.getenv("IOT_REQUEST_TIMEOUT", "5")),
            iot_retry_seconds=float(os.getenv("IOT_RETRY_SECONDS", "3")),
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
        if self.servo_pwm_backend not in {"pigpio", "gpiozero"}:
            raise ValueError("SERVO_PWM_BACKEND must be pigpio or gpiozero")
        if self.cleaning_switch_enabled:
            if not self.hotel_api_base_url.startswith(("http://", "https://")):
                raise ValueError("HOTEL_API_BASE_URL must start with http:// or https://")
            if not self.iot_device_api_key:
                raise ValueError("IOT_DEVICE_API_KEY is required when cleaning switch is enabled")
            if not self.iot_cleaning_room_number:
                raise ValueError("IOT_CLEANING_ROOM_NUMBER is required")
            if not 0 <= self.iot_cleaning_switch_gpio <= 27:
                raise ValueError("IOT_CLEANING_SWITCH_GPIO must be a BCM GPIO number from 0 to 27")
            if self.servo_enabled and self.iot_cleaning_switch_gpio == self.servo_gpio_pin:
                raise ValueError("Cleaning switch and servo cannot use the same GPIO pin")
            if not 0 <= self.iot_switch_bounce_time <= 2:
                raise ValueError("IOT_SWITCH_BOUNCE_TIME must be between 0 and 2 seconds")
            if self.iot_request_timeout <= 0 or self.iot_retry_seconds <= 0:
                raise ValueError("IoT request timeout and retry interval must be positive")
