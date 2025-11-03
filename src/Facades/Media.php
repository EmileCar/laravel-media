<?php

namespace Carone\Media\Facades;

use Carone\Media\Models\MediaResource;
use Illuminate\Support\Facades\Facade;

/**
 * This facade provides all media functionality through a clean, simple interface.
 * External projects should ONLY use this facade and not access internal classes directly.
 *
 * @method static MediaResource store(StoreMediaData $data) Store a new media file
 * @method static MediaResource getById(int $id) Get media by ID
 * @method static \Illuminate\Pagination\LengthAwarePaginator search(string $query, string|null $type = null, int $limit = 20, int $offset = 0) Search media
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse serve(string $path) Serve media file
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse thumbnail(string $path) Serve thumbnail
 * @method static bool delete(int $id) Delete media by ID
 * @method static array deleteMultiple(array $ids) Delete multiple media files
 * @method static array deleteByType(string $type) Delete all media of a specific type
 * @method static array cleanupOrphanedFiles(string $type) Clean up orphaned files for a specific type
 * @method static array getEnabledTypes() Get list of enabled media types
 */
class Media extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'carone.media';
    }
}
