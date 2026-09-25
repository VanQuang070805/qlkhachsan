<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\ReportSnapshot;
use Illuminate\Console\Command;

class SnapshotHotelMetrics extends Command
{
    protected $signature = 'reports:snapshot {--date= : Ngày snapshot theo YYYY-MM-DD}';
    protected $description = 'Lưu snapshot KPI hằng ngày cho báo cáo Royal Hotel';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $metrics = (new Booking)->getReportStats(['start_date' => $date, 'end_date' => $date]);

        ReportSnapshot::query()->updateOrCreate(
            ['snapshot_date' => $date],
            ['metrics' => $metrics],
        );

        $this->info("Đã lưu snapshot KPI ngày {$date}.");

        return self::SUCCESS;
    }
}
