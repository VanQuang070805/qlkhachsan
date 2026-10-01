"""Move the configured door servo once without starting camera recognition."""

import argparse

from app.door_servo import DoorServo


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--pin", type=int, default=18, help="BCM GPIO number")
    parser.add_argument("--closed-angle", type=float, default=0)
    parser.add_argument("--open-angle", type=float, default=180)
    parser.add_argument("--move-seconds", type=float, default=1.2)
    parser.add_argument("--hold-seconds", type=float, default=3)
    args = parser.parse_args()

    servo = DoorServo(
        pin=args.pin,
        closed_angle=args.closed_angle,
        open_angle=args.open_angle,
        move_seconds=args.move_seconds,
        hold_seconds=args.hold_seconds,
        cooldown_seconds=0,
        min_pulse_width=0.0005,
        max_pulse_width=0.0025,
        detach_after_move=True,
    )
    try:
        if not servo.unlock():
            return 1
        if servo.thread:
            servo.thread.join(timeout=args.hold_seconds + args.move_seconds * 2 + 2)
    finally:
        servo.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
