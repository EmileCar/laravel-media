<?php

namespace Carone\Media\Actions;

use Carone\Media\Contracts\MediaRetrievalStrategyInterface;
use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GetMediaAction
{
    use AsAction;

    private array $strategies = [];

    public function __construct()
    {
        // Strategies will be injected via the service provider
    }

    public function setStrategies(array $strategies): void
    {
        $this->strategies = $strategies;
    }

    /**
     * Get media by ID
     *
     * @param int $id
     * @return MediaResource
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): MediaResource
    {
        return MediaResource::findOrFail($id);
    }

    /**
     * Get media by type with pagination
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByType(string $type, int $limit = 20, int $offset = 0): array
    {
        if (!MediaType::isEnabled($type)) {
            throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
        }

        $query = MediaResource::where('type', $type);
        $total = $query->count();
        
        $media = $query->skip($offset)
            ->take($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        $strategy = $this->getStrategy($type);
        
        $data = $media->map(function($item) use ($strategy) {
            return $strategy->getApiData($item);
        });

        return [
            'data' => $data,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Get all enabled media types
     *
     * @return array
     */
    public function getMediaTypes(): array
    {
        return MediaType::getEnabledForApi();
    }

    /**
     * Serve media file
     *
     * @param string $type
     * @param string $identifier
     * @return BinaryFileResponse
     */
    public function serveMedia(string $type, string $identifier): BinaryFileResponse
    {
        $media = MediaResource::where('type', $type)
            ->where('source', 'local')
            ->where('file_name', $identifier)
            ->firstOrFail();

        $strategy = $this->getStrategy($type);
        return $strategy->getMedia($media);
    }

    /**
     * Serve thumbnail file
     *
     * @param string $type
     * @param string $identifier
     * @return BinaryFileResponse
     */
    public function serveThumbnail(string $type, string $identifier): BinaryFileResponse
    {
        $media = MediaResource::where('type', $type)
            ->where('file_name', $identifier)
            ->firstOrFail();

        $strategy = $this->getStrategy($type);
        
        if (!$strategy->supportsThumbnails()) {
            abort(404, 'Thumbnails not supported for this media type');
        }

        $thumbnail = $strategy->getThumbnail($media);
        
        if (!$thumbnail) {
            abort(404, 'Thumbnail not found');
        }

        return $thumbnail;
    }

    /**
     * Search media by name or description
     *
     * @param string $query
     * @param string|null $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(string $query, ?string $type = null, int $limit = 20, int $offset = 0): array
    {
        $builder = MediaResource::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        });

        if ($type) {
            if (!MediaType::isEnabled($type)) {
                throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
            }
            $builder->where('type', $type);
        }

        $total = $builder->count();
        
        $media = $builder->skip($offset)
            ->take($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $media->map(function($item) {
            $strategy = $this->getStrategy($item->type);
            return $strategy->getApiData($item);
        });

        return [
            'data' => $data,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'query' => $query,
            'type' => $type,
        ];
    }

    /**
     * Get the appropriate strategy for the media type
     *
     * @param string $type
     * @return MediaRetrievalStrategyInterface
     * @throws \InvalidArgumentException
     */
    private function getStrategy(string $type): MediaRetrievalStrategyInterface
    {
        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException("No strategy found for media type: {$type}");
        }

        return $this->strategies[$type];
    }

    /**
     * Static method for easier usage - get by ID
     *
     * @param int $id
     * @return MediaResource
     */
    public static function byId(int $id): MediaResource
    {
        $action = app(static::class);
        return $action->getById($id);
    }

    /**
     * Static method for easier usage - get by type
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function byType(string $type, int $limit = 20, int $offset = 0): array
    {
        $action = app(static::class);
        return $action->getByType($type, $limit, $offset);
    }
}