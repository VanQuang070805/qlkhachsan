from __future__ import annotations

import hashlib
import shutil
import tempfile
from pathlib import Path


def opencv_model_path(path: Path, label: str) -> str:
    if not path.is_file() or path.stat().st_size == 0:
        raise FileNotFoundError(f"{label} model not found: {path}")
    try:
        str(path).encode("ascii")
        return str(path)
    except UnicodeEncodeError:
        digest = hashlib.sha256(path.read_bytes()).hexdigest()[:16]
        directory = Path(tempfile.gettempdir()) / "hotel_face_models"
        directory.mkdir(parents=True, exist_ok=True)
        cached = directory / f"pi-{label.lower()}-{digest}.onnx"
        if not cached.is_file() or cached.stat().st_size != path.stat().st_size:
            temporary = cached.with_suffix(".tmp")
            shutil.copyfile(path, temporary)
            temporary.replace(cached)
        return str(cached)

