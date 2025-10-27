<?php

namespace Carone\Media\Models;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends Model
{
    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'path', 'disk', 'group', 'url',
        'display_name', 'description', 'date', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'date' => 'date',
    ];

    public function loadFileReference(): ?MediaFileReference
    {
        if ($this->source === 'external' || empty($this->path)) {
            return null;
        }

        return MediaFileReference::fromPath($this->path, $this->disk);
    }
}
