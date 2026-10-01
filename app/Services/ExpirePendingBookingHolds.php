<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\DB;

class ExpirePendingBookingHolds
{
    public function expireIfDue(Booking|int $booking): bool
    {
        $bookingId = $booking instanceof Booking ? $booking->id : $booking;

        return DB::transaction(function () use ($bookingId): bool {
            $booking = Booking::query()->lockForUpdate()->find($bookingId);
            if (!$booking) {
                return false;
            }

            $createdAt = $booking->getAttribute($booking->getCreatedAtColumn());

            if ($booking->status !== 'pending' || $booking->payment_status !== 'pending'
                || !$createdAt || $createdAt->gt(now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES))) {
                return false;
            }

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Hết thời hạn thanh toán 10 phút.',
                'refund_status' => 'none',
            ]);

            PaymentLog::query()
                ->where('booking_id', $booking->id)
                ->where('purpose', 'deposit')
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            return true;
        });
    }

    public function expireStale(): int
    {
        $expiredCount = 0;
        $createdAt = (new Booking)->getCreatedAtColumn();

        Booking::query()
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where($createdAt, '<=', now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES))
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$expiredCount): void {
                foreach ($bookings as $booking) {
                    $expiredCount += (int) $this->expireIfDue($booking);
                }
            });

        return $expiredCount;
    }
}
