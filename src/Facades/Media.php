<?php

namespace Carone\Media\Facades;

use Carone\Media\Models\MediaResource;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 * This facade provides all media functionality through a clean, simple interface.
 * External projects should ONLY use this facade and not access internal classes directly.
 *
 * @method static MediaResource store(array $data) Store a new media file
 * @method static MediaResource getById(int $id) Get media by ID
 * @method static array getByType(string $type, int $limit = 20, int $offset = 0) Get media by type
 * @method static array search(string $query, string|null $type = null, int $limit = 20, int $offset = 0) Search media
 * @method static BinaryFileResponse serve(string $type, string $identifier) Serve media file
 * @method static BinaryFileResponse thumbnail(string $type, string $identifier) Serve thumbnail
 * @method static bool delete(int $id) Delete media by ID
 * @method static array deleteMultiple(array $ids) Delete multiple media files
 * @method static array deleteByType(string $type, array $filters = []) Delete all media of a specific type
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