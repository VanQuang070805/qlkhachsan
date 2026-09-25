from __future__ import annotations

from pathlib import Path

import cv2
import numpy as np

from .database import normalize_embedding
from .face_detector import FaceDetection
from .model_utils import opencv_model_path


class FaceRecognizer:
    def __init__(self, model_path: str | Path) -> None:
        path = Path(model_path)
        try:
            self._recognizer = cv2.FaceRecognizerSF.create(opencv_model_path(path, "SFace"), "")
        except cv2.error as error:
            raise RuntimeError(f"Cannot load SFace model: {path}") from error

    def embedding(self, frame: np.ndarray, face: FaceDetection) -> np.ndarray:
        try:
            aligned = self._recognizer.alignCrop(frame, face.raw)
            feature = self._recognizer.feature(aligned)
        except cv2.error as error:
            raise RuntimeError("SFace failed to create an embedding") from error
        return normalize_embedding(feature.reshape(-1))

