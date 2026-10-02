from __future__ import annotations

import sqlite3
from contextlib import contextmanager
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Iterable, Iterator

import numpy as np


@dataclass(frozen=True)
class FaceRecord:
    customer_id: str
    booking_id: int
    user_id: int | None
    name: str
    room: str
    embedding: np.ndarray
    embedding_model: str
    version: int
    valid_until: str | None


def normalize_embedding(values: Iterable[float]) -> np.ndarray:
    embedding = np.asarray(list(values), dtype=np.float32).reshape(-1)
    if not 16 <= embedding.size <= 4096 or not np.isfinite(embedding).all():
        raise ValueError("Invalid embedding")
    norm = float(np.linalg.norm(embedding))
    if norm <= 1e-12:
        raise ValueError("Embedding norm is zero")
    return embedding / norm


class FaceDatabase:
    def __init__(self, path: str | Path) -> None:
        self.path = Path(path)
        self.path.parent.mkdir(parents=True, exist_ok=True)

    @contextmanager
    def connection(self) -> Iterator[sqlite3.Connection]:
        connection = sqlite3.connect(self.path, timeout=10)
        connection.row_factory = sqlite3.Row
        connection.execute("PRAGMA foreign_keys = ON")
        connection.execute("PRAGMA journal_mode = WAL")
        try:
            yield connection
        finally:
            connection.close()

    def initialize(self) -> None:
        with self.connection() as connection:
            connection.execute(
                """
                CREATE TABLE IF NOT EXISTS faces (
                    customer_id TEXT PRIMARY KEY,
                    booking_id INTEGER NOT NULL,
                    user_id INTEGER,
                    name TEXT NOT NULL,
                    room TEXT NOT NULL DEFAULT '',
                    embedding BLOB NOT NULL,
                    embedding_dimension INTEGER NOT NULL,
                    embedding_model TEXT NOT NULL,
                    version INTEGER NOT NULL,
                    valid_until TEXT,
                    updated_at TEXT NOT NULL
                )
                """
            )
            connection.commit()

    def upsert(self, record: FaceRecord) -> str:
        clean = self.validate_record(record)
        with self.connection() as connection:
            existing = connection.execute(
                "SELECT version FROM faces WHERE customer_id = ?", (clean.customer_id,)
            ).fetchone()
            if existing and int(existing["version"]) > clean.version:
                return "stale"
            action = "updated" if existing else "added"
            self._upsert(connection, clean)
            connection.commit()
            return action

    def delete(self, customer_id: str) -> bool:
        with self.connection() as connection:
            cursor = connection.execute("DELETE FROM faces WHERE customer_id = ?", (customer_id,))
            connection.commit()
            return cursor.rowcount > 0

    def list_metadata(self) -> list[dict]:
        with self.connection() as connection:
            rows = connection.execute(
                "SELECT customer_id, booking_id, user_id, name, room, embedding_model, version, valid_until, updated_at FROM faces ORDER BY name"
            ).fetchall()
        return [dict(row) for row in rows]

    def load_all(self) -> list[FaceRecord]:
        with self.connection() as connection:
            rows = connection.execute("SELECT * FROM faces ORDER BY customer_id").fetchall()
        return [self._row_to_record(row) for row in rows]

    def full_sync(self, records: list[FaceRecord]) -> dict[str, int]:
        clean = [self.validate_record(record) for record in records]
        ids = [record.customer_id for record in clean]
        if len(ids) != len(set(ids)):
            raise ValueError("Snapshot contains duplicate customer_id")

        with self.connection() as connection:
            connection.execute("BEGIN IMMEDIATE")
            try:
                existing = {row["customer_id"]: int(row["version"]) for row in connection.execute("SELECT customer_id, version FROM faces")}
                added = updated = 0
                for record in clean:
                    if record.customer_id in existing:
                        updated += 1
                    else:
                        added += 1
                    self._upsert(connection, record)
                if ids:
                    placeholders = ",".join("?" for _ in ids)
                    deleted = connection.execute(
                        f"DELETE FROM faces WHERE customer_id NOT IN ({placeholders})", ids
                    ).rowcount
                else:
                    deleted = connection.execute("DELETE FROM faces").rowcount
                connection.commit()
            except Exception:
                connection.rollback()
                raise
        return {"received": len(clean), "added": added, "updated": updated, "deleted": deleted}

    def validate_record(self, record: FaceRecord) -> FaceRecord:
        if not record.customer_id or len(record.customer_id) > 64:
            raise ValueError("Invalid customer_id")
        if record.booking_id < 1 or record.version < 1:
            raise ValueError("Invalid booking_id or version")
        if not record.name or len(record.name) > 255 or len(record.room) > 255:
            raise ValueError("Invalid customer metadata")
        if len(record.embedding_model) > 80:
            raise ValueError("Invalid embedding model")
        return FaceRecord(
            customer_id=record.customer_id,
            booking_id=record.booking_id,
            user_id=record.user_id,
            name=record.name,
            room=record.room,
            embedding=normalize_embedding(record.embedding),
            embedding_model=record.embedding_model,
            version=record.version,
            valid_until=record.valid_until,
        )

    def _upsert(self, connection: sqlite3.Connection, record: FaceRecord) -> None:
        connection.execute(
            """
            INSERT INTO faces (customer_id, booking_id, user_id, name, room, embedding, embedding_dimension, embedding_model, version, valid_until, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(customer_id) DO UPDATE SET
                booking_id = excluded.booking_id,
                user_id = excluded.user_id,
                name = excluded.name,
                room = excluded.room,
                embedding = excluded.embedding,
                embedding_dimension = excluded.embedding_dimension,
                embedding_model = excluded.embedding_model,
                version = excluded.version,
                valid_until = excluded.valid_until,
                updated_at = excluded.updated_at
            WHERE excluded.version >= faces.version
            """,
            (
                record.customer_id, record.booking_id, record.user_id, record.name, record.room,
                record.embedding.astype("<f4", copy=False).tobytes(), int(record.embedding.size),
                record.embedding_model, record.version, record.valid_until,
                datetime.now(timezone.utc).isoformat(),
            ),
        )

    @staticmethod
    def _row_to_record(row: sqlite3.Row) -> FaceRecord:
        dimension = int(row["embedding_dimension"])
        embedding = np.frombuffer(row["embedding"], dtype="<f4", count=dimension).copy()
        if embedding.size != dimension:
            raise ValueError("Corrupt embedding in database")
        return FaceRecord(
            customer_id=row["customer_id"], booking_id=int(row["booking_id"]), user_id=row["user_id"],
            name=row["name"], room=row["room"], embedding=normalize_embedding(embedding),
            embedding_model=row["embedding_model"], version=int(row["version"]), valid_until=row["valid_until"],
        )

