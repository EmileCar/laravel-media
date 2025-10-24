<?php

namespace Carone\Media\Http\Controllers;

use Carone\Media\Actions\GetMediaAction;
use Carone\Media\Actions\StoreMediaAction;
use Carone\Media\Actions\DeleteMediaAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Get media types
     */
    public function getMediaTypes(): JsonResponse
    {
        $types = GetMediaAction::make()->getMediaTypes();
        return response()->json($types);
    }

    /**
     * Get media by type with pagination
     */
    public function getMediaByType(Request $request, string $type): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 20);
            $offset = (int) $request->get('offset', 0);

            $result = GetMediaAction::byType($type, $limit, $offset);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            logger()->error('Error fetching media by type: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media'], 500);
        }
    }

    /**
     * Search media
     */
    public function searchMedia(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $type = $request->get('type');
            $limit = (int) $request->get('limit', 20);
            $offset = (int) $request->get('offset', 0);

            if (empty($query)) {
                return response()->json(['error' => 'Search query is required'], 400);
            }

            $result = GetMediaAction::make()->search($query, $type, $limit, $offset);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            logger()->error('Error searching media: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search media'], 500);
        }
    }

    /**
     * Upload media
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Handle file upload
            if ($request->hasFile('file')) {
                $data['file'] = $request->file('file');
            }

            $media = StoreMediaAction::run($data);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'media' => $media
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            logger()->error('Media upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete media
     */
    public function deleteMedia(int $id): JsonResponse
    {
        try {
            DeleteMediaAction::run($id);

            return response()->json([
                'success' => true,
                'message' => 'Media successfully deleted'
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
                'message' => 'Failed to delete media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve media file
     */
    public function getMedia(string $type, string $identifier): BinaryFileResponse
    {
        try {
            return GetMediaAction::make()->serveMedia($type, $identifier);
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
    public function getThumbnail(string $type, string $identifier): BinaryFileResponse
    {
        try {
            return GetMediaAction::make()->serveThumbnail($type, $identifier);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Media not found');
        } catch (\Exception $e) {
            logger()->error('Error serving thumbnail: ' . $e->getMessage());
            abort(404, 'Thumbnail not found');
        }
    }

    /**
     * Get media by ID
     */
    public function getMediaById(int $id): JsonResponse
    {
        try {
            $media = GetMediaAction::byId($id);
            return response()->json($media);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Media not found'], 404);
        } catch (\Exception $e) {
            logger()->error('Error fetching media by ID: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media'], 500);
        }
    }

    /**
     * Bulk delete media
     */
    public function bulkDeleteMedia(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'IDs array is required'
                ], 400);
            }

            $result = DeleteMediaAction::make()->deleteMultiple($ids);

            return response()->json([
                'success' => true,
                'message' => "Deleted {$result['deleted']} media items",
                'deleted' => $result['deleted'],
                'failed' => count($result['failed']),
                'failures' => $result['failed']
            ]);

        } catch (\Exception $e) {
            logger()->error('Bulk media deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media items: ' . $e->getMessage()
            ], 500);
        }
    }
}