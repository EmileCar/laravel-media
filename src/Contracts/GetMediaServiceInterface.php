<?php

namespace Carone\Media\Contracts;

use Carone\Common\Search\SearchCriteria;
use Carone\Media\Models\MediaResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface GetMediaServiceInterface
{
    public function getById(int $id): MediaResource;

    public function getMediaTypes(): array;

    public function serveMedia(string $path): BinaryFileResponse;

    public function search(SearchCriteria $criteria, ?int $offset, ?int $limit): LengthAwarePaginator;
}
