import threading

import numpy as np

from app.pi_camera import PiCamera


class FakeCamera:
    def __init__(self, frame):
        self.frame = frame
        self.configuration = None
        self.stopped = False
        self.closed = False

    def create_video_configuration(self, **kwargs):
        self.configuration = kwargs
        return kwargs

    def configure(self, _configuration):
        pass

    def start(self):
        pass

    def capture_array(self, stream):
        assert stream == "main"
        return self.frame

    def stop(self):
        self.stopped = True

    def close(self):
        self.closed = True


def test_picamera2_source_returns_opencv_frame_and_releases_camera():
    backend = FakeCamera(np.zeros((480, 640, 3), dtype=np.uint8))
    camera = PiCamera(0, 640, 480, 15, 0, 0.01, 1, lambda _index: backend)
    stream = camera.frames(threading.Event())

    frame = next(stream)
    stream.close()

    assert frame.shape == (480, 640, 3)
    assert backend.configuration["main"] == {"size": (640, 480), "format": "RGB888"}
    assert backend.stopped and backend.closed
