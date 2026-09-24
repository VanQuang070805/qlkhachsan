"""Bounded MJPEG reader with reconnect; OpenCV decodes each JPEG locally.

HTTP is read explicitly because VideoCapture network timeouts depend on the
installed FFmpeg/GStreamer backend. No frames are saved or sent to the laptop.
"""
import logging
import threading
import time

import cv2
import numpy as np
import requests
from urllib3.exceptions import HTTPError

from config import CameraConfig

LOG = logging.getLogger(__name__)


class JpegParser:
    def __init__(self, max_bytes):
        self.max_bytes = max_bytes
        self.buffer = bytearray()

    def feed(self, chunk):
        self.buffer.extend(chunk)
        frames = []
        while True:
            start = self.buffer.find(b"\xff\xd8")
            if start < 0:
                self.buffer[:] = self.buffer[-1:]
                break
            if start:
                del self.buffer[:start]
            end = self.buffer.find(b"\xff\xd9", 2)
            if end < 0:
                if len(self.buffer) > self.max_bytes:
                    raise ValueError("JPEG exceeds configured size limit")
                break
            if end + 2 > self.max_bytes:
                raise ValueError("JPEG exceeds configured size limit")
            frames.append(bytes(self.buffer[:end + 2]))
            del self.buffer[:end + 2]
        return frames


class ESP32Camera:
    def __init__(self, config: CameraConfig, session=None):
        self.config = config
        self.session = session or requests.Session()
        # Local camera traffic should not accidentally be sent through a proxy.
        self.session.trust_env = False
        self.frames_received = 0
        self.connections = 0
        self.failures = 0

    def frames(self, stop=None, deadline=None):
        stop = stop or threading.Event()
        delay = self.config.reconnect_initial
        while not stop.is_set() and (deadline is None or time.monotonic() < deadline):
            try:
                with self.session.get(
                    self.config.url, stream=True,
                    timeout=(self.config.connect_timeout, self.config.read_timeout),
                    allow_redirects=False,
                ) as response:
                    response.raise_for_status()
                    if response.status_code != 200:
                        raise ValueError("Camera must return HTTP 200 (redirects are disabled)")
                    content_type = response.headers.get("Content-Type", "").lower()
                    if not content_type.startswith("multipart/x-mixed-replace"):
                        raise ValueError("Camera response is not an MJPEG stream")
                    self.connections += 1
                    LOG.info("MJPEG connection established (connection %d)", self.connections)
                    parser = JpegParser(self.config.max_frame_bytes)
                    last_frame = time.monotonic()
                    # read1 returns currently available bytes instead of waiting
                    # to fill 4096 bytes: slow trickles cannot hide frame timeout.
                    while True:
                        chunk = response.raw.read1(4096, decode_content=False)
                        if not chunk:
                            break
                        now = time.monotonic()
                        if stop.is_set() or (deadline is not None and now >= deadline):
                            return
                        if now - last_frame > self.config.frame_timeout:
                            raise ValueError("Stream sends data but no decodable frame")
                        for jpeg in parser.feed(chunk):
                            frame = cv2.imdecode(np.frombuffer(jpeg, np.uint8), cv2.IMREAD_COLOR)
                            if frame is None or frame.size == 0:
                                continue
                            last_frame = time.monotonic()
                            delay = self.config.reconnect_initial
                            self.frames_received += 1
                            yield frame
                    if not stop.is_set():
                        raise ValueError("Camera closed the stream")
            except (requests.RequestException, HTTPError, ValueError, cv2.error) as exc:
                self.failures += 1
                # Avoid printing request URLs/credentials or biometric data.
                reason = str(exc) if isinstance(exc, ValueError) else type(exc).__name__
                LOG.warning("Camera unavailable (%s); retry in %.1fs", reason, delay)
            if stop.is_set() or (deadline is not None and time.monotonic() >= deadline):
                break
            wait = delay if deadline is None else min(delay, max(0, deadline - time.monotonic()))
            if stop.wait(wait):
                break
            delay = min(delay * 2, self.config.reconnect_max)

    def close(self):
        self.session.close()
