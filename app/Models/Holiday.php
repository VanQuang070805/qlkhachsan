<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
    $monthDay = $date->format('m-d');
    
    return self::where(function ($q) use ($date, $monthDay) {
        $q->where('recurring', true)
          ->whereRaw("DATE_FORMAT(date, '%m-%d') = ?", [$monthDay]);
    })->orWhere(function ($q) use ($date) {
        $q->where('recurring', false)
          ->whereDate('date', $date->toDateString());
    })->exists();
    }
}
