from __future__ import annotations

import threading
import time
import uuid
from dataclasses import dataclass, field

import cv2
import numpy as np

from .config import FaceConfig
from .face_detector import FaceDetector
from .face_recognizer import FaceRecognizer, representative_embedding


@dataclass
class EnrollmentSession:
    session_id: str
    created_at: float
    last_sample_at: float = 0.0
    embeddings: list[np.ndarray] = field(default_factory=list)
    completed_embedding: np.ndarray | None = None


class EnrollmentManager:
    def __init__(self, config: FaceConfig, detector: FaceDetector, recognizer: FaceRecognizer) -> None:
        self.config = config
        self.detector = detector
        self.recognizer = recognizer
        self._sessions: dict[str, EnrollmentSession] = {}
        self._lock = threading.RLock()

    def create(self) -> EnrollmentSession:
        self._remove_expired()
        session = EnrollmentSession(session_id=str(uuid.uuid4()), created_at=time.monotonic())
        with self._lock:
            self._sessions[session.session_id] = session
        return session

    def cancel(self, session_id: str) -> bool:
        with self._lock:
            return self._sessions.pop(session_id, None) is not None

    def add_sample(self, session_id: str, image_bytes: bytes) -> dict:
        frame = self._decode_image(image_bytes)

        with self._lock:
            session = self._sessions.get(session_id)
            if not session or time.monotonic() - session.created_at > self.config.session_ttl_seconds:
                self._sessions.pop(session_id, None)
                raise KeyError("Enrollment session expired")
            if session.completed_embedding is not None:
                return self._result(session, accepted=False, reason="complete")

            now = time.monotonic()
            if now - session.last_sample_at < self.config.sample_interval_seconds:
                return self._result(session, accepted=False, reason="too_soon")

            faces = self.detector.detect(frame)
            if len(faces) == 0:
                return self._result(session, accepted=False, reason="no_face")
            if len(faces) > 1:
                return self._result(session, accepted=False, reason="multiple_faces")
            face = faces[0]
            _, _, width, height = face.box
            if min(width, height) < self.config.minimum_face_size:
                return self._result(session, accepted=False, reason="face_too_small")

            session.embeddings.append(self.recognizer.embedding(frame, face))
            session.last_sample_at = now
            if len(session.embeddings) >= self.config.registration_samples:
                session.completed_embedding = representative_embedding(session.embeddings)
            return self._result(session, accepted=True)

    def recognize(self, image_bytes: bytes, candidates: list[dict]) -> dict:
        frame = self._decode_image(image_bytes)
        faces = self.detector.detect(frame)
        if len(faces) == 0:
            return self._recognition_result(reason="no_face")
        if len(faces) > 1:
            return self._recognition_result(reason="multiple_faces")

        face = faces[0]
        _, _, width, height = face.box
        if min(width, height) < self.config.minimum_face_size:
            return self._recognition_result(reason="face_too_small")

        probe = self.recognizer.embedding(frame, face)
        scores: list[tuple[float, str]] = []
        for candidate in candidates:
            score = FaceRecognizer.similarity(probe, np.asarray(candidate["embedding"], dtype=np.float32))
            scores.append((score, candidate["customer_id"]))

        scores.sort(reverse=True)
        best_score, best_id = scores[0] if scores else (-1.0, None)
        second_score = scores[1][0] if len(scores) > 1 else -1.0
        ambiguous = (
            best_id is not None
            and best_score >= self.config.recognition_threshold
            and second_score >= self.config.recognition_threshold
            and best_score - second_score < 0.08
        )

        matched = best_id is not None and best_score >= self.config.recognition_threshold and not ambiguous
        return {
            "matched": matched,
            "reason": None if matched else ("ambiguous_face" if ambiguous else "unknown_face"),
            "customer_id": best_id if matched else None,
            "score": round(best_score, 6) if best_id is not None else None,
            "second_score": round(second_score, 6) if len(scores) > 1 else None,
            "threshold": self.config.recognition_threshold,
        }

    def _decode_image(self, image_bytes: bytes) -> np.ndarray:
        if not image_bytes or len(image_bytes) > 3_000_000:
            raise ValueError("Invalid image size")
        frame = cv2.imdecode(np.frombuffer(image_bytes, dtype=np.uint8), cv2.IMREAD_COLOR)
        if frame is None:
            raise ValueError("Cannot decode image")
        return frame

    def _recognition_result(self, reason: str) -> dict:
        return {
            "matched": False,
            "reason": reason,
            "customer_id": None,
            "score": None,
            "threshold": self.config.recognition_threshold,
        }

    def _result(self, session: EnrollmentSession, accepted: bool, reason: str | None = None) -> dict:
        complete = session.completed_embedding is not None
        result = {
            "accepted": accepted,
            "reason": reason,
            "samples": len(session.embeddings),
            "target": self.config.registration_samples,
            "complete": complete,
        }
        if complete:
            result["embedding"] = session.completed_embedding.astype(float).tolist()
        return result

    def _remove_expired(self) -> None:
        cutoff = time.monotonic() - self.config.session_ttl_seconds
        with self._lock:
            expired = [key for key, value in self._sessions.items() if value.created_at < cutoff]
            for key in expired:
                del self._sessions[key]
