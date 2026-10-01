<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $fillable = ['source', 'title', 'content', 'content_hash', 'embedding', 'embedding_model'];

    protected $casts = ['embedding' => 'array'];
}
