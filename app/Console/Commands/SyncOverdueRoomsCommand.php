<?php

namespace App\Console\Commands;

use App\Services\SyncOverdueRooms;
use Illuminate\Console\Command;

class SyncOverdueRoomsCommand extends Command
{
    protected $signature = 'bookings:sync-overdue';

    protected $description = 'Cập nhật phòng đang lưu trú sang trạng thái quá giờ trả (overdue) theo quy định vận hành';

    public function handle(SyncOverdueRooms $syncOverdueRooms): int
    {
        $updated = $syncOverdueRooms->sync();
        $this->info("Đã cập nhật {$updated} phòng sang trạng thái quá giờ trả.");

        return self::SUCCESS;
    }
}
