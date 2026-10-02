from __future__ import annotations

import logging
import threading
import time

import cv2

from .cache import FaceCache
from .config import PiConfig
from .door_servo import DoorServo
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

        servo: DoorServo | None = None
        if self.config.servo_enabled:
            try:
                servo = DoorServo(
                    pin=self.config.servo_gpio_pin,
                    closed_angle=self.config.servo_closed_angle,
                    open_angle=self.config.servo_open_angle,
                    move_seconds=self.config.servo_move_seconds,
                    hold_seconds=self.config.servo_hold_seconds,
                    cooldown_seconds=self.config.servo_cooldown_seconds,
                    min_pulse_width=self.config.servo_min_pulse_width,
                    max_pulse_width=self.config.servo_max_pulse_width,
                    detach_after_move=self.config.servo_detach_after_move,
                    pwm_backend=self.config.servo_pwm_backend,
                )
                LOGGER.info("Door servo ready on BCM GPIO %d", self.config.servo_gpio_pin)
            except Exception:
                LOGGER.exception("Door servo initialization failed; recognition will continue")

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
        last_label = "UNKNOWN"
        last_box: tuple[int, int, int, int] | None = None
        last_score = 0.0
        preview_enabled = self.config.show_preview
        window_name = "Hotel Face ID - Raspberry Pi (Q/Esc to close)"
        try:
            for frame in camera.frames(self.stop_event):
                frame_count += 1
                if frame_count % self.config.process_every_n_frames == 0:
                    processed += 1
                    faces = detector.detect(frame)
                    result = "UNKNOWN"
                    last_label = "UNKNOWN"
                    last_box = None
                    last_score = 0.0
                    if faces:
                        face = max(faces, key=lambda item: item.box[2] * item.box[3])
                        last_box = face.box
                        embedding = recognizer.embedding(frame, face)
                        matched, last_score = self.cache.match(embedding, self.config.recognition_threshold)
                        if matched:
                            result = f"{matched.customer_id}|{matched.name}|{matched.room}"
                            last_label = f"MATCH: {matched.name} | Room {matched.room}"
                            # Ask the servo to open on every successful match.
                            # DoorServo rejects requests while it is moving or
                            # inside its cooldown, preventing overlapping moves
                            # and GPIO jitter while keeping recognition active.
                            if servo and servo.unlock():
                                LOGGER.info(
                                    "Door unlock triggered for customer_id=%s",
                                    matched.customer_id,
                                )

                    if result != last_result:
                        if result == "UNKNOWN":
                            LOGGER.info("Recognition: UNKNOWN similarity=%.3f", last_score)
                        else:
                            customer_id, name, room = result.split("|", 2)
                            LOGGER.info("Recognition: customer_id=%s name=%s room=%s similarity=%.3f", customer_id, name, room, last_score)
                        last_result = result

                if preview_enabled:
                    preview = frame.copy()
                    color = (0, 190, 0) if last_label.startswith("MATCH:") else (0, 0, 220)
                    if last_box:
                        x, y, width, height = last_box
                        cv2.rectangle(preview, (x, y), (x + width, y + height), color, 2)
                    status = f"{last_label} | similarity={last_score:.3f}"
                    cv2.rectangle(preview, (0, 0), (preview.shape[1], 34), (20, 20, 20), -1)
                    cv2.putText(preview, status, (8, 23), cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 1, cv2.LINE_AA)
                    try:
                        cv2.imshow(window_name, preview)
                        if cv2.waitKey(1) & 0xFF in (ord("q"), 27):
                            LOGGER.info("Preview closed by user")
                            self.stop_event.set()
                            break
                    except cv2.error:
                        LOGGER.warning("OpenCV GUI is unavailable; continuing without preview")
                        preview_enabled = False
                now = time.monotonic()
                if now - last_fps_log >= 5:
                    LOGGER.info("Recognition FPS=%.1f processed=%d cache=%d", frame_count / max(now - started, 0.001), processed, self.cache.size)
                    last_fps_log = now
        finally:
            camera.close()
            if preview_enabled:
                try:
                    cv2.destroyWindow(window_name)
                except cv2.error:
                    pass
            if servo:
                servo.close()
