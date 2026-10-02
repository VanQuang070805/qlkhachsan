"""Enroll one face in RAM, then test SFace matching. Press Q to quit."""
from __future__ import annotations

import time

import cv2

from app.config import FaceConfig
from app.face_detector import FaceDetector, open_camera
from app.face_recognizer import FaceRecognizer, representative_embedding
from app.visuals import draw_detection


def main() -> int:
    config = FaceConfig.from_env()
    config.validate()
    detector = FaceDetector(config.yunet_model_path, config.detection_threshold)
    recognizer = FaceRecognizer(config.sface_model_path)
    camera = open_camera(config.camera_index)
    samples = []
    reference = None
    last_sample_at = 0.0
    frame_number = 0
    started = time.perf_counter()

    try:
        while True:
            ok, frame = camera.read()
            if not ok or frame is None:
                raise RuntimeError("Webcam returned an invalid frame")
            frame_number += 1
            faces = detector.detect(frame) if frame_number % config.process_every_n_frames == 0 else []
            label = "Show exactly one face"

            if len(faces) == 1:
                face = faces[0]
                draw_detection(frame, face)
                _, _, width, height = face.box
                now = time.monotonic()
                if min(width, height) < config.minimum_face_size:
                    label = "Move closer to camera"
                elif reference is None:
                    label = f"Samples: {len(samples)}/{config.registration_samples}"
                    if now - last_sample_at >= config.sample_interval_seconds:
                        samples.append(recognizer.embedding(frame, face))
                        last_sample_at = now
                    if len(samples) >= config.registration_samples:
                        reference = representative_embedding(samples)
                        label = "Enrollment complete"
                        print(f"Embedding ready: shape={reference.shape}, samples={len(samples)}")
                else:
                    score = recognizer.similarity(reference, recognizer.embedding(frame, face))
                    result = "MATCH" if score >= config.recognition_threshold else "UNKNOWN"
                    label = f"{result}  similarity={score:.3f}"
            elif len(faces) > 1:
                label = "Only one face is allowed"

            elapsed = max(time.perf_counter() - started, 1e-6)
            cv2.putText(frame, label, (12, 28), cv2.FONT_HERSHEY_SIMPLEX, 0.65, (255, 255, 255), 2, cv2.LINE_AA)
            cv2.putText(frame, f"FPS: {frame_number / elapsed:.1f}", (12, 56), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2, cv2.LINE_AA)
            cv2.imshow("SFace RAM enrollment test - Q to quit", frame)
            if cv2.waitKey(1) & 0xFF in (ord("q"), ord("Q")):
                return 0
    finally:
        camera.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    raise SystemExit(main())

