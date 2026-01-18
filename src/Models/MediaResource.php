<?php

namespace Carone\Media\Models;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaResource extends Model
{
    use HasFactory;

    protected $table = 'media_resources';

    protected $fillable = [
        'type', 'source', 'path', 'url',
        'display_name', 'description', 'date', 'meta',
        'thumbnail_path', 'thumbnail_url'
    ];

    protected $casts = [
        'meta' => 'array',
        'date' => 'date',
        'type' => 'string',
    ];

    public function loadFileReference(): ?MediaFileReference
    {
        if ($this->source === 'external' || empty($this->path)) {
            return null;
        }

        $disk = config('media.disk');
        return MediaFileReference::fromPath($this->path, $disk);
    }

    /**
     * Load thumbnail file reference for local thumbnails
     * Uses the thumbnail storage path configuration
     */
    public function loadThumbnailFileReference(): ?MediaFileReference
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        $thumbnailDisk = config('media.thumbnails.disk') ?? config('media.disk');
        return MediaFileReference::fromThumbnailPath($this->thumbnail_path, $thumbnailDisk);
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
            $disk = config('media.thumbnails.disk') ?? config('media.disk');
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
     * Get the tags for this media resource
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'media_resource_tag',
            'media_resource_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Sync tags with the media resource
     * Creates tags if they don't exist
     *
     * @param array $tagNames Array of tag names
     */
    public function syncTags(array $tagNames): void
    {
        if (!$this->tagsEnabled()) {
            return;
        }

        $tags = Tag::findOrCreateByNames($tagNames);
        $this->tags()->sync($tags->pluck('id'));
    }

    /**
     * Check if tags functionality is enabled
     */
    public function tagsEnabled(): bool
    {
        return config('media.tags.enabled', false);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\MediaResourceFactory::new();
    }
}
