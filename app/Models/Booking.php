<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    public static function boot()
    {
        parent::boot();

        static::updated(function ($booking) {
            if ($booking->wasChanged('status') && $booking->status === 'completed') {
                try {
                    $booking->loadMissing('rooms.roomType');
                    $roomTypes = $booking->rooms->map(fn($r) => $r->roomType)->unique('id');

                    if ($roomTypes->isNotEmpty() && $booking->customer_email) {
                        \Illuminate\Support\Facades\Mail::send('emails.review_request', compact('booking', 'roomTypes'), function ($message) use ($booking) {
                            $message->to($booking->customer_email)
                                    ->subject('Cảm ơn quý khách và Đánh giá phòng tại Royal Hotel');
                        });
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gửi email đánh giá thất bại cho booking #{$booking->id}: " . $e->getMessage());
                }
            }
        });
    }

    public function getCreatedAtColumn()
    {
        static $column = null;
        if ($column === null) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'booking_date')) {
                    $column = 'booking_date';
                } else {
                    $column = 'created_at';
                }
            } catch (\Throwable $e) {
                $column = 'created_at';
            }
        }
        return $column;
    }

    public function getUpdatedAtColumn()
    {
        static $column = null;
        if ($column === null) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'updated_at')) {
                    $column = 'updated_at';
                } else {
                    $column = null;
                }
            } catch (\Throwable $e) {
                $column = null;
            }
        }
        return $column;
    }
    
    protected $fillable = [
        'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'check_in', 'check_out', 'actual_check_in', 'actual_check_out',
        'adult_count', 'child_count', 'total_price',
        'payment_method', 'payment_status', 'status', 'late_checkout_fee',
        'deposit_amount', 'waive_late_fee',
        'cancelled_at', 'cancellation_reason',
        'refund_status', 'refund_amount',
    ];

    protected $casts = [
        'check_in'        => 'date',
        'check_out'       => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out'=> 'datetime',
        'cancelled_at'    => 'datetime',
        'total_price'     => 'decimal:2',
        'late_checkout_fee'  => 'decimal:2',
        'refund_amount'   => 'decimal:2',
        'deposit_amount'  => 'decimal:2',
        'waive_late_fee'  => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'booking_rooms');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }

    // ── Status Helpers ─────────────────────────────────────
    public function isPaid(): bool      { return $this->payment_status === 'paid'; }
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }

    // ── Refund Rule ────────────────────────────────────────
    /**
     * Tính số ngày tối thiểu cần hủy trước để được hoàn tiền.
     * - Check-in T2→T6: cần hủy trước 5 ngày
     * - Check-in T7, CN, hoặc ngày lễ: cần hủy trước 3 ngày
     */
    public function refundDeadlineDays(): int
    {
        $checkIn    = Carbon::parse($this->check_in);
        $isWeekend  = in_array($checkIn->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
        $isHoliday  = Holiday::isHoliday($checkIn);

        return ($isWeekend || $isHoliday) ? 7 : 5;
    }

    /**
     * Kiểm tra booking này còn trong thời hạn hoàn tiền không.
     */
    public function isRefundEligible(): bool
    {
        if (!$this->isPaid()) return false;

        $deadline = Carbon::parse($this->check_in)
            ->subDays($this->refundDeadlineDays())
            ->startOfDay();

        return Carbon::now()->lessThanOrEqualTo($deadline);
    }

    /**
     * Số ngày còn lại để được hoàn tiền (âm = đã quá hạn).
     */
    public function daysUntilRefundDeadline(): int
    {
        $deadline = Carbon::parse($this->check_in)->subDays($this->refundDeadlineDays());
        return (int) Carbon::now()->diffInDays($deadline, false);
    }

    // --- BÁO CÁO NÂNG CAO ---

    private function buildReportConditions(array $filters): array
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['start_date'])) {
            $where[] = "b.check_in >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "b.check_in <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['room_type_id'])) {
            $where[] = "b.id IN (SELECT br.booking_id FROM booking_rooms br JOIN rooms r ON br.room_id = r.id WHERE r.room_type_id = ?)";
            $params[] = $filters['room_type_id'];
        }

        $whereSql = implode(' AND ', $where);
        return [$whereSql, $params];
    }

    public function getReportStats(array $filters): array
    {
        list($whereSql, $params) = $this->buildReportConditions($filters);

        $sql = "SELECT 
                    SUM(CASE WHEN b.payment_status = 'paid' AND b.status != 'cancelled' THEN b.total_price ELSE 0 END) AS total_revenue,
                    COUNT(*) AS total_bookings,
                    SUM(DATEDIFF(b.check_out, b.check_in)) AS total_nights,
                    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
                    SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
                FROM bookings b
                WHERE $whereSql";
        
        $r = (array) \Illuminate\Support\Facades\DB::selectOne($sql, $params);
        
        $totalBookings = (int)($r['total_bookings'] ?? 0);
        $totalRevenue = (float)($r['total_revenue'] ?? 0);
        $totalNights = (int)($r['total_nights'] ?? 0);
        $cancelledCount = (int)($r['cancelled_count'] ?? 0);
        
        $avgRevenue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
        $cancelRate = $totalBookings > 0 ? ($cancelledCount / $totalBookings) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_bookings' => $totalBookings,
            'total_nights' => $totalNights,
            'pending_count' => (int)($r['pending_count'] ?? 0),
            'confirmed_count' => (int)($r['confirmed_count'] ?? 0),
            'cancelled_count' => $cancelledCount,
            'avg_revenue' => $avgRevenue,
            'cancel_rate' => $cancelRate
        ];
    }

    public function getReportChartData(array $filters): array
    {
        list($whereSql, $params) = $this->buildReportConditions($filters);
        
        $sql = "SELECT b.check_in AS date, SUM(b.total_price) AS revenue
                FROM bookings b
                WHERE $whereSql AND b.payment_status = 'paid' AND b.status != 'cancelled'
                GROUP BY b.check_in
                ORDER BY b.check_in ASC";
        
        return \Illuminate\Support\Facades\DB::select($sql, $params);
    }

    public function getFilteredBookings(array $filters): array
    {
        list($whereSql, $params) = $this->buildReportConditions($filters);
        
        $sql = "SELECT b.*,
                       (SELECT GROUP_CONCAT(r.room_number ORDER BY r.room_number SEPARATOR ', ')
                          FROM booking_rooms br2 
                          JOIN rooms r ON br2.room_id = r.id 
                         WHERE br2.booking_id = b.id) AS room_number,
                       (SELECT GROUP_CONCAT(rt.type_name SEPARATOR ', ')
                          FROM booking_rooms br3 
                          JOIN rooms r2 ON br3.room_id = r2.id 
                          JOIN room_types rt ON r2.room_type_id = rt.id 
                         WHERE br3.booking_id = b.id) AS type_name
                  FROM bookings b
                 WHERE $whereSql
                 ORDER BY b.check_in DESC";
                 
        return \Illuminate\Support\Facades\DB::select($sql, $params);
    }
}
