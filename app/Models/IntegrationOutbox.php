<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOutbox extends Model
{
    protected $table = 'integration_outbox';

    protected $fillable = [
        'event_id',
        'topic',
        'event_key',
        'event_type',
        'schema_version',
        'correlation_id',
        'payload',
        'available_at',
        'published_at',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'published_at' => 'datetime',
        'attempts' => 'integer',
        'schema_version' => 'integer',
    ];
}
