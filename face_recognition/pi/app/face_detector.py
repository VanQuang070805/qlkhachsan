from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

import cv2
import numpy as np

from .model_utils import opencv_model_path


@dataclass(frozen=True)
class FaceDetection:
    box: tuple[int, int, int, int]
    landmarks: np.ndarray
    confidence: float
    raw: np.ndarray


class FaceDetector:
    def __init__(self, model_path: str | Path, threshold: float = 0.9) -> None:
        path = Path(model_path)
        try:
            self._detector = cv2.FaceDetectorYN.create(
                opencv_model_path(path, "YuNet"), "", (320, 320), threshold, 0.3, 5000
            )
        except cv2.error as error:
            raise RuntimeError(f"Cannot load YuNet model: {path}") from error

    def detect(self, frame: np.ndarray) -> list[FaceDetection]:
        if not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
            raise ValueError("Invalid camera frame")
        height, width = frame.shape[:2]
        self._detector.setInputSize((width, height))
        _, faces = self._detector.detect(frame)
        if faces is None:
            return []
        return [
            FaceDetection(
                box=tuple(int(round(value)) for value in face[:4]),
                landmarks=face[4:14].reshape(5, 2).copy(),
                confidence=float(face[14]),
                raw=face.copy(),
            )
            for face in faces
        ]

