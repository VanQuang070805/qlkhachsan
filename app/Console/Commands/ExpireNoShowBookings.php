<?php

namespace App\Console\Commands;

use App\Services\ExpireNoShowBookings as NoShowExpiry;
use Illuminate\Console\Command;

class ExpireNoShowBookings extends Command
{
    protected $signature = 'bookings:expire-no-shows';
    protected $description = 'Cancel confirmed bookings that miss the 17:00 check-in deadline';

    public function handle(NoShowExpiry $noShows): int
    {
        $count = $noShows->cancelDueBookings();
        $this->info("Đã tự hủy {$count} booking không đến nhận phòng trước 17:00.");

        return self::SUCCESS;
    }
}
