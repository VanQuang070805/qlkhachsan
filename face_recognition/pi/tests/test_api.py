from pathlib import Path

from fastapi.testclient import TestClient

from app.config import PiConfig
from app.main import create_app


def settings(tmp_path: Path) -> PiConfig:
    return PiConfig(
        database_path=tmp_path / "faces.db",
        api_key="test-secret",
        host="127.0.0.1",
        port=8002,
        yunet_model_path=tmp_path / "yunet.onnx",
        sface_model_path=tmp_path / "sface.onnx",
        detection_threshold=0.9,
        recognition_threshold=0.363,
        process_every_n_frames=3,
        request_timeout=5,
    )


def payload(customer_id: str = "KH001", version: int = 1) -> dict:
    return {
        "customer_id": customer_id,
        "booking_id": 10,
        "user_id": None,
        "name": "Khach thu nghiem",
        "room": "101",
        "embedding": [1.0] + [0.0] * 127,
        "embedding_model": "sface_2021dec",
        "version": version,
        "valid_until": "2026-09-26",
    }


def test_health_is_public_but_face_list_requires_key(tmp_path: Path):
    with TestClient(create_app(settings(tmp_path))) as client:
        assert client.get("/api/health").json() == {"status": "ok", "faces": 0}
        assert client.get("/api/faces").status_code == 401
        assert client.get("/api/faces", headers={"X-API-Key": "wrong"}).status_code == 401


def test_add_update_delete_are_idempotent_and_hide_embedding(tmp_path: Path):
    headers = {"X-API-Key": "test-secret"}
    with TestClient(create_app(settings(tmp_path))) as client:
        assert client.post("/api/faces", headers=headers, json=payload()).status_code == 200
        assert client.post("/api/faces", headers=headers, json=payload()).status_code == 200
        faces = client.get("/api/faces", headers=headers).json()["faces"]
        assert len(faces) == 1
        assert "embedding" not in faces[0]
        assert client.delete("/api/faces/KH001", headers=headers).json()["status"] == "deleted"
        assert client.delete("/api/faces/KH001", headers=headers).json()["status"] == "already_absent"


def test_full_sync_replaces_snapshot(tmp_path: Path):
    headers = {"X-API-Key": "test-secret"}
    with TestClient(create_app(settings(tmp_path))) as client:
        client.post("/api/faces", headers=headers, json=payload("OLD"))
        response = client.post(
            "/api/faces/full-sync",
            headers=headers,
            json={"complete": True, "faces": [payload("KH001"), payload("KH002")]},
        )
        assert response.status_code == 200
        assert response.json() == {"received": 2, "added": 2, "updated": 0, "deleted": 1}
        assert client.get("/api/health").json()["faces"] == 2


def test_incomplete_snapshot_is_rejected_without_deleting_data(tmp_path: Path):
    headers = {"X-API-Key": "test-secret"}
    with TestClient(create_app(settings(tmp_path))) as client:
        client.post("/api/faces", headers=headers, json=payload())
        response = client.post("/api/faces/full-sync", headers=headers, json={"complete": False, "faces": []})
        assert response.status_code == 422
        assert client.get("/api/health").json()["faces"] == 1
