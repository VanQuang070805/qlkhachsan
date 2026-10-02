from pathlib import Path

import numpy as np
import pytest

from app.cache import FaceCache
from app.database import FaceDatabase, FaceRecord


def record(customer_id: str = "KH001", version: int = 1, marker: float = 1.0) -> FaceRecord:
    embedding = np.zeros(128, dtype=np.float32)
    embedding[0] = marker
    embedding[1] = 0.2
    return FaceRecord(
        customer_id=customer_id,
        booking_id=1,
        user_id=None,
        name="Khach thu nghiem",
        room="101",
        embedding=embedding,
        embedding_model="sface_2021dec",
        version=version,
        valid_until="2026-09-26",
    )


def test_embedding_round_trip_and_upsert(tmp_path: Path):
    database = FaceDatabase(tmp_path / "faces.db")
    database.initialize()
    assert database.upsert(record()) == "added"
    assert database.upsert(record(version=2, marker=0.7)) == "updated"

    saved = database.load_all()
    assert len(saved) == 1
    assert saved[0].version == 2
    assert np.linalg.norm(saved[0].embedding) == pytest.approx(1.0)


def test_stale_update_does_not_overwrite_newer_version(tmp_path: Path):
    database = FaceDatabase(tmp_path / "faces.db")
    database.initialize()
    database.upsert(record(version=3))
    assert database.upsert(record(version=2, marker=0.5)) == "stale"
    assert database.load_all()[0].version == 3


def test_full_sync_adds_updates_and_deletes_atomically(tmp_path: Path):
    database = FaceDatabase(tmp_path / "faces.db")
    database.initialize()
    database.upsert(record("OLD"))
    database.upsert(record("KH001"))

    result = database.full_sync([record("KH001", version=2), record("KH002")])

    assert result == {"received": 2, "added": 1, "updated": 1, "deleted": 1}
    assert {item.customer_id for item in database.load_all()} == {"KH001", "KH002"}


def test_cache_matches_without_querying_per_frame(tmp_path: Path):
    database = FaceDatabase(tmp_path / "faces.db")
    database.initialize()
    database.upsert(record())
    cache = FaceCache(database)
    assert cache.reload() == 1

    matched, score = cache.match(record().embedding, threshold=0.5)
    assert matched is not None
    assert matched.customer_id == "KH001"
    assert score > 0.99

