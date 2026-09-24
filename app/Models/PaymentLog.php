<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

// ============================================================
// PaymentLog — lưu log từ webhook và polling
// ============================================================
class PaymentLog extends Model
{
    protected $fillable = [
        'booking_id', 'gateway', 'transaction_id',
        'reference_code', 'amount', 'status', 'raw_response',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'raw_response' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
