<?php

namespace App\Console\Commands;

use App\Services\ExpirePendingBookingHolds as BookingHoldExpiry;
use Illuminate\Console\Command;

class ExpirePendingBookingHolds extends Command
{
    protected $signature = 'bookings:expire-pending';
    protected $description = 'Cancel unpaid pending bookings after the 10-minute hold expires';

    public function handle(BookingHoldExpiry $holds): int
    {
        $count = $holds->expireStale();
        $this->info("Đã tự hủy {$count} booking chờ thanh toán quá 10 phút.");

        return self::SUCCESS;
    }
}
