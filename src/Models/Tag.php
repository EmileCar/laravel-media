<?php

namespace Carone\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'media_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the media resources that have this tag
     */
    public function mediaResources(): BelongsToMany
    {
        return $this->belongsToMany(
            config('media.model', MediaResource::class),
            'media_resource_tag',
            'tag_id',
            'media_resource_id'
        );
    }

    /**
     * Find or create a tag by name
     * Tags are automatically slugified for consistency
     */
    public static function findOrCreateByName(string $name): self
    {
        $slug = static::generateSlug($name);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }

    /**
     * Find or create multiple tags by names
     *
     * @param array $names
     * @return \Illuminate\Support\Collection<Tag>
     */
    public static function findOrCreateByNames(array $names): \Illuminate\Support\Collection
    {
        return collect($names)->map(fn($name) => static::findOrCreateByName($name));
    }

    /**
     * Generate a URL-friendly slug from a tag name
     */
    public static function generateSlug(string $name): string
    {
        return Str::slug(strtolower(trim($name)));
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate slug when creating
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });
    }
}
