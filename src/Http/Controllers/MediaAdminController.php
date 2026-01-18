<?php

namespace Carone\Media\Http\Controllers;

use Carone\Media\Models\MediaResource;
use Carone\Media\Models\Tag;
use Carone\Media\Services\GetMediaService;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class MediaAdminController extends Controller
{
    public function __construct(
        private readonly GetMediaService $getMediaService,
    ) {}

    /**
     * Display the admin panel
     */
    public function index(): View
    {
        $enabledTypes = config('media.enabled_types', []);
        $tagsEnabled = config('media.tags.enabled', true);

        return view('media::admin', [
            'enabledTypes' => $enabledTypes,
            'tagsEnabled' => $tagsEnabled,
        ]);
    }

    /**
     * Get all media with filters
     */
    public function getAllMedia(Request $request): JsonResponse
    {
        $query = MediaResource::query();

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by source
        if ($request->has('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        // Search by name or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by tags
        if ($request->has('tag') && $request->tag) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Order by
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginate
        $perPage = min((int) $request->get('per_page', 24), 100);
        $media = $query->with('tags')->paginate($perPage);

        // Transform each media item to include URLs
        $media->getCollection()->transform(function($item) {
            return $this->transformMediaForAdmin($item);
        });

        return response()->json($media);
    }

    /**
     * Get all tags
     */
    public function getAllTags(): JsonResponse
    {
        $tags = Tag::withCount('mediaResources')
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    /**
     * Update media tags
     */
    public function updateMediaTags(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:50',
        ]);

        $media = MediaResource::findOrFail($id);
        $media->syncTags($request->tags);

        return response()->json([
            'success' => true,
            'message' => 'Tags updated successfully',
            'tags' => $media->fresh()->tags,
        ]);
    }

    /**
     * Update media details
     */
    public function updateMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $media = MediaResource::findOrFail($id);

        if ($request->has('name')) {
            $media->name = $request->name;
        }

        if ($request->has('description')) {
            $media->description = $request->description;
        }

        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'media' => $media->fresh()->load('tags'),
        ]);
    }

    /**
     * Get media statistics
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total' => MediaResource::count(),
            'by_type' => [],
            'by_source' => [
                'local' => MediaResource::where('source', 'local')->count(),
                'external' => MediaResource::where('source', 'external')->count(),
            ],
            'total_tags' => Tag::count(),
            'recent_uploads' => MediaResource::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        // Get counts by type
        foreach (MediaType::cases() as $type) {
            $stats['by_type'][$type->value] = MediaResource::where('type', $type->value)->count();
        }

        return response()->json($stats);
    }

    /**
     * Transform media resource for admin panel
     * Returns all fields needed for admin operations plus computed URLs
     */
    private function transformMediaForAdmin($media): array
    {
        // Generate media URL
        $mediaUrl = match ($media->source) {
            'local' => url("/media/{$media->path}"),
            'external' => $media->url,
            default => null,
        };

        // Generate thumbnail URL
        $thumbnailUrl = null;
        if (!empty($media->thumbnail_url)) {
            // External thumbnail URL
            $thumbnailUrl = $media->thumbnail_url;
        } elseif (!empty($media->thumbnail_path)) {
            // Local thumbnail
            $thumbnailUrl = url("/media/thumbnails/{$media->id}");
        }

        $result = [
            'id' => $media->id,
            'name' => $media->name,
            'display_name' => $media->display_name ?? $media->name,
            'description' => $media->description,
            'date' => $media->date?->toDateString(),
            'type' => $media->type,
            'source' => $media->source,
            'file_name' => $media->file_name,
            'file_size' => $media->file_size,
            'path' => $media->path,
            'url' => $media->url,
            'thumbnail_path' => $media->thumbnail_path,
            'thumbnail_url' => $thumbnailUrl,
            'media_url' => $mediaUrl,
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
        ];

        // Include tags if enabled and loaded
        if (config('media.tags.enabled', false) && $media->relationLoaded('tags')) {
            $result['tags'] = $media->tags->map(function($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            })->toArray();
        }

        return $result;
    }
}
