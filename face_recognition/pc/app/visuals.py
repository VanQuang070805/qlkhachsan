from __future__ import annotations

import cv2
import numpy as np

from .face_detector import FaceDetection


LANDMARK_COLORS = (
    (255, 90, 90),
    (90, 255, 90),
    (90, 90, 255),
    (255, 220, 90),
    (220, 90, 255),
)


def draw_detection(frame: np.ndarray, detection: FaceDetection) -> None:
    x, y, width, height = detection.box
    cv2.rectangle(frame, (x, y), (x + width, y + height), (45, 220, 120), 2)
    for point, color in zip(detection.landmarks.astype(int), LANDMARK_COLORS):
        cv2.circle(frame, tuple(point), 2, color, 2)
    cv2.putText(
        frame,
        f"{detection.confidence:.2f}",
        (max(0, x), max(20, y - 8)),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.6,
        (45, 220, 120),
        2,
        cv2.LINE_AA,
    )

