"""No secrets or laptop-specific paths in the Pi configuration."""
import os
from dataclasses import dataclass
from urllib.parse import urlsplit


@dataclass(frozen=True)
class CameraConfig:
    url: str = "http://172.20.10.3/stream"
    connect_timeout: float = 3.0
    read_timeout: float = 3.0
    frame_timeout: float = 8.0
    reconnect_initial: float = 1.0
    reconnect_max: float = 15.0
    max_frame_bytes: int = 2_000_000

    def __post_init__(self):
        url = urlsplit(self.url)
        if url.scheme not in ("http", "https") or not url.hostname or url.username or url.password:
            raise ValueError("CAMERA_URL must be an HTTP(S) URL without credentials")
        for value in (self.connect_timeout, self.read_timeout, self.frame_timeout,
                      self.reconnect_initial, self.reconnect_max):
            if not 0 < value <= 300:
                raise ValueError("Timeout/reconnect values must be between 0 and 300 seconds")
        if self.reconnect_initial > self.reconnect_max:
            raise ValueError("Initial reconnect delay must not exceed maximum")
        if not 1024 <= self.max_frame_bytes <= 10_000_000:
            raise ValueError("Invalid maximum frame size")

    @classmethod
    def from_env(cls, url=None):
        return cls(
            url=url or os.getenv("CAMERA_URL", "http://172.20.10.3/stream"),
            connect_timeout=float(os.getenv("CAMERA_CONNECT_TIMEOUT", "3")),
            read_timeout=float(os.getenv("CAMERA_READ_TIMEOUT", "3")),
            frame_timeout=float(os.getenv("CAMERA_FRAME_TIMEOUT", "8")),
            reconnect_initial=float(os.getenv("CAMERA_RECONNECT_INITIAL", "1")),
            reconnect_max=float(os.getenv("CAMERA_RECONNECT_MAX", "15")),
        )
