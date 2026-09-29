<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSnapshot extends Model
{
    protected $fillable = ['snapshot_date', 'metrics'];

    protected $casts = [
        'snapshot_date' => 'date',
        'metrics' => 'array',
    ];
}
