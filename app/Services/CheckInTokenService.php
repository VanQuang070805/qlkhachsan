<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;
use App\Models\Booking;
use Carbon\Carbon;

class CheckInTokenService
{
    public const QR_PREFIX = 'ROYAL-CHECKIN:';
    public const ROSALIZA_QR_PREFIX = 'ROSALIZA-CHECKIN:';
    public const LEGACY_QR_PREFIX = 'POSH-CHECKIN:';

    public function qrPayload(Booking $booking): string
    {
        $booking->loadMissing('rooms.roomType');

        $roomNumbers = $booking->rooms
            ->pluck('room_number')
            ->filter()
            ->unique()
            ->values()
            ->implode(',');

        $roomTypes = $booking->rooms
            ->map(fn ($room) => $room->roomType?->type_name)
            ->filter()
            ->unique()
            ->map(fn ($name) => Str::limit(Str::ascii((string) $name), 36, ''))
            ->values()
            ->implode(',');

        $checkIn = $booking->check_in ? Carbon::parse($booking->check_in)->format('Y-m-d') : '';
        $checkOut = $booking->check_out ? Carbon::parse($booking->check_out)->format('Y-m-d') : '';

        $segments = [
            self::QR_PREFIX.$this->issue($booking),
            'HOTEL:Rosaliza Hotel',
            'BOOKING:#'.$booking->id,
            'ROOMS:'.($roomNumbers !== '' ? $roomNumbers : 'TBA'),
        ];

        if ($roomTypes !== '') {
            $segments[] = 'TYPE:'.$roomTypes;
        }
        if ($checkIn !== '') {
            $segments[] = 'IN:'.$checkIn.' 14:00';
        }
        if ($checkOut !== '') {
            $segments[] = 'OUT:'.$checkOut.' 12:00';
        }

        return implode('|', $segments);
    }

    public function issue(Booking $booking): string
    {
        $nonce = Str::random(32);
        $expiresAt = Carbon::parse($booking->check_out->toDateString(), 'Asia/Ho_Chi_Minh')->endOfDay();
        Cache::put('checkin-token:'.$nonce, [
            'booking_id' => (int) $booking->id,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        return 'v1:'.$booking->id.':'.$nonce;
    }

    public function tokenFromQrPayload(string $payload): string
    {
        $payload = trim($payload);
        foreach ([self::QR_PREFIX, self::ROSALIZA_QR_PREFIX, 'ROSA-CHECKIN:', self::LEGACY_QR_PREFIX] as $prefix) {
            if (str_starts_with($payload, $prefix)) {
                $rawToken = substr($payload, strlen($prefix));
                $token = trim(explode('|', $rawToken, 2)[0]);
                if ($token !== '') {
                    return $token;
                }
            }
        }

        throw new RuntimeException('Mã QR không đúng định dạng nhận phòng Rosaliza Hotel.');
    }

    public function bookingId(string $token): int
    {
        $cleanToken = trim(explode('|', trim($token), 2)[0]);
        if (preg_match('/^v1:(\d+):([A-Za-z0-9]{32})$/', $cleanToken, $matches) === 1) {
            $bookingId = (int) $matches[1];
            $cached = Cache::get('checkin-token:'.$matches[2]);
            if (! is_array($cached)
                || (int) ($cached['booking_id'] ?? 0) !== $bookingId
                || (int) ($cached['expires_at'] ?? 0) < now()->timestamp) {
                throw new RuntimeException('Mã nhận phòng đã hết hạn hoặc đã được sử dụng.');
            }

            return $bookingId;
        }

        return $this->legacyBookingId($cleanToken);
    }

    private function legacyBookingId(string $token): int
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Mã nhận phòng không hợp lệ.');
        }

        if (($payload['purpose'] ?? null) !== 'check-in' || (int) ($payload['expires_at'] ?? 0) < now()->timestamp
            || !Cache::has('checkin-token:'.($payload['nonce'] ?? ''))) {
            throw new RuntimeException('Mã nhận phòng đã hết hạn hoặc đã được sử dụng.');
        }

        return (int) ($payload['booking_id'] ?? 0);
    }

    public function consume(string $token): void
    {
        $cleanToken = trim(explode('|', trim($token), 2)[0]);
        if (preg_match('/^v1:\d+:([A-Za-z0-9]{32})$/', $cleanToken, $matches) === 1) {
            Cache::forget('checkin-token:'.$matches[1]);
            return;
        }

        try {
            $payload = json_decode(Crypt::decryptString($cleanToken), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Mã nhận phòng không hợp lệ.');
        }

        Cache::forget('checkin-token:'.($payload['nonce'] ?? ''));
    }
}
