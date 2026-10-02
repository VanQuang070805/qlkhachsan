import json
import time

from app.cleaning_switch import CleaningApiClient, CleaningSwitchService


class FakeResponse:
    def __init__(self, payload):
        self.payload = json.dumps(payload).encode("utf-8")

    def __enter__(self):
        return self

    def __exit__(self, *_args):
        return False

    def read(self):
        return self.payload


class FakeButton:
    def __init__(self, gpio, pull_up, bounce_time):
        self.gpio = gpio
        self.pull_up = pull_up
        self.bounce_time = bounce_time
        self.is_pressed = False
        self.when_pressed = None
        self.when_released = None
        self.closed = False

    def close(self):
        self.closed = True


def test_client_posts_cleaning_state_to_laravel():
    captured = {}

    def opener(api_request, timeout):
        captured["url"] = api_request.full_url
        captured["headers"] = dict(api_request.header_items())
        captured["body"] = json.loads(api_request.data.decode("utf-8"))
        captured["timeout"] = timeout
        return FakeResponse({"success": True, "room_number": "501", "message": "ok"})

    client = CleaningApiClient(
        base_url="http://172.20.10.4:8000/",
        api_key="device-secret",
        room_number="501",
        timeout=5,
        opener=opener,
    )

    result = client.send(True)

    assert captured["url"] == "http://172.20.10.4:8000/api/iot/rooms/501/cleaning-request"
    assert captured["headers"]["X-api-key"] == "device-secret"
    assert captured["body"] == {"needs_cleaning": True}
    assert result["room_number"] == "501"


def test_service_reports_initial_switch_state_and_stops():
    sent = []

    class Client:
        timeout = 0.1

        def send(self, state):
            sent.append(state)
            return {"room_number": "501", "message": "ok"}

    service = CleaningSwitchService(
        client=Client(),
        gpio=17,
        bounce_time=0.15,
        retry_seconds=0.01,
        button_factory=FakeButton,
    )
    service.start()
    deadline = time.monotonic() + 1
    while not sent and time.monotonic() < deadline:
        time.sleep(0.01)

    assert sent == [False]
    assert service.health()["running"] is True
    service.stop()
    assert service.button.closed is True
