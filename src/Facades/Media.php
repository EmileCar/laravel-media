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
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse thumbnail(int $id) Serve thumbnail by media ID
 * @method static \Symfony\Component\HttpFoundation\Response stream(int $id, \Symfony\Component\HttpFoundation\Request|null $request = null) Stream a media file inline (any disk, HTTP Range support)
 * @method static \Symfony\Component\HttpFoundation\Response download(int $id, string|null $filename = null, \Symfony\Component\HttpFoundation\Request|null $request = null) Stream a media file as download
 * @method static \Symfony\Component\HttpFoundation\Response streamThumbnail(int $id, \Symfony\Component\HttpFoundation\Request|null $request = null) Stream the thumbnail of a media resource
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
