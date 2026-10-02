from __future__ import annotations

import json
import logging
import threading
from urllib import error, parse, request


LOGGER = logging.getLogger("hotel-face-pi.cleaning")


class CleaningApiClient:
    def __init__(
        self,
        base_url: str,
        api_key: str,
        room_number: str,
        timeout: float,
        opener=None,
    ) -> None:
        self.base_url = base_url.rstrip("/")
        self.api_key = api_key
        self.room_number = room_number
        self.timeout = timeout
        self.opener = opener or request.urlopen

    def send(self, needs_cleaning: bool) -> dict:
        room = parse.quote(self.room_number, safe="")
        api_request = request.Request(
            f"{self.base_url}/api/iot/rooms/{room}/cleaning-request",
            data=json.dumps({"needs_cleaning": bool(needs_cleaning)}).encode("utf-8"),
            method="POST",
            headers={
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-API-Key": self.api_key,
            },
        )
        try:
            with self.opener(api_request, timeout=self.timeout) as response:
                result = json.loads(response.read().decode("utf-8"))
        except error.HTTPError as exc:
            message = exc.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"Laravel returned HTTP {exc.code}: {message}") from exc
        except error.URLError as exc:
            raise RuntimeError(f"Cannot reach Laravel: {exc.reason}") from exc

        if not result.get("success"):
            raise RuntimeError(result.get("message", "Laravel rejected the cleaning state"))
        return result


class CleaningSwitchService:
    """Read the room switch and retry its latest state until Laravel accepts it."""

    def __init__(
        self,
        client: CleaningApiClient,
        gpio: int,
        bounce_time: float,
        retry_seconds: float,
        button_factory=None,
    ) -> None:
        self.client = client
        self.gpio = gpio
        self.bounce_time = bounce_time
        self.retry_seconds = retry_seconds
        self.button_factory = button_factory
        self.condition = threading.Condition()
        self.desired: bool | None = None
        self.version = 0
        self.sent_version = -1
        self.stopped = False
        self.last_error: str | None = None
        self.button = None
        self.thread: threading.Thread | None = None

    @property
    def running(self) -> bool:
        return bool(self.thread and self.thread.is_alive())

    def start(self) -> None:
        if self.running:
            return
        if self.button_factory is None:
            try:
                from gpiozero import Button
            except ImportError as exc:
                raise RuntimeError("gpiozero is required for the cleaning switch") from exc
            self.button_factory = Button

        self.button = self.button_factory(
            self.gpio,
            pull_up=True,
            bounce_time=self.bounce_time,
        )
        self.button.when_pressed = lambda: self.update(True)
        self.button.when_released = lambda: self.update(False)
        self.stopped = False
        self.thread = threading.Thread(
            target=self._run,
            name="cleaning-state-reporter",
            daemon=True,
        )
        self.thread.start()
        self.update(bool(self.button.is_pressed))
        LOGGER.info(
            "Cleaning switch ready on BCM GPIO%d; initial state=%s",
            self.gpio,
            self.button.is_pressed,
        )

    def update(self, needs_cleaning: bool) -> None:
        with self.condition:
            self.desired = bool(needs_cleaning)
            self.version += 1
            self.condition.notify_all()

    def _run(self) -> None:
        while True:
            with self.condition:
                self.condition.wait_for(
                    lambda: self.stopped or (
                        self.desired is not None and self.sent_version != self.version
                    )
                )
                if self.stopped:
                    return
                state = bool(self.desired)
                version = self.version

            try:
                result = self.client.send(state)
                self.last_error = None
                LOGGER.info(
                    "Room %s cleaning=%s: %s",
                    result["room_number"],
                    state,
                    result["message"],
                )
                with self.condition:
                    if version == self.version:
                        self.sent_version = version
            except Exception as exc:
                self.last_error = str(exc)
                LOGGER.warning(
                    "Cannot send cleaning state (%s); retry in %.1fs",
                    exc,
                    self.retry_seconds,
                )
                with self.condition:
                    self.condition.wait(timeout=self.retry_seconds)

    def stop(self) -> None:
        with self.condition:
            self.stopped = True
            self.condition.notify_all()
        if self.thread:
            self.thread.join(timeout=self.client.timeout + 1)
        if self.button:
            self.button.close()

    def health(self) -> dict:
        return {
            "enabled": True,
            "running": self.running,
            "desired": self.desired,
            "last_error": self.last_error,
        }
