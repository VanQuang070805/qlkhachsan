import threading
import time
import unittest

import cv2
import numpy as np
import requests

from camera.esp32_camera import ESP32Camera, JpegParser
from config import CameraConfig


class Response:
    status_code = 200
    headers = {"Content-Type": "multipart/x-mixed-replace; boundary=frame"}

    def __init__(self, chunks):
        self.chunks = iter(chunks)
        self.closed = False
        self.raw = self

    def __enter__(self):
        return self

    def __exit__(self, *_):
        self.closed = True

    def raise_for_status(self):
        pass

    def read1(self, *_args, **_kwargs):
        return next(self.chunks, b'')


class Session:
    def __init__(self, outcomes):
        self.outcomes = iter(outcomes)
        self.calls = []

    def get(self, *args, **kwargs):
        self.calls.append(kwargs)
        outcome = next(self.outcomes)
        if isinstance(outcome, Exception):
            raise outcome
        return outcome

    def close(self):
        pass


class CameraTests(unittest.TestCase):
    def jpeg(self):
        ok, image = cv2.imencode('.jpg', np.zeros((24, 32, 3), np.uint8))
        self.assertTrue(ok)
        return image.tobytes()

    def test_split_markers_and_multiple_frames(self):
        parser = JpegParser(1024)
        self.assertEqual(parser.feed(b'headers\xff'), [])
        self.assertEqual(parser.feed(b'\xd8one\xff'), [])
        self.assertEqual(parser.feed(b'\xd9\r\n\xff\xd8two\xff\xd9'),
                         [b'\xff\xd8one\xff\xd9', b'\xff\xd8two\xff\xd9'])

    def test_noise_is_discarded_and_oversize_is_rejected(self):
        parser = JpegParser(1024)
        parser.feed(b'x' * 8192)
        self.assertLessEqual(len(parser.buffer), 1)
        with self.assertRaises(ValueError):
            parser.feed(b'\xff\xd8' + b'x' * 1024)

    def test_reconnects_after_connect_timeout_and_eof(self):
        jpeg = self.jpeg()
        first, second = Response([jpeg]), Response([jpeg])
        session = Session([requests.ConnectTimeout(), first, second])
        config = CameraConfig(reconnect_initial=0.001, reconnect_max=0.002)
        camera = ESP32Camera(config, session)
        stop = threading.Event()
        frames = camera.frames(stop)
        self.assertEqual(next(frames).shape, (24, 32, 3))
        self.assertEqual(next(frames).shape, (24, 32, 3))
        stop.set()
        frames.close()
        self.assertEqual(camera.failures, 2)
        self.assertEqual(camera.connections, 2)
        self.assertTrue(first.closed and second.closed)
        self.assertEqual(session.calls[0]['timeout'], (3.0, 3.0))
        self.assertFalse(session.calls[0]['allow_redirects'])

    def test_invalid_content_type_reconnects(self):
        bad = Response([])
        bad.headers = {'Content-Type': 'text/html'}
        camera = ESP32Camera(CameraConfig(reconnect_initial=.001), Session([bad, Response([self.jpeg()])]))
        frames = camera.frames()
        self.assertEqual(next(frames).shape, (24, 32, 3))
        frames.close()
        self.assertEqual(camera.failures, 1)
        self.assertTrue(bad.closed)

    def test_corrupt_jpeg_is_not_a_frame(self):
        response = Response([b'\xff\xd8bad\xff\xd9', self.jpeg()])
        camera = ESP32Camera(CameraConfig(), Session([response]))
        frames = camera.frames()
        next(frames)
        frames.close()
        self.assertEqual(camera.frames_received, 1)

    def test_deadline_and_stopped_reader_do_not_connect(self):
        session = Session([])
        camera = ESP32Camera(CameraConfig(), session)
        self.assertEqual(list(camera.frames(deadline=time.monotonic() - 1)), [])
        stop = threading.Event()
        stop.set()
        self.assertEqual(list(camera.frames(stop)), [])
        self.assertEqual(session.calls, [])

    def test_bad_configuration_is_rejected(self):
        for url in ['file:///etc/passwd', 'http://user:secret@example.org/stream', 'http://']:
            with self.assertRaises(ValueError):
                CameraConfig(url=url)
        with self.assertRaises(ValueError):
            CameraConfig(read_timeout=0)

    def test_stalled_invalid_stream_reconnects(self):
        class Stalled(Response):
            def read1(self, *_args, **_kwargs):
                time.sleep(.02)
                return b'not a jpeg'
        camera = ESP32Camera(CameraConfig(frame_timeout=.01, reconnect_initial=.001),
                             Session([Stalled([]), Response([self.jpeg()])]))
        frames = camera.frames()
        next(frames)
        frames.close()
        self.assertEqual(camera.failures, 1)


if __name__ == '__main__':
    unittest.main()
