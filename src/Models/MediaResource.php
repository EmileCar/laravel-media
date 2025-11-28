<?php

namespace Carone\Media\Models;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends Model
{
    use HasFactory;

    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'path', 'disk', 'url',
        'display_name', 'description', 'date', 'meta',
        'thumbnail_path', 'thumbnail_url', 'thumbnail_disk'
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

    /**
     * Load thumbnail file reference for local thumbnails
     */
    public function loadThumbnailFileReference(): ?MediaFileReference
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        $disk = $this->thumbnail_disk ?? $this->disk;
        return MediaFileReference::fromPath($this->thumbnail_path, $disk);
    }

    /**
     * Get the thumbnail URL (either external or generated from local path)
     */
    public function getThumbnailUrl(): ?string
    {
        // External thumbnail URL takes priority
        if (!empty($this->thumbnail_url)) {
            return $this->thumbnail_url;
        }

        // Generate URL from local thumbnail path
        if (!empty($this->thumbnail_path)) {
            $disk = $this->thumbnail_disk ?? $this->disk;
            return \Storage::disk($disk)->url($this->thumbnail_path);
        }

        return null;
    }

    /**
     * Check if this media has a thumbnail
     */
    public function hasThumbnail(): bool
    {
        return !empty($this->thumbnail_url) || !empty($this->thumbnail_path);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\MediaResourceFactory::new();
    }
}
