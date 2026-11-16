<?php

namespace Carone\Media;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreMediaData;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Facade interface for the media system
 */
class MediaManager
{
    public function __construct(
        private StoreMediaServiceInterface $storeService,
        private GetMediaServiceInterface $getService,
        private DeleteMediaServiceInterface $deleteService
    ) {}

    /**
     * Store a file
     */
    public function store(StoreMediaData $data): MediaResource
    {
        return $this->storeService->store($data);
    }

    /**
     * Get media by ID
     */
    public function getById(int $id): MediaResource
    {
        return $this->getService->getResourceById($id);
    }

    /**
     * Search media with pagination
     */
    public function search(string $query, ?string $type = null, int $limit = 20, int $offset = 0): LengthAwarePaginator
    {
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm($query),
            filters: $type ? ['type' => [$type]] : []
        );

        return $this->getService->search($criteria, $offset, $limit);
    }

    /**
     * Serve a media file by path
     */
    public function serve(string $path): BinaryFileResponse
    {
        return $this->getService->serveMedia($path);
    }

    /**
     * Serve thumbnail by media ID
     */
    public function thumbnail(int $id): BinaryFileResponse
    {
        return $this->getService->serveThumbnail($id);
    }

    /**
     * Stream a media file inline by media ID (any disk, incl. private disks).
     * Supports HTTP Range requests (206 Partial Content) for video/audio seeking.
     * Meant for the application's own (authenticated) routes.
     */
    public function stream(int $id, ?Request $request = null): Response
    {
        return $this->getService->streamMedia($id, $request);
    }

    /**
     * Stream a media file as a download (Content-Disposition: attachment).
     * The filename defaults to the original upload name.
     */
    public function download(int $id, ?string $filename = null, ?Request $request = null): Response
    {
        return $this->getService->streamMedia($id, $request, download: true, filename: $filename);
    }

    /**
     * Stream the stored thumbnail of a media resource by media ID
     */
    public function streamThumbnail(int $id, ?Request $request = null): Response
    {
        return $this->getService->streamThumbnail($id, $request);
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
        $result = $this->deleteService->deleteMultiple($ids);

        return [
            'deleted' => count($ids), // For now, assume all succeeded
            'failed' => 0,
            'result' => $result, // Include the full result for advanced usage
        ];
    }

    /**
     * Delete all media of a specific type
     */
    public function deleteByType(string $type): array
    {
        $mediaType = MediaType::from($type);
        $result = $this->deleteService->deleteByType($mediaType);

        return [
            'deleted' => 0, // Would need to count actual deletions
            'failed' => 0,
            'result' => $result, // Include the full result for advanced usage
        ];
    }

    /**
     * Get list of enabled media types
     */
    public function getEnabledTypes(): array
    {
        return array_map(fn($type) => $type->value, MediaUtilities::getEnabled());
    }

    /**
     * Clean up orphaned files (placeholder for now)
     */
    public function cleanupOrphanedFiles(string $type): array
    {
        // TODO: Implement orphaned file cleanup
        return ['cleaned' => 0, 'errors' => []];
    }
}
