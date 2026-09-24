"""Milestone 1 only: camera diagnostics. No recognition or GPIO output."""
import argparse
import importlib.metadata
import json
import logging
import platform
import signal
import threading
import time

import cv2

from camera.esp32_camera import ESP32Camera
from config import CameraConfig


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check-env", action="store_true")
    parser.add_argument("--url", help="Overrides CAMERA_URL")
    parser.add_argument("--headless", action="store_true", help="No GUI; use over SSH")
    parser.add_argument("--seconds", type=float, default=0, help="0 = until Ctrl+C; timeout may add read timeout")
    parser.add_argument("--min-frames", type=int, default=1)
    parser.add_argument("--report", help="Write diagnostic JSON (no images)")
    args = parser.parse_args()
    if args.seconds < 0 or args.min_frames < 1:
        parser.error("seconds must be >= 0 and min-frames must be >= 1")
    if args.check_env:
        print(json.dumps({
            "python": platform.python_version(), "platform": platform.system(),
            "architecture": platform.machine(), "opencv": cv2.__version__,
            "requests": importlib.metadata.version("requests"),
            "yunet_api": hasattr(cv2, "FaceDetectorYN"),
            "sface_api": hasattr(cv2, "FaceRecognizerSF"),
        }, indent=2))
        return 0
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    try:
        config = CameraConfig.from_env(args.url)
    except ValueError as exc:
        parser.error(str(exc))
    stop = threading.Event()
    for signum in (signal.SIGINT, signal.SIGTERM):
        signal.signal(signum, lambda *_: stop.set())
    camera = ESP32Camera(config)
    started = time.monotonic()
    deadline = started + args.seconds if args.seconds else None
    width = height = 0
    last_log = started
    stream = camera.frames(stop, deadline)
    try:
        for frame in stream:
            height, width = frame.shape[:2]
            if time.monotonic() - last_log >= 5:
                logging.info("Frames=%d size=%dx%d failures=%d", camera.frames_received, width, height, camera.failures)
                last_log = time.monotonic()
            if not args.headless:
                cv2.imshow("ESP32-CAM - camera test only", frame)
                if cv2.waitKey(1) & 0xFF == ord("q"):
                    stop.set()
    except cv2.error:
        logging.error("Cannot display preview. Use --headless for SSH/no desktop.")
        return 2
    finally:
        stream.close()
        camera.close()
        if not args.headless:
            try:
                cv2.destroyAllWindows()
            except cv2.error:
                pass  # A headless OpenCV build has no window backend to clean up.
    elapsed = time.monotonic() - started
    report = {
        "milestone": "camera-only", "frames": camera.frames_received,
        "width": width, "height": height, "connections": camera.connections,
        "failures": camera.failures, "elapsed_seconds": round(elapsed, 2),
        "average_fps": round(camera.frames_received / max(elapsed, 0.001), 2),
        "received_minimum_frames": camera.frames_received >= args.min_frames,
    }
    print(json.dumps(report, indent=2))
    if args.report:
        from pathlib import Path
        Path(args.report).write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    return 0 if report["received_minimum_frames"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
