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
    public function issue(Booking $booking): string
    {
        $nonce = Str::random(40);
        $expiresAt = Carbon::parse($booking->check_out->toDateString(), 'Asia/Ho_Chi_Minh')->endOfDay();
        Cache::put('checkin-token:'.$nonce, true, $expiresAt);

        return Crypt::encryptString(json_encode([
            'booking_id' => $booking->id,
            'purpose' => 'check-in',
            'nonce' => $nonce,
            'expires_at' => $expiresAt->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    public function bookingId(string $token): int
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
        $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        Cache::forget('checkin-token:'.$payload['nonce']);
    }
}
