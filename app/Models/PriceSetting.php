<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSetting extends Model
{
    protected $table = 'price_settings';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'adjustment_type',
        'adjustment_value',
        'status',
    ];

    protected $casts = [
        'status'           => 'boolean',
        'adjustment_value' => 'float',
    ];

    /**
     * Kiểm tra trùng lặp khoảng thời gian sự kiện.
     */
    public function checkOverlap(string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = self::where('status', 1)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($sub) use ($startDate) {
                    $sub->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $startDate);
                })
                ->orWhere(function ($sub) use ($endDate) {
                    $sub->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $endDate);
                })
                ->orWhere(function ($sub) use ($startDate, $endDate) {
                    $sub->where('start_date', '>=', $startDate)
                        ->where('end_date', '<=', $endDate);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Tính toán tổng giá phòng cho một khoảng ngày lưu trú dựa trên các dịp lễ tết kích hoạt.
     */
    public static function calculateTotalPrice(float $basePrice, string $checkIn, string $checkOut): float
    {
        $priceSettings = self::where('status', 1)->get();
        $totalPrice = 0;
        
        $startDate = new \DateTime($checkIn);
        $endDate = new \DateTime($checkOut);
        
        $currentDate = clone $startDate;
        
        // Loop qua từng đêm (trừ ngày checkout)
        while ($currentDate < $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $nightPrice = $basePrice;
            
            foreach ($priceSettings as $setting) {
                // Chuyển đổi start_date và end_date về chuỗi Y-m-d để so sánh chính xác
                $sd = $setting->start_date instanceof \DateTimeInterface ? $setting->start_date->format('Y-m-d') : $setting->start_date;
                $ed = $setting->end_date instanceof \DateTimeInterface ? $setting->end_date->format('Y-m-d') : $setting->end_date;
                
                if ($dateString >= $sd && $dateString <= $ed) {
                    if ($setting->adjustment_type === 'percent') {
                        $nightPrice += $basePrice * ($setting->adjustment_value / 100);
                    } else {
                        $nightPrice += $setting->adjustment_value;
                    }
                    break; // Chỉ áp dụng 1 mức điều chỉnh
                }
            }
            $totalPrice += $nightPrice;
            $currentDate->modify('+1 day');
        }
        
        return $totalPrice;
    }
}
