from __future__ import annotations

import threading

import numpy as np

from .database import FaceDatabase, FaceRecord, normalize_embedding


class FaceCache:
    def __init__(self, database: FaceDatabase) -> None:
        self._database = database
        self._lock = threading.RLock()
        self._records: list[FaceRecord] = []
        self._matrix = np.empty((0, 0), dtype=np.float32)

    def reload(self) -> int:
        records = self._database.load_all()
        matrix = np.stack([record.embedding for record in records]) if records else np.empty((0, 0), dtype=np.float32)
        with self._lock:
            self._records = records
            self._matrix = matrix
        return len(records)

    @property
    def size(self) -> int:
        with self._lock:
            return len(self._records)

    def match(self, embedding: np.ndarray, threshold: float) -> tuple[FaceRecord | None, float]:
        query = normalize_embedding(embedding)
        with self._lock:
            if not self._records or self._matrix.shape[1] != query.size:
                return None, 0.0
            scores = self._matrix @ query
            index = int(np.argmax(scores))
            score = float(scores[index])
            return (self._records[index] if score >= threshold else None), score

