<?php

namespace Carone\Media\Contracts;

use Carone\Common\Search\SearchCriteria;
use Carone\Media\Models\MediaResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface GetMediaServiceInterface
{
    /**
     * Get media resource by ID
     *
     * @param int $id
     * @return MediaResource
     */
    public function getResourceById(int $id): MediaResource;

    /**
     * Get available media types
     *
     * @return array
     */
    public function getMediaTypes(): array;

    /**
     * Serve media file by path
     *
     * @param string $path
     * @return BinaryFileResponse
     */
    public function serveMedia(string $path): BinaryFileResponse;

    /**
     * Search media resources based on criteria
     *
     * @param SearchCriteria $criteria
     * @param int|null $offset
     * @param int|null $limit
     * @return LengthAwarePaginator
     */
    public function search(SearchCriteria $criteria, ?int $offset = null, ?int $limit = null): LengthAwarePaginator;
}
