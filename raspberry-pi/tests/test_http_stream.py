"""Real HTTP integration: simulated MJPEG source disconnects then reconnects."""
import threading
import time
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import cv2
import numpy as np

from camera.esp32_camera import ESP32Camera
from config import CameraConfig


class HttpStreamTests(unittest.TestCase):
    def test_real_http_decoding_and_reconnect(self):
        _, encoded = cv2.imencode('.jpg', np.zeros((240, 320, 3), np.uint8))
        jpeg = encoded.tobytes()

        class Handler(BaseHTTPRequestHandler):
            def do_GET(self):
                self.send_response(200)
                self.send_header('Content-Type', 'multipart/x-mixed-replace; boundary=frame')
                self.end_headers()
                self.wfile.write(b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + jpeg + b'\r\n')
                self.wfile.flush()

            def log_message(self, *_):
                pass

        server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        config = CameraConfig(url=f'http://127.0.0.1:{server.server_port}/stream', reconnect_initial=.01)
        camera = ESP32Camera(config)
        frames = camera.frames(deadline=time.monotonic() + 5)
        try:
            for _ in range(3):
                self.assertEqual(next(frames).shape, (240, 320, 3))
            self.assertEqual(camera.connections, 3)
            self.assertEqual(camera.failures, 2)
        finally:
            frames.close()
            camera.close()
            server.shutdown()
            server.server_close()
            thread.join(timeout=2)
