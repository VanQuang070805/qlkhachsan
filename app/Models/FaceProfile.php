<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FaceProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id', 'user_id', 'embedding', 'embedding_model',
        'embedding_dimension', 'sample_count', 'version', 'active',
        'consent_at', 'revoked_at',
    ];

    protected $hidden = ['embedding'];

    protected $casts = [
        'embedding' => 'encrypted:array',
        'active' => 'boolean',
        'consent_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function syncJobs()
    {
        return $this->hasMany(FaceSyncJob::class);
    }
}

