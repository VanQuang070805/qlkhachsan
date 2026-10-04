<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class ExpireNoShowBookings
{
    public function cancelIfDue(Booking|int $booking): bool
    {
        $bookingId = $booking instanceof Booking ? $booking->id : $booking;

        return DB::transaction(function () use ($bookingId): bool {
            $booking = Booking::query()->lockForUpdate()->find($bookingId);
            if (! $booking || $booking->status !== 'confirmed' || $booking->actual_check_in) {
                return false;
            }

            $now = now('Asia/Ho_Chi_Minh');
            $checkIn = $booking->check_in->toDateString();
            $isPastArrivalDate = $checkIn < $now->toDateString();
            $isPastArrivalWindow = $checkIn === $now->toDateString()
                && $now->greaterThanOrEqualTo($now->copy()->setTime(17, 0));

            if (! $isPastArrivalDate && ! $isPastArrivalWindow) {
                return false;
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => $now,
                'cancellation_reason' => 'Không đến nhận phòng trước 17:00.',
                'refund_status' => 'none',
                'refund_amount' => 0,
            ]);

            return true;
        });
    }

    public function cancelDueBookings(): int
    {
        $now = now('Asia/Ho_Chi_Minh');
        $lastDueDate = $now->hour >= 17
            ? $now->toDateString()
            : $now->copy()->subDay()->toDateString();
        $cancelledCount = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNull('actual_check_in')
            ->whereDate('check_in', '<=', $lastDueDate)
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$cancelledCount): void {
                foreach ($bookings as $booking) {
                    $cancelledCount += (int) $this->cancelIfDue($booking);
                }
            });

        return $cancelledCount;
    }
}
