from app.door_servo import DoorServo


class FakeServo:
    def __init__(self):
        self.angles = []
        self.closed = False

    @property
    def angle(self):
        return self.angles[-1] if self.angles else None

    @angle.setter
    def angle(self, value):
        self.angles.append(value)

    def close(self):
        self.closed = True


class Clock:
    def __init__(self):
        self.value = 100.0

    def monotonic(self):
        return self.value


def test_unlock_turns_180_degrees_then_returns_and_detaches():
    device = FakeServo()
    clock = Clock()
    servo = DoorServo(
        pin=18,
        closed_angle=0,
        open_angle=180,
        move_seconds=0,
        hold_seconds=0,
        cooldown_seconds=8,
        min_pulse_width=0.0005,
        max_pulse_width=0.0025,
        detach_after_move=True,
        device=device,
        sleep=lambda _seconds: None,
        monotonic=clock.monotonic,
    )

    assert servo.unlock() is True
    servo.thread.join(timeout=1)

    assert device.angles[:5] == [0, 180, None, 0, None]
    servo.close()
    assert device.closed is True


def test_unlock_respects_cooldown():
    device = FakeServo()
    clock = Clock()
    servo = DoorServo(
        pin=18,
        closed_angle=0,
        open_angle=180,
        move_seconds=0,
        hold_seconds=0,
        cooldown_seconds=8,
        min_pulse_width=0.0005,
        max_pulse_width=0.0025,
        detach_after_move=False,
        device=device,
        sleep=lambda _seconds: None,
        monotonic=clock.monotonic,
    )

    assert servo.unlock() is True
    servo.thread.join(timeout=1)
    assert servo.unlock() is False
    clock.value += 8
    assert servo.unlock() is True
    servo.thread.join(timeout=1)
    servo.close()


def test_sweeps_smoothly_and_detaches_pwm_while_door_stays_open():
    device = FakeServo()
    sleeps = []
    servo = DoorServo(
        pin=18,
        closed_angle=0,
        open_angle=180,
        move_seconds=0.06,
        hold_seconds=3,
        cooldown_seconds=8,
        min_pulse_width=0.0005,
        max_pulse_width=0.0025,
        detach_after_move=True,
        device=device,
        sleep=sleeps.append,
    )

    assert servo.unlock() is True
    servo.thread.join(timeout=1)

    assert device.angles[:9] == [0, 60, 120, 180, None, 120, 60, 0, None]
    assert sleeps[:7] == [0.02, 0.02, 0.02, 3, 0.02, 0.02, 0.02]
