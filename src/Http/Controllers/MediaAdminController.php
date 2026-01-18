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
}
