<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public $timestamps = false;

    protected $fillable = ['room_number', 'room_type_id', 'floor', 'status'];

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

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
