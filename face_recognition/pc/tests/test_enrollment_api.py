import json
from dataclasses import replace

import numpy as np
from fastapi.testclient import TestClient

from app.config import FaceConfig
from app.enrollment import EnrollmentManager
from app.main import create_app


class FakeDetector:
    def detect(self, frame):
        from app.face_detector import FaceDetection
        raw = np.array([10, 10, 120, 120, 30, 40, 90, 40, 60, 70, 40, 100, 85, 100, 0.99], dtype=np.float32)
        return [FaceDetection((10, 10, 120, 120), raw[4:14].reshape(5, 2), 0.99, raw)]


class FakeRecognizer:
    def embedding(self, frame, face):
        result = np.zeros(128, dtype=np.float32)
        result[0] = 1.0
        return result


def test_enrollment_session_returns_progress_and_only_returns_embedding_when_complete(monkeypatch):
    config = replace(FaceConfig.from_env(), registration_samples=3, sample_interval_seconds=0.001)
    manager = EnrollmentManager(config, FakeDetector(), FakeRecognizer())
    app = create_app(config, manager)
    image = np.zeros((200, 200, 3), dtype=np.uint8)
    import cv2
    ok, encoded = cv2.imencode(".jpg", image)
    assert ok

    with TestClient(app) as client:
        session = client.post("/api/enrollment-sessions").json()
        responses = []
        for _ in range(3):
            monkeypatch.setattr("time.monotonic", lambda: len(responses) + 10.0)
            responses.append(client.post(
                f"/api/enrollment-sessions/{session['session_id']}/samples",
                files={"frame": ("frame.jpg", encoded.tobytes(), "image/jpeg")},
            ).json())

    assert [item["samples"] for item in responses] == [1, 2, 3]
    assert "embedding" not in responses[0]
    assert responses[-1]["complete"] is True
    assert len(responses[-1]["embedding"]) == 128


def test_recognition_matches_a_registered_candidate():
    config = replace(FaceConfig.from_env(), recognition_threshold=0.5)
    manager = EnrollmentManager(config, FakeDetector(), FakeRecognizer())
    app = create_app(config, manager)
    image = np.zeros((200, 200, 3), dtype=np.uint8)
    import cv2
    ok, encoded = cv2.imencode(".jpg", image)
    assert ok
    embedding = np.zeros(128, dtype=np.float32)
    embedding[0] = 1.0

    with TestClient(app) as client:
        response = client.post(
            "/api/recognize",
            files={"frame": ("frame.jpg", encoded.tobytes(), "image/jpeg")},
            data={"candidates": json.dumps([{"customer_id": "profile-1", "embedding": embedding.tolist()}])},
        )

    assert response.status_code == 200
    assert response.json()["matched"] is True
    assert response.json()["customer_id"] == "profile-1"
    assert response.json()["score"] == 1.0


def test_recognition_rejects_ambiguous_duplicate_profiles():
    config = replace(FaceConfig.from_env(), recognition_threshold=0.5)
    manager = EnrollmentManager(config, FakeDetector(), FakeRecognizer())
    app = create_app(config, manager)
    image = np.zeros((200, 200, 3), dtype=np.uint8)
    import cv2
    ok, encoded = cv2.imencode(".jpg", image)
    assert ok
    embedding = np.zeros(128, dtype=np.float32)
    embedding[0] = 1.0
    candidates = [
        {"customer_id": "profile-1", "embedding": embedding.tolist()},
        {"customer_id": "profile-2", "embedding": embedding.tolist()},
    ]

    with TestClient(app) as client:
        response = client.post(
            "/api/recognize",
            files={"frame": ("frame.jpg", encoded.tobytes(), "image/jpeg")},
            data={"candidates": json.dumps(candidates)},
        )

    assert response.status_code == 200
    assert response.json()["matched"] is False
    assert response.json()["reason"] == "ambiguous_face"
