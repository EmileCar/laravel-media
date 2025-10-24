<?php

namespace Carone\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaResource extends Model
{
    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'file_name', 'path', 'url',
        'name', 'description', 'date', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'date' => 'date',
    ];
}
