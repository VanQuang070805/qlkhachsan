<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiIdempotencyKey extends Model
{
    protected $table = 'api_idempotency_keys';

    protected $fillable = [
        'actor_scope',
        'route_signature',
        'idempotency_key_hash',
        'request_hash',
        'status_code',
        'response_body',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'status_code' => 'integer',
        'expires_at' => 'datetime',
    ];
}
