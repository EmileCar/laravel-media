<?php

namespace Carone\Media\Facades;

use Carone\Media\Models\MediaResource;
use Illuminate\Support\Facades\Facade;

/**
 * This facade provides all media functionality through a clean, simple interface.
 * External projects should ONLY use this facade and not access internal classes directly.
 *
 * @method static MediaResource store(\Carone\Media\ValueObjects\StoreMediaData $data) Store a new media file (local or external)
 * @method static MediaResource getById(int $id) Get media resource by ID
 * @method static \Illuminate\Pagination\LengthAwarePaginator search(string $query, string|null $type = null, int $limit = 20, int $offset = 0) Search media with optional type filter
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse serve(string $path) Serve media file by path
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse thumbnail(string $path) Serve thumbnail by media path
 * @method static bool delete(int $id) Delete media by ID
 * @method static array deleteMultiple(array $ids) Delete multiple media files by IDs
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
