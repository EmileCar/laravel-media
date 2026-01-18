<?php

namespace Carone\Media\Services;

use Carone\Common\Search\AppliesSearchCriteria;
use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchFilter;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\Utilities\MediaUtilities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GetMediaService implements GetMediaServiceInterface, AppliesSearchCriteria
{
    public function getResourceById(int $id): MediaResource
    {
        $query = MediaModel::getClass()::query();

        // Eager load tags if enabled
        if (config('media.tags.enabled', false)) {
            $query->with('tags');
        }

        return $query->findOrFail($id);
    }

    public function getMediaTypes(): array
    {
        return MediaUtilities::getEnabled();
    }

    public function serveMedia(string $path): BinaryFileResponse
    {
        // Decode the path in case it contains URL-encoded characters
        $path = urldecode($path);

        // Find media by path (disk is no longer stored, always uses config)
        $media = MediaModel::where('source', 'local')
            ->where('path', $path)
            ->firstOrFail();

        $fileReference = $media->loadFileReference();
        if (!$fileReference || !MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getStoragePath())) {
            abort(404, 'Media file not found');
        }

        $physicalPath = MediaStorageHelper::getPhysicalPath($fileReference);
        $mimeType = MediaUtilities::getMimeType($fileReference->extension, 'image/jpg');

        $cacheMinutes = config('media.cache_minutes');

        return response()->file($physicalPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => "public, max-age=" . ($cacheMinutes * 60),
        ]);
    }

    public function serveThumbnail(int $id): BinaryFileResponse
    {
        $media = MediaModel::where('source', 'local')
            ->where('thumbnail_path', '!=', null)
            ->findOrFail($id);

        $fileReference = $media->loadThumbnailFileReference();
        if (!$fileReference || !MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getThumbnailStoragePath())) {
            abort(404, 'Thumbnail not found');
        }

        $path = MediaStorageHelper::getPhysicalPath($fileReference, isThumbnail: true);
        $mimeType = MediaUtilities::getMimeType($fileReference->extension, 'image/jpg');

        $cacheMinutes = config('media.cache_minutes');

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => "public, max-age=" . ($cacheMinutes * 60),
        ]);
    }

    public function search(SearchCriteria $criteria, ?int $offset = null, ?int $limit = null): LengthAwarePaginator
    {
        $query = $this->applySearchCriteria($criteria);

        return $query->paginate(
            perPage: $limit ?? 20,
            page: ($offset ?? 0) / ($limit ?? 20) + 1
        );
    }

    public function applySearchCriteria(SearchCriteria $searchCriteria): Builder
    {
        $query = MediaModel::getClass()::query();

        // Eager load tags if enabled
        if (config('media.tags.enabled', false)) {
            $query->with('tags');
        }

        if ($searchCriteria->searchTerm->hasValue()) {
            $terms = $searchCriteria->searchTerm->getTermsForQuery();

            $query->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('display_name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
                }
            });
        }

        foreach ($searchCriteria->filters as $type => $values) {
            $filter = match ($type) {
                'type' => new MediaTypeFilter($values),
                'tags' => new MediaTagFilter($values),
                default => null,
            };

            if ($filter instanceof SearchFilter) {
                $filter->apply($query);
            }
        }

        return $query;
    }

    /**
     * Get all tags used in media resources
     *
     * @return array Array of tag names with counts
     */
    public function getAllTags(): array
    {
        if (!config('media.tags.enabled', false)) {
            return [];
        }

        return \Carone\Media\Models\Tag::withCount('mediaResources')
            ->orderBy('name')
            ->get()
            ->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->media_resources_count,
            ])
            ->toArray();
    }
}

class MediaTypeFilter implements SearchFilter
{
    public function __construct(private readonly array $types) {}

    public function apply(Builder $query): Builder
    {
        return $query->whereIn('type', $this->types);
    }
}

class MediaTagFilter implements SearchFilter
{
    public function __construct(private readonly array $tags) {}

    public function apply(Builder $query): Builder
    {
        if (empty($this->tags) || !config('media.tags.enabled', false)) {
            return $query;
        }

        // Filter by tag names or slugs
        return $query->whereHas('tags', function (Builder $q) {
            $q->where(function (Builder $tagQuery) {
                foreach ($this->tags as $tag) {
                    $tagQuery->orWhere('name', $tag)
                             ->orWhere('slug', \Illuminate\Support\Str::slug($tag));
                }
            });
        });
    }
}
