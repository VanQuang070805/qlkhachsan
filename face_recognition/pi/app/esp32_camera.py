from __future__ import annotations

import logging
import threading
import time
from collections.abc import Iterator

import cv2
import numpy as np
import requests


LOGGER = logging.getLogger("hotel-face-pi.camera")


class ESP32Camera:
    """Reconnectable MJPEG reader that never writes frames to disk."""

    def __init__(self, url: str, timeout: float, reconnect_initial: float, reconnect_max: float) -> None:
        self.url = url
        self.timeout = timeout
        self.reconnect_initial = reconnect_initial
        self.reconnect_max = reconnect_max
        self._session = requests.Session()
        self._response: requests.Response | None = None

    def frames(self, stop: threading.Event) -> Iterator[np.ndarray]:
        delay = self.reconnect_initial
        while not stop.is_set():
            try:
                LOGGER.info("Connecting to ESP32-CAM")
                self._response = self._session.get(
                    self.url,
                    stream=True,
                    timeout=(self.timeout, self.timeout),
                )
                self._response.raise_for_status()
                delay = self.reconnect_initial
                buffer = bytearray()
                for chunk in self._response.iter_content(chunk_size=4096):
                    if stop.is_set():
                        return
                    if not chunk:
                        continue
                    buffer.extend(chunk)
                    while True:
                        start = buffer.find(b"\xff\xd8")
                        end = buffer.find(b"\xff\xd9", start + 2) if start >= 0 else -1
                        if start < 0 or end < 0:
                            if len(buffer) > 4_000_000:
                                del buffer[:-2]
                            break
                        jpeg = bytes(buffer[start:end + 2])
                        del buffer[:end + 2]
                        frame = cv2.imdecode(np.frombuffer(jpeg, dtype=np.uint8), cv2.IMREAD_COLOR)
                        if frame is not None:
                            yield frame
                raise ConnectionError("ESP32-CAM stream ended")
            except (requests.RequestException, ConnectionError) as error:
                LOGGER.warning("Camera unavailable; retrying in %.1fs (%s)", delay, type(error).__name__)
            finally:
                if self._response is not None:
                    self._response.close()
                    self._response = None
            stop.wait(delay)
            delay = min(self.reconnect_max, delay * 2)

    def close(self) -> None:
        if self._response is not None:
            self._response.close()
        self._session.close()

