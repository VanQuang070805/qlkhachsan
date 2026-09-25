from __future__ import annotations

from pathlib import Path
from typing import Iterable

import cv2
import numpy as np

from .config import opencv_model_path
from .face_detector import FaceDetection


class FaceRecognizer:
    """Reusable SFace alignment and embedding wrapper."""

    def __init__(self, model_path: str | Path) -> None:
        path = Path(model_path)
        compatible_path = opencv_model_path(path, "SFace")
        try:
            self._recognizer = cv2.FaceRecognizerSF.create(compatible_path, "")
        except cv2.error as exc:
            raise RuntimeError(f"Cannot load SFace model: {path}") from exc

    def embedding(self, frame: np.ndarray, detection: FaceDetection) -> np.ndarray:
        if not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
            raise ValueError("Frame must be a non-empty BGR image")
        try:
            aligned = self._recognizer.alignCrop(frame, detection.raw)
            feature = self._recognizer.feature(aligned)
        except cv2.error as exc:
            raise RuntimeError("SFace failed to create an embedding") from exc
        return normalize_embedding(feature.reshape(-1))

    @staticmethod
    def similarity(first: np.ndarray, second: np.ndarray) -> float:
        return float(np.dot(normalize_embedding(first), normalize_embedding(second)))


def normalize_embedding(embedding: np.ndarray) -> np.ndarray:
    value = np.asarray(embedding, dtype=np.float32).reshape(-1)
    if value.size == 0 or not np.isfinite(value).all():
        raise ValueError("Embedding must contain finite values")
    norm = float(np.linalg.norm(value))
    if norm <= 1e-12:
        raise ValueError("Embedding norm is zero")
    return value / norm


def representative_embedding(embeddings: Iterable[np.ndarray]) -> np.ndarray:
    normalized = [normalize_embedding(item) for item in embeddings]
    if not normalized:
        raise ValueError("At least one embedding is required")
    dimensions = {item.size for item in normalized}
    if len(dimensions) != 1:
        raise ValueError("All embeddings must have the same dimensions")
    return normalize_embedding(np.mean(np.stack(normalized), axis=0))
