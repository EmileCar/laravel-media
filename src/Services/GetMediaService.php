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
        return MediaModel::findOrFail($id);
    }

    public function getMediaTypes(): array
    {
        return MediaUtilities::getEnabled();
    }

    public function serveMedia(string $path): BinaryFileResponse
    {
        $media = MediaModel::where('source', 'local')
            ->where('path', $path)
            ->firstOrFail();

        $fileReference = $media->loadFileReference();
        if (!$fileReference || !MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getPath())) {
            abort(404, 'Media file not found');
        }

        $path = MediaStorageHelper::getPhysicalPath($fileReference);
        $mimeType = MediaUtilities::getMimeType($fileReference->extension, 'image/jpg');

        $cacheMinutes = config('media.cache_minutes');

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => "public, max-age=" . ($cacheMinutes * 60),
        ]);
    }

    public function serveThumbnail(string $path): BinaryFileResponse
    {
        $media = MediaModel::where('source', 'local')
            ->where('path', $path)
            ->firstOrFail();

        $fileReference = $media->loadThumbnailFileReference();
        if (!$fileReference || !MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getPath())) {
            abort(404, 'Media file not found');
        }

        $path = MediaStorageHelper::getPhysicalPath($fileReference);
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
                default => null,
            };

            if ($filter instanceof SearchFilter) {
                $filter->apply($query);
            }
        }

        return $query;
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
