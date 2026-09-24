<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type_name', 'price', 'max_adults',
        'max_children', 'max_guests', 'description', 'image',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'max_adults'   => 'integer',
        'max_children' => 'integer',
        'max_guests'   => 'integer',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenities');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function availableRooms()
    {
        return $this->rooms()->where('status', 'available');
    }
}
