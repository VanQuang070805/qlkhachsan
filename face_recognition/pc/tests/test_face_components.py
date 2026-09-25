from pathlib import Path

import numpy as np
import pytest

from app.config import FaceConfig, ensure_model_exists
from app.face_detector import FaceDetector
from app.face_recognizer import normalize_embedding, representative_embedding


def test_normalize_embedding_has_unit_norm():
    result = normalize_embedding(np.array([3.0, 4.0], dtype=np.float32))
    assert np.linalg.norm(result) == pytest.approx(1.0)
    assert result.tolist() == pytest.approx([0.6, 0.8])


def test_representative_embedding_is_normalized():
    result = representative_embedding(
        [np.array([1.0, 0.0]), np.array([0.8, 0.2]), np.array([0.9, 0.1])]
    )
    assert result.shape == (2,)
    assert np.linalg.norm(result) == pytest.approx(1.0)


def test_invalid_embedding_is_rejected():
    with pytest.raises(ValueError):
        normalize_embedding(np.zeros(128))
    with pytest.raises(ValueError):
        normalize_embedding(np.array([np.nan, 1.0]))


def test_missing_model_has_clear_error(tmp_path: Path):
    missing = tmp_path / "missing.onnx"
    with pytest.raises(FileNotFoundError, match="YuNet model not found"):
        ensure_model_exists(missing, "YuNet")


def test_yunet_loads_and_accepts_blank_frame():
    config = FaceConfig.from_env()
    detector = FaceDetector(config.yunet_model_path, config.detection_threshold)
    assert detector.detect(np.zeros((320, 320, 3), dtype=np.uint8)) == []

