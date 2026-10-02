"""Manual YuNet webcam test. Press Q to quit."""
from __future__ import annotations

import time

import cv2

from app.config import FaceConfig
from app.face_detector import FaceDetector, open_camera
from app.visuals import draw_detection


def main() -> int:
    config = FaceConfig.from_env()
    config.validate()
    detector = FaceDetector(config.yunet_model_path, config.detection_threshold)
    camera = open_camera(config.camera_index)
    frames = 0
    started = time.perf_counter()

    try:
        while True:
            ok, frame = camera.read()
            if not ok or frame is None:
                raise RuntimeError("Webcam returned an invalid frame")
            for face in detector.detect(frame):
                draw_detection(frame, face)
            frames += 1
            elapsed = max(time.perf_counter() - started, 1e-6)
            cv2.putText(
                frame,
                f"FPS: {frames / elapsed:.1f}",
                (12, 28),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.7,
                (255, 255, 255),
                2,
                cv2.LINE_AA,
            )
            cv2.imshow("YuNet camera test - Q to quit", frame)
            if cv2.waitKey(1) & 0xFF in (ord("q"), ord("Q")):
                return 0
    finally:
        camera.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    raise SystemExit(main())

