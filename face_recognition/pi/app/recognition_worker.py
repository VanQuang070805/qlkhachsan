from __future__ import annotations

import logging
import threading
import time

from .cache import FaceCache
from .config import PiConfig
from .face_detector import FaceDetector
from .face_recognizer import FaceRecognizer
from .pi_camera import PiCamera


LOGGER = logging.getLogger("hotel-face-pi.recognition")


class RecognitionWorker:
    def __init__(self, config: PiConfig, cache: FaceCache) -> None:
        self.config = config
        self.cache = cache
        self.stop_event = threading.Event()
        self.thread: threading.Thread | None = None

    def start(self) -> None:
        if self.thread and self.thread.is_alive():
            return
        self.thread = threading.Thread(target=self.run, name="face-recognition", daemon=True)
        self.thread.start()

    def stop(self) -> None:
        self.stop_event.set()
        if self.thread:
            self.thread.join(timeout=self.config.request_timeout + 2)

    def run(self) -> None:
        try:
            detector = FaceDetector(self.config.yunet_model_path, self.config.detection_threshold)
            recognizer = FaceRecognizer(self.config.sface_model_path)
        except Exception:
            LOGGER.exception("Recognition worker cannot load models")
            return

        camera = PiCamera(
            self.config.camera_index,
            self.config.camera_width,
            self.config.camera_height,
            self.config.camera_framerate,
            self.config.camera_warmup_seconds,
            self.config.reconnect_initial,
            self.config.reconnect_max,
        )
        frame_count = processed = 0
        started = last_fps_log = time.monotonic()
        last_result: str | None = None
        try:
            for frame in camera.frames(self.stop_event):
                frame_count += 1
                if frame_count % self.config.process_every_n_frames:
                    continue
                processed += 1
                faces = detector.detect(frame)
                result = "UNKNOWN"
                best_score = 0.0
                if faces:
                    face = max(faces, key=lambda item: item.box[2] * item.box[3])
                    embedding = recognizer.embedding(frame, face)
                    matched, best_score = self.cache.match(embedding, self.config.recognition_threshold)
                    if matched:
                        result = f"{matched.customer_id}|{matched.name}|{matched.room}"
                if result != last_result:
                    if result == "UNKNOWN":
                        LOGGER.info("Recognition: UNKNOWN similarity=%.3f", best_score)
                    else:
                        customer_id, name, room = result.split("|", 2)
                        LOGGER.info("Recognition: customer_id=%s name=%s room=%s similarity=%.3f", customer_id, name, room, best_score)
                    last_result = result
                now = time.monotonic()
                if now - last_fps_log >= 5:
                    LOGGER.info("Recognition FPS=%.1f processed=%d cache=%d", frame_count / max(now - started, 0.001), processed, self.cache.size)
                    last_fps_log = now
        finally:
            camera.close()
