<?php

namespace Carone\Media;

use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaManager
{
    public function __construct(
        private StoreMediaServiceInterface $storeService,
        private GetMediaServiceInterface $getService,
        private DeleteMediaServiceInterface $deleteService
    ) {}

    /**
     * Store a new media file
     */
    public function store(array $data): MediaResource
    {
        return $this->storeService->handle($data);
    }

    /**
     * Get media by ID
     */
    public function getById(int $id): MediaResource
    {
        return $this->getService->getById($id);
    }

    /**
     * Get media by type with pagination
     */
    public function getByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->getService->getByType($type, $limit, $offset);
    }

    /**
     * Search media with optional type filter
     */
    public function search(string $query, ?string $type = null, int $limit = 20, int $offset = 0): array
    {
        return $this->getService->search($query, $type, $limit, $offset);
    }

    /**
     * Serve a media file
     */
    public function serve(string $type, string $identifier): BinaryFileResponse
    {
        return $this->getService->serveMedia($type, $identifier);
    }

    /**
     * Serve a thumbnail
     */
    public function thumbnail(string $type, string $identifier): BinaryFileResponse
    {
        return $this->getService->serveThumbnail($type, $identifier);
    }

    /**
     * Delete media by ID
     */
    public function delete(int $id): bool
    {
        return $this->deleteService->delete($id);
    }

    /**
     * Delete multiple media files
     */
    public function deleteMultiple(array $ids): array
    {
        return $this->deleteService->deleteMultiple($ids);
    }

    /**
     * Delete all media of a specific type
     */
    public function deleteByType(string $type, array $filters = []): array
    {
        return $this->deleteService->deleteByType($type, $filters);
    }

    /**
     * Clean up orphaned files for a specific type
     */
    public function cleanupOrphanedFiles(string $type): array
    {
        return $this->deleteService->cleanupOrphanedFiles($type);
    }

    /**
     * Get list of enabled media types
     */
    public function getEnabledTypes(): array
    {
        return MediaUtilities::getEnabled();
    }
}