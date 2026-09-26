"""No secrets or laptop-specific paths in the Pi configuration."""
import os
from dataclasses import dataclass


@dataclass(frozen=True)
class CameraConfig:
    camera_index: int = 0
    width: int = 640
    height: int = 480
    framerate: float = 15.0
    warmup_seconds: float = 1.0
    reconnect_initial: float = 1.0
    reconnect_max: float = 15.0

    def __post_init__(self):
        if self.camera_index < 0:
            raise ValueError("PI_CAMERA_INDEX must be at least 0")
        if not 160 <= self.width <= 1920 or not 120 <= self.height <= 1080:
            raise ValueError("Pi camera resolution is invalid")
        if not 0 < self.framerate <= 60:
            raise ValueError("PI_CAMERA_FRAMERATE must be between 0 and 60")
        if not 0 <= self.warmup_seconds <= 30:
            raise ValueError("PI_CAMERA_WARMUP_SECONDS must be between 0 and 30")
        for value in (self.reconnect_initial, self.reconnect_max):
            if not 0 < value <= 300:
                raise ValueError("Reconnect values must be between 0 and 300 seconds")
        if self.reconnect_initial > self.reconnect_max:
            raise ValueError("Initial reconnect delay must not exceed maximum")

    @classmethod
    def from_env(cls, camera_index=None):
        return cls(
            camera_index=camera_index if camera_index is not None else int(os.getenv("PI_CAMERA_INDEX", "0")),
            width=int(os.getenv("PI_CAMERA_WIDTH", "640")),
            height=int(os.getenv("PI_CAMERA_HEIGHT", "480")),
            framerate=float(os.getenv("PI_CAMERA_FRAMERATE", "15")),
            warmup_seconds=float(os.getenv("PI_CAMERA_WARMUP_SECONDS", "1")),
            reconnect_initial=float(os.getenv("CAMERA_RECONNECT_INITIAL", "1")),
            reconnect_max=float(os.getenv("CAMERA_RECONNECT_MAX", "15")),
        )
