<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'room_type_id', 'rating', 'comment'];

    public function user()     { return $this->belongsTo(User::class); }
    public function roomType() { return $this->belongsTo(RoomType::class); }
}
