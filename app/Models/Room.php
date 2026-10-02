<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_number', 'room_type_id', 'floor', 'status',
        'needs_cleaning', 'cleaning_requested_at',
    ];

    protected $casts = [
        'needs_cleaning' => 'boolean',
        'cleaning_requested_at' => 'datetime',
    ];

    const STATUS_AVAILABLE   = 'available';
    const STATUS_BOOKED      = 'soon_to_checkin';
    const STATUS_OCCUPIED    = 'occupied';
    const STATUS_CLEANING    = 'cleaning';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_OVERDUE     = 'overdue';

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_rooms');
    }

    public function faceProfiles()
    {
        return $this->hasMany(FaceProfile::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
