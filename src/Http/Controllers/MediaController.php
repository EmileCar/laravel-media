<?php

namespace Carone\Media\Http\Controllers;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Media\Http\Requests\BulkDeleteMediaRequest;
use Carone\Media\Http\Requests\SearchMediaRequest;
use Carone\Media\Http\Requests\UploadMediaRequest;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly GetMediaService $getMediaService,
        private readonly StoreMediaService $storeMediaService,
        private readonly DeleteMediaService $deleteMediaService,
    ) {}

    /**
     * Get enabled media types
     */
    public function getMediaTypes(): JsonResponse
    {
        $types = $this->getMediaService->getMediaTypes();
        return response()->json(['types' => $types]);
    }

    /**
     * Get media by type with pagination
     */
    public function getMediaByType(Request $request, string $type): JsonResponse
    {
        try {
            // Validate type
            $validTypes = array_map(fn($case) => $case->value, MediaType::cases());
            if (!in_array($type, $validTypes)) {
                return response()->json(['error' => 'Invalid media type'], 400);
            }

            $limit = min((int) $request->get('limit', 20), 100); // Max 100 items
            $offset = max((int) $request->get('offset', 0), 0);

            $criteria = new SearchCriteria(
                searchTerm: new SearchTerm(''),
                filters: ['type' => [$type]]
            );

            $result = $this->getMediaService->search($criteria, $offset, $limit);

            return response()->json([
                'data' => $result->items(),
                'total' => $result->total(),
                'limit' => $limit,
                'offset' => $offset,
            ]);

        } catch (\Exception $e) {
            logger()->error('Error fetching media by type: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media'], 500);
        }
    }

    /**
     * Search media
     */
    public function searchMedia(SearchMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $query = $validated['q'];
            $type = $validated['type'] ?? null;
            $limit = $validated['limit'] ?? 20;
            $offset = $validated['offset'] ?? 0;

            $filters = [];
            if ($type) {
                $filters['type'] = [$type];
            }

            $criteria = new SearchCriteria(
                searchTerm: new SearchTerm($query),
                filters: $filters
            );

            $result = $this->getMediaService->search($criteria, $offset, $limit);

            return response()->json([
                'data' => $result->items(),
                'total' => $result->total(),
                'limit' => $limit,
                'offset' => $offset,
            ]);

        } catch (\Exception $e) {
            logger()->error('Error searching media: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search media'], 500);
        }
    }

    /**
     * Upload media
     */
    public function uploadMedia(UploadMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $type = MediaType::from($validated['type']);

            if ($validated['source'] === 'local') {
                $data = new StoreLocalMediaData(
                    type: $type,
                    file: $request->file('file'),
                    fileName: null,
                    name: $validated['name'],
                    description: $validated['description'] ?? null,
                    date: now(),
                    meta: [],
                    directory: $validated['directory'] ?? null,
                    generateThumbnail: $validated['generate_thumbnail'] ?? false,
                );
            } else {
                $data = new StoreExternalMediaData(
                    type: $type,
                    url: $validated['url'],
                    name: $validated['name'],
                    description: $validated['description'] ?? null,
                    date: now(),
                    meta: [],
                );
            }

            $media = $this->storeMediaService->store($data);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => $media
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            logger()->error('Media upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media'
            ], 500);
        }
    }

    /**
     * Get media by ID
     */
    public function getMediaById(int $id): JsonResponse
    {
        try {
            $media = $this->getMediaService->getResourceById($id);
            return response()->json(['data' => $media]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Media not found'], 404);
        } catch (\Exception $e) {
            logger()->error('Error fetching media by ID: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media'], 500);
        }
    }

    /**
     * Delete media
     */
    public function deleteMedia(int $id): JsonResponse
    {
        try {
            $this->deleteMediaService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found'
            ], 404);
        } catch (\Exception $e) {
            logger()->error('Media deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media'
            ], 500);
        }
    }

    /**
     * Bulk delete media
     */
    public function bulkDeleteMedia(BulkDeleteMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->deleteMediaService->deleteMultiple($validated['ids']);

            return response()->json([
                'success' => true,
                'message' => "Deleted {$result->getSucceededCount()} of {$result->getTotalCount()} media items",
                'deleted' => $result->getSucceededCount(),
                'failed' => $result->getFailedCount(),
                'failures' => $result->getFailed()
            ]);

        } catch (\Exception $e) {
            logger()->error('Bulk media deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media items'
            ], 500);
        }
    }

    /**
     * Serve media file
     */
    public function getMedia(int $id): BinaryFileResponse
    {
        try {
            return $this->getMediaService->serveMedia($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Media not found');
        } catch (\Exception $e) {
            logger()->error('Error serving media: ' . $e->getMessage());
            abort(500, 'Failed to serve media');
        }
    }

    /**
     * Serve thumbnail
     */
    public function getThumbnail(int $id): BinaryFileResponse
    {
        try {
            return $this->getMediaService->serveThumbnail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Thumbnail not found');
        } catch (\Exception $e) {
            logger()->error('Error serving thumbnail: ' . $e->getMessage());
            abort(404, 'Thumbnail not found');
        }
    }
}
