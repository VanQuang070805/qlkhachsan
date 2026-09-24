<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    public $timestamps = false;

    protected $fillable = ['amenity_name', 'icon'];

    public function roomTypes()
    {
        return $this->belongsToMany(RoomType::class, 'room_type_amenities');
    }
}
