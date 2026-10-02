from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

import cv2
import numpy as np

from .config import opencv_model_path


@dataclass(frozen=True)
class FaceDetection:
    box: tuple[int, int, int, int]
    landmarks: np.ndarray
    confidence: float
    raw: np.ndarray


class FaceDetector:
    """Reusable YuNet wrapper with no camera or UI dependency."""

    def __init__(
        self,
        model_path: str | Path,
        score_threshold: float = 0.9,
        nms_threshold: float = 0.3,
        top_k: int = 5000,
    ) -> None:
        path = Path(model_path)
        compatible_path = opencv_model_path(path, "YuNet")
        try:
            self._detector = cv2.FaceDetectorYN.create(
                compatible_path, "", (320, 320), score_threshold, nms_threshold, top_k
            )
        except cv2.error as exc:
            raise RuntimeError(f"Cannot load YuNet model: {path}") from exc

    def detect(self, frame: np.ndarray) -> list[FaceDetection]:
        if not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
            raise ValueError("Frame must be a non-empty BGR image")

        height, width = frame.shape[:2]
        if width < 2 or height < 2:
            raise ValueError("Frame dimensions are too small")

        self._detector.setInputSize((width, height))
        try:
            _, faces = self._detector.detect(frame)
        except cv2.error as exc:
            raise RuntimeError("YuNet failed to process the frame") from exc

        if faces is None:
            return []

        detections: list[FaceDetection] = []
        for face in faces:
            x, y, w, h = (int(round(value)) for value in face[:4])
            landmarks = face[4:14].reshape(5, 2).copy()
            detections.append(
                FaceDetection(
                    box=(x, y, w, h),
                    landmarks=landmarks,
                    confidence=float(face[14]),
                    raw=face.copy(),
                )
            )
        return sorted(detections, key=lambda item: item.confidence, reverse=True)


def open_camera(index: int) -> cv2.VideoCapture:
    camera = cv2.VideoCapture(index, cv2.CAP_DSHOW)
    if not camera.isOpened():
        camera.release()
        camera = cv2.VideoCapture(index)
    if not camera.isOpened():
        camera.release()
        raise RuntimeError(f"Cannot open webcam at index {index}")
    return camera
