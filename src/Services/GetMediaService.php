<?php

namespace Carone\Media\Services;

use Carone\Media\Services\MediaService;
use Carone\Common\Search\AppliesSearchCriteria;
use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchFilter;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Models\MediaResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GetMediaService extends MediaService implements GetMediaServiceInterface, AppliesSearchCriteria
{
    public function __construct() { }

    public function getById(int $id): MediaResource
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

        $strategy = $this->getStrategy($media->type);
        return $strategy->getMediaFile($media);
    }

    public function search(SearchCriteria $criteria, ?int $offset = null, ?int $limit = null): LengthAwarePaginator
    {
        $query = $this->applySearchCriteria($criteria);

        $paginator = $query->paginate(
            perPage: $limit,
            page: $offset / $limit + 1
        );

        return $paginator;
    }

    public function applySearchCriteria(SearchCriteria $searchCriteria): Builder
    {
        $query = MediaModel::getClass()::query();

        if ($searchCriteria->searchTerm->hasValue()) {
            $terms = $searchCriteria->searchTerm->getTermsForQuery();

            $query->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('name', 'like', "%{$term}%")
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