<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceSyncJob extends Model
{
    protected $table = 'face_sync_queue';

    protected $fillable = [
        'face_profile_id', 'action', 'status', 'retry_count',
        'next_attempt_at', 'last_attempt_at', 'last_error', 'synced_at',
    ];

    protected $casts = [
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->belongsTo(FaceProfile::class, 'face_profile_id');
    }
}

