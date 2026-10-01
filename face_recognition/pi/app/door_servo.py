from __future__ import annotations

import logging
import threading
import time
from typing import Callable


LOGGER = logging.getLogger("hotel-face-pi.servo")


class DoorServo:
    def __init__(
        self,
        pin: int,
        closed_angle: float,
        open_angle: float,
        move_seconds: float,
        hold_seconds: float,
        cooldown_seconds: float,
        min_pulse_width: float,
        max_pulse_width: float,
        detach_after_move: bool,
        device=None,
        sleep: Callable[[float], None] = time.sleep,
        monotonic: Callable[[], float] = time.monotonic,
    ) -> None:
        self.closed_angle = closed_angle
        self.open_angle = open_angle
        self.move_seconds = move_seconds
        self.hold_seconds = hold_seconds
        self.cooldown_seconds = cooldown_seconds
        self.detach_after_move = detach_after_move
        self.sleep = sleep
        self.monotonic = monotonic
        self.lock = threading.Lock()
        self.thread: threading.Thread | None = None
        self.last_activation = float("-inf")

        if device is None:
            try:
                from gpiozero import AngularServo
            except ImportError as error:
                raise RuntimeError(
                    "gpiozero is required for servo control. Install python3-gpiozero."
                ) from error
            device = AngularServo(
                pin,
                min_angle=0,
                max_angle=180,
                min_pulse_width=min_pulse_width,
                max_pulse_width=max_pulse_width,
            )
        self.device = device
        self.device.angle = self.closed_angle
        self.current_angle = self.closed_angle

    def unlock(self) -> bool:
        with self.lock:
            now = self.monotonic()
            if self.thread and self.thread.is_alive():
                return False
            if now - self.last_activation < self.cooldown_seconds:
                return False
            self.last_activation = now
            self.thread = threading.Thread(
                target=self._open_then_close,
                name="door-servo",
                daemon=True,
            )
            self.thread.start()
            return True

    def _open_then_close(self) -> None:
        try:
            LOGGER.info("Door servo opening to %.1f degrees", self.open_angle)
            self._move_smoothly(self.open_angle)
            if self.detach_after_move:
                # Stop PWM while holding the open position. This avoids the
                # servo hunting around the target angle under CPU load.
                self.device.angle = None
            self.sleep(self.hold_seconds)
            self._move_smoothly(self.closed_angle)
            LOGGER.info("Door servo returned to %.1f degrees", self.closed_angle)
            if self.detach_after_move:
                self.sleep(0.4)
                self.device.angle = None
        except Exception:
            LOGGER.exception("Door servo movement failed")

    def _move_smoothly(self, target_angle: float) -> None:
        start_angle = self.current_angle
        if target_angle == start_angle:
            self.device.angle = target_angle
            return

        # Servo control pulses repeat every ~20 ms. Updating once per pulse
        # produces a smooth sweep without flooding the GPIO backend.
        steps = max(1, round(self.move_seconds / 0.02))
        delay = self.move_seconds / steps
        distance = target_angle - start_angle
        for step in range(1, steps + 1):
            angle = start_angle + distance * step / steps
            self.device.angle = angle
            self.current_angle = angle
            self.sleep(delay)

    def close(self) -> None:
        thread = self.thread
        if thread and thread.is_alive():
            thread.join(timeout=self.hold_seconds + self.move_seconds * 2 + 2)
        try:
            self._move_smoothly(self.closed_angle)
            self.sleep(0.4)
            if self.detach_after_move:
                self.device.angle = None
        finally:
            self.device.close()
