"""Picamera2 input for Raspberry Pi Camera Module Rev 1.3."""
import logging
import threading
import time

import numpy as np

from config import CameraConfig


LOG = logging.getLogger(__name__)


class PiCamera:
    def __init__(self, config: CameraConfig, camera_factory=None):
        self.config = config
        self.camera_factory = camera_factory
        self.frames_received = 0
        self.starts = 0
        self.failures = 0

    def frames(self, stop=None, deadline=None):
        stop = stop or threading.Event()
        delay = self.config.reconnect_initial
        while not stop.is_set() and (deadline is None or time.monotonic() < deadline):
            camera = None
            try:
                camera = self._create_camera()
                configuration = camera.create_video_configuration(
                    main={
                        "size": (self.config.width, self.config.height),
                        "format": "RGB888",
                    },
                    controls={"FrameRate": self.config.framerate},
                    buffer_count=4,
                )
                camera.configure(configuration)
                camera.start()
                self.starts += 1
                LOG.info(
                    "CSI camera started (start %d, index=%d, %dx%d at %.1f FPS)",
                    self.starts,
                    self.config.camera_index,
                    self.config.width,
                    self.config.height,
                    self.config.framerate,
                )
                if self._wait(stop, self.config.warmup_seconds, deadline):
                    return
                while not stop.is_set() and (deadline is None or time.monotonic() < deadline):
                    frame = camera.capture_array("main")
                    if not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
                        raise RuntimeError("Pi camera returned an invalid frame")
                    delay = self.config.reconnect_initial
                    self.frames_received += 1
                    yield frame
            except Exception as exc:
                self.failures += 1
                LOG.warning(
                    "Pi camera unavailable (%s); retry in %.1fs",
                    type(exc).__name__,
                    delay,
                )
            finally:
                self._release(camera)
            if self._wait(stop, delay, deadline):
                break
            delay = min(delay * 2, self.config.reconnect_max)

    def _create_camera(self):
        if self.camera_factory is not None:
            return self.camera_factory(self.config.camera_index)
        try:
            from picamera2 import Picamera2
        except ImportError as exc:
            raise RuntimeError(
                "Picamera2 is not installed; run: sudo apt install python3-picamera2"
            ) from exc
        return Picamera2(self.config.camera_index)

    @staticmethod
    def _wait(stop, seconds, deadline):
        if deadline is not None:
            seconds = min(seconds, max(0, deadline - time.monotonic()))
        return stop.wait(seconds) or (deadline is not None and time.monotonic() >= deadline)

    @staticmethod
    def _release(camera):
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

    def close(self):
        # The active camera is released by frames() in its finally block.
        pass
