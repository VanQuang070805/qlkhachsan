import json
import os
import sys
import unittest
from unittest.mock import patch


sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from cleaning_switch import CleaningApiClient, CleaningSwitchConfig


class FakeResponse:
    def __init__(self, payload):
        self.payload = json.dumps(payload).encode("utf-8")

    def __enter__(self):
        return self

    def __exit__(self, *_args):
        return False

    def read(self):
        return self.payload


class CleaningSwitchTests(unittest.TestCase):
    def test_configuration_uses_bcm_gpio17_and_room_501_by_default(self):
        with patch.dict(os.environ, {
            "HOTEL_API_BASE_URL": "http://192.168.1.10:8000/",
            "IOT_DEVICE_API_KEY": "secret",
        }, clear=True):
            config = CleaningSwitchConfig.from_env()

        self.assertEqual(config.api_base_url, "http://192.168.1.10:8000")
        self.assertEqual(config.room_number, "501")
        self.assertEqual(config.gpio, 17)

    def test_client_posts_cleaning_state_with_api_key(self):
        captured = {}

        def opener(api_request, timeout):
            captured["url"] = api_request.full_url
            captured["headers"] = dict(api_request.header_items())
            captured["body"] = json.loads(api_request.data.decode("utf-8"))
            captured["timeout"] = timeout
            return FakeResponse({
                "success": True,
                "room_number": "501",
                "message": "ok",
            })

        config = CleaningSwitchConfig(
            api_base_url="http://192.168.1.10:8000",
            api_key="secret",
        )
        result = CleaningApiClient(config, opener=opener).send(True)

        self.assertEqual(captured["url"], "http://192.168.1.10:8000/api/iot/rooms/501/cleaning-request")
        self.assertEqual(captured["headers"]["X-api-key"], "secret")
        self.assertEqual(captured["body"], {"needs_cleaning": True})
        self.assertEqual(result["room_number"], "501")

    def test_missing_device_key_is_rejected(self):
        with patch.dict(os.environ, {
            "HOTEL_API_BASE_URL": "http://192.168.1.10:8000",
        }, clear=True):
            with self.assertRaisesRegex(ValueError, "IOT_DEVICE_API_KEY"):
                CleaningSwitchConfig.from_env()


if __name__ == "__main__":
    unittest.main()
