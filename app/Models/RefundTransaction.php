<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundTransaction extends Model
{
    protected $fillable = [
        'booking_id', 'processed_by', 'amount', 'provider',
        'provider_reference', 'status', 'processed_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
