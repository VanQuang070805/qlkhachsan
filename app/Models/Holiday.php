<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'recurring'];

    protected $casts = [
        'date'      => 'date',
        'recurring' => 'boolean',
    ];

    /**
     * Kiểm tra một ngày cụ thể có phải ngày lễ không.
     * Với ngày lễ recurring=true → so sánh tháng+ngày (bất kể năm)
     * Với recurring=false → so sánh ngày chính xác
     */
    public static function isHoliday(Carbon $date): bool
    {
        $dateString = $date->toDateString();
        $monthDay = $date->format('m-d');
        $monthDayExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%m-%d', date)"
            : "DATE_FORMAT(date, '%m-%d')";

        $inHolidayTable = self::where(function ($q) use ($monthDay, $monthDayExpression) {
            $q->where('recurring', true)
              ->whereRaw("{$monthDayExpression} = ?", [$monthDay]);
        })->orWhere(function ($q) use ($dateString) {
            $q->where('recurring', false)
              ->whereDate('date', $dateString);
        })->exists();

        if ($inHolidayTable) {
            return true;
        }

        return PriceSetting::query()
            ->where('status', 1)
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();
    }
}
