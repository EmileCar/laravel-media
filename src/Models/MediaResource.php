<?php

namespace Carone\Media\Models;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends Model
{
    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'file_name', 'extension', 'disk', 'directory', 'group', 'url',
        'display_name', 'description', 'date', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'date' => 'date',
    ];

    public function loadFileReference(): ?MediaFileReference
    {
        if ($this->source === 'external') {
            return null;
        }

        return new MediaFileReference(
            $this->file_name,
            $this->extension,
            $this->disk,
            $this->directory
        );
    }
}
