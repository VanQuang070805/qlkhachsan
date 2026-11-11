<?php

namespace App\Support;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

class StayDates
{
    public static function validate(string $checkIn, string $checkOut): void
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $checkIn);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOut);

        if (! $start || $start->format('Y-m-d') !== $checkIn || ! $end || $end->format('Y-m-d') !== $checkOut) {
            throw ValidationException::withMessages(['check_in' => 'Ngày nhận và trả phòng không hợp lệ.']);
        }

        $today = now('Asia/Ho_Chi_Minh')->startOfDay()->toDateTimeImmutable();
        if ($start < $today || $start > $today->modify('+365 days')) {
            throw ValidationException::withMessages(['check_in' => 'Chỉ có thể đặt phòng trong 365 ngày tới.']);
        }

        if ($end <= $start || $start->diff($end)->days > 30) {
            throw ValidationException::withMessages(['check_out' => 'Thời gian lưu trú phải từ 1 đến 30 đêm.']);
        }
    }
}
