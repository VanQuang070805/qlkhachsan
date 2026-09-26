import threading
import unittest

import numpy as np

from camera.pi_camera import PiCamera
from config import CameraConfig


class FakeCamera:
    def __init__(self, outcomes):
        self.outcomes = iter(outcomes)
        self.config_args = None
        self.configured = None
        self.started = False
        self.stopped = False
        self.closed = False

    def create_video_configuration(self, **kwargs):
        self.config_args = kwargs
        return {"test": True}

    def configure(self, configuration):
        self.configured = configuration

    def start(self):
        self.started = True

    def capture_array(self, stream):
        assert stream == "main"
        outcome = next(self.outcomes)
        if isinstance(outcome, Exception):
            raise outcome
        return outcome

    def stop(self):
        self.stopped = True

    def close(self):
        self.closed = True


class PiCameraTests(unittest.TestCase):
    def test_reads_rgb888_frames_and_restarts_after_failure(self):
        frame = np.zeros((480, 640, 3), dtype=np.uint8)
        first = FakeCamera([frame, RuntimeError("camera disconnected")])
        second = FakeCamera([frame])
        cameras = iter([first, second])
        camera = PiCamera(
            CameraConfig(warmup_seconds=0, reconnect_initial=0.001),
            camera_factory=lambda _index: next(cameras),
        )
        stream = camera.frames()

        self.assertEqual(next(stream).shape, (480, 640, 3))
        self.assertEqual(next(stream).shape, (480, 640, 3))
        stream.close()

        self.assertEqual(camera.starts, 2)
        self.assertEqual(camera.failures, 1)
        self.assertEqual(camera.frames_received, 2)
        self.assertEqual(first.config_args["main"], {"size": (640, 480), "format": "RGB888"})
        self.assertEqual(first.config_args["controls"], {"FrameRate": 15.0})
        self.assertTrue(first.stopped and first.closed and second.stopped and second.closed)

    def test_stopped_reader_does_not_open_camera(self):
        stop = threading.Event()
        stop.set()
        opened = []
        camera = PiCamera(CameraConfig(), camera_factory=lambda index: opened.append(index))

        self.assertEqual(list(camera.frames(stop)), [])
        self.assertEqual(opened, [])

    def test_bad_configuration_is_rejected(self):
        with self.assertRaises(ValueError):
            CameraConfig(camera_index=-1)
        with self.assertRaises(ValueError):
            CameraConfig(width=80)
        with self.assertRaises(ValueError):
            CameraConfig(framerate=0)


if __name__ == "__main__":
    unittest.main()
