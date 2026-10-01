"""Report the room 501 cleaning rocker switch to the Laravel receptionist app."""

import json
import logging
import os
import signal
import threading
from dataclasses import dataclass
from urllib import error, parse, request


LOG = logging.getLogger(__name__)


@dataclass(frozen=True)
class CleaningSwitchConfig:
    api_base_url: str
    api_key: str
    room_number: str = "501"
    gpio: int = 17
    bounce_time: float = 0.08
    request_timeout: float = 5.0
    retry_seconds: float = 3.0

    @classmethod
    def from_env(cls):
        config = cls(
            api_base_url=os.getenv("HOTEL_API_BASE_URL", "").rstrip("/"),
            api_key=os.getenv("IOT_DEVICE_API_KEY", ""),
            room_number=os.getenv("IOT_CLEANING_ROOM_NUMBER", "501"),
            gpio=int(os.getenv("IOT_CLEANING_SWITCH_GPIO", "17")),
            bounce_time=float(os.getenv("IOT_SWITCH_BOUNCE_TIME", "0.08")),
            request_timeout=float(os.getenv("IOT_REQUEST_TIMEOUT", "5")),
            retry_seconds=float(os.getenv("IOT_RETRY_SECONDS", "3")),
        )
        config.validate()
        return config

    def validate(self):
        if not self.api_base_url.startswith(("http://", "https://")):
            raise ValueError("HOTEL_API_BASE_URL must start with http:// or https://")
        if not self.api_key:
            raise ValueError("IOT_DEVICE_API_KEY is required")
        if not self.room_number:
            raise ValueError("IOT_CLEANING_ROOM_NUMBER is required")
        if not 0 <= self.gpio <= 27:
            raise ValueError("IOT_CLEANING_SWITCH_GPIO must be a BCM GPIO number from 0 to 27")
        if not 0 <= self.bounce_time <= 2:
            raise ValueError("IOT_SWITCH_BOUNCE_TIME must be between 0 and 2 seconds")
        if self.request_timeout <= 0 or self.retry_seconds <= 0:
            raise ValueError("IOT request timeout and retry interval must be positive")


class CleaningApiClient:
    def __init__(self, config, opener=None):
        self.config = config
        self.opener = opener or request.urlopen

    def send(self, needs_cleaning):
        room = parse.quote(self.config.room_number, safe="")
        url = f"{self.config.api_base_url}/api/iot/rooms/{room}/cleaning-request"
        payload = json.dumps({"needs_cleaning": bool(needs_cleaning)}).encode("utf-8")
        api_request = request.Request(
            url,
            data=payload,
            method="POST",
            headers={
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-API-Key": self.config.api_key,
            },
        )
        try:
            with self.opener(api_request, timeout=self.config.request_timeout) as response:
                result = json.loads(response.read().decode("utf-8"))
        except error.HTTPError as exc:
            message = exc.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"Laravel returned HTTP {exc.code}: {message}") from exc
        except error.URLError as exc:
            raise RuntimeError(f"Cannot reach Laravel: {exc.reason}") from exc

        if not result.get("success"):
            raise RuntimeError(result.get("message", "Laravel rejected the cleaning state"))
        return result


class StateReporter:
    """Keep retrying the latest switch state until the laptop accepts it."""

    def __init__(self, client, retry_seconds):
        self.client = client
        self.retry_seconds = retry_seconds
        self.condition = threading.Condition()
        self.desired = None
        self.version = 0
        self.sent_version = -1
        self.stopped = False

    def update(self, needs_cleaning):
        with self.condition:
            self.desired = bool(needs_cleaning)
            self.version += 1
            self.condition.notify_all()

    def run(self):
        while True:
            with self.condition:
                self.condition.wait_for(
                    lambda: self.stopped or (
                        self.desired is not None and self.sent_version != self.version
                    )
                )
                if self.stopped:
                    return
                state = self.desired
                version = self.version

            try:
                result = self.client.send(state)
                LOG.info("Room %s cleaning=%s: %s", result["room_number"], state, result["message"])
                with self.condition:
                    if version == self.version:
                        self.sent_version = version
            except Exception as exc:
                LOG.warning("Cannot send cleaning state (%s); retry in %.1fs", exc, self.retry_seconds)
                with self.condition:
                    self.condition.wait(timeout=self.retry_seconds)

    def stop(self):
        with self.condition:
            self.stopped = True
            self.condition.notify_all()


def main():
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    try:
        from gpiozero import Button
    except ImportError as exc:
        raise SystemExit("gpiozero is missing; run: sudo apt install python3-gpiozero") from exc

    try:
        config = CleaningSwitchConfig.from_env()
    except ValueError as exc:
        raise SystemExit(str(exc)) from exc

    switch = Button(config.gpio, pull_up=True, bounce_time=config.bounce_time)
    reporter = StateReporter(CleaningApiClient(config), config.retry_seconds)
    worker = threading.Thread(target=reporter.run, name="cleaning-state-reporter", daemon=True)
    stop = threading.Event()

    switch.when_pressed = lambda: reporter.update(True)
    switch.when_released = lambda: reporter.update(False)
    for signum in (signal.SIGINT, signal.SIGTERM):
        signal.signal(signum, lambda *_: stop.set())

    worker.start()
    reporter.update(switch.is_pressed)
    LOG.info(
        "Watching BCM GPIO%d (physical pin 11 when GPIO17), room %s; initial state=%s",
        config.gpio,
        config.room_number,
        switch.is_pressed,
    )

    stop.wait()
    reporter.stop()
    worker.join(timeout=config.request_timeout + 1)
    switch.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
