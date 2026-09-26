from __future__ import annotations

import logging
import threading
from collections.abc import Callable, Iterator

import numpy as np


LOGGER = logging.getLogger("hotel-face-pi.camera")


class PiCamera:
    """Picamera2 frame source for a CSI Raspberry Pi camera."""

    def __init__(
        self,
        camera_index: int,
        width: int,
        height: int,
        framerate: float,
        warmup_seconds: float,
        reconnect_initial: float,
        reconnect_max: float,
        camera_factory: Callable[[int], object] | None = None,
    ) -> None:
        self.camera_index = camera_index
        self.width = width
        self.height = height
        self.framerate = framerate
        self.warmup_seconds = warmup_seconds
        self.reconnect_initial = reconnect_initial
        self.reconnect_max = reconnect_max
        self.camera_factory = camera_factory
        self.frames_received = 0
        self.starts = 0
        self.failures = 0

    def frames(self, stop: threading.Event) -> Iterator[np.ndarray]:
        delay = self.reconnect_initial
        while not stop.is_set():
            camera = None
            try:
                camera = self._create_camera()
                configuration = camera.create_video_configuration(
                    main={"size": (self.width, self.height), "format": "RGB888"},
                    controls={"FrameRate": self.framerate},
                    buffer_count=4,
                )
                camera.configure(configuration)
                camera.start()
                self.starts += 1
                LOGGER.info(
                    "Raspberry Pi CSI camera started index=%d size=%dx%d fps=%.1f",
                    self.camera_index,
                    self.width,
                    self.height,
                    self.framerate,
                )
                if stop.wait(self.warmup_seconds):
                    return
                while not stop.is_set():
                    frame = camera.capture_array("main")
                    if not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
                        raise RuntimeError("Pi camera returned an invalid frame")
                    self.frames_received += 1
                    delay = self.reconnect_initial
                    yield frame
            except Exception as error:
                self.failures += 1
                LOGGER.warning(
                    "Pi camera unavailable; retrying in %.1fs (%s)",
                    delay,
                    type(error).__name__,
                )
            finally:
                self._release(camera)
            if stop.wait(delay):
                return
            delay = min(self.reconnect_max, delay * 2)

    def _create_camera(self):
        if self.camera_factory is not None:
            return self.camera_factory(self.camera_index)
        try:
            from picamera2 import Picamera2
        except ImportError as error:
            raise RuntimeError(
                "Picamera2 is not installed; run: sudo apt install python3-picamera2"
            ) from error
        return Picamera2(self.camera_index)

    @staticmethod
    def _release(camera) -> None:
        if camera is None:
            return
        try:
            camera.stop()
        except Exception:
            pass
        try:
            camera.close()
        except Exception:
            pass

    def close(self) -> None:
        # The active camera is released by frames() in its finally block.
        pass
