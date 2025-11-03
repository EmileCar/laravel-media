<?php

namespace Carone\Media\Models;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends Model
{
    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'path', 'disk', 'group', 'url',
        'display_name', 'description', 'date', 'meta', 'thumbnail_file_name'
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

    public function loadThumbnailFileReference(): ?MediaFileReference
    {
        $fileReference = $this->loadFileReference();
        if (empty($fileReference) || empty($this->thumbnail_file_name)) {
            return null;
        }

        return MediaFileReference::fromPath($this->thumbnail_file_name, $this->disk);
    }
}
