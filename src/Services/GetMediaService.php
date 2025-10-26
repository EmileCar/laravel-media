<?php

namespace Carone\Media\Services;

use App\Services\MediaService;
use Carone\Common\Search\AppliesSearchCriteria;
use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchFilter;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Traits\HasThumbnails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GetMediaService extends MediaService implements GetMediaServiceInterface, AppliesSearchCriteria
{
    public function __construct() { }

    public function getById(int $id): MediaResource
    {
        return MediaResource::findOrFail($id);
    }

    public function getMediaTypes(): array
    {
        return MediaType::getEnabled();
    }

    public function serveMedia(string $type, MediaF): BinaryFileResponse
    {
        $media = MediaResource::where('type', $type)
            ->where('source', 'local')
            ->where('file_name', $identifier)
            ->firstOrFail();

        $strategy = $this->getStrategy($type);
        return $strategy->getMediaFile($media);
    }

    public function serveThumbnail(string $type, string $identifier): BinaryFileResponse
    {
        $media = MediaResource::where('type', $type)
            ->where('source', 'local')
            ->where('file_name', $identifier)
            ->firstOrFail();

        $strategy = $this->getStrategy($type);

        if (! in_array(HasThumbnails::class, class_uses_recursive($strategy))) {
            abort(404, 'Thumbnails not supported for this media type');
        }

        $thumbnail = $strategy->getThumbnail($media);

        if (!$thumbnail) {
            abort(404, 'Thumbnail not found');
        }

        return $thumbnail;
    }

    public function search(SearchCriteria $criteria, ?int $offset, ?int $limit): LengthAwarePaginator
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
        $query = MediaResource::query();

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