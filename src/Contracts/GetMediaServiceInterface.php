<?php

namespace Carone\Media\Contracts;

use Carone\Common\Search\SearchCriteria;
use Carone\Media\Models\MediaResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * Serve thumbnail file by media ID
     *
     * @param int $id
     * @return BinaryFileResponse
     */
    public function serveThumbnail(int $id): BinaryFileResponse;

    /**
     * Stream a local media file by media ID, on any disk (also private disks).
     * Supports HTTP Range requests (206 Partial Content).
     *
     * @param int $id
     * @param Request|null $request Used for the Range header; defaults to the current request
     * @param bool $download Send as attachment instead of inline
     * @param string|null $filename Defaults to the original upload name
     * @return Response
     */
    public function streamMedia(int $id, ?Request $request = null, bool $download = false, ?string $filename = null): Response;

    /**
     * Stream the local thumbnail of a media resource by media ID
     *
     * @param int $id
     * @param Request|null $request
     * @return Response
     */
    public function streamThumbnail(int $id, ?Request $request = null): Response;

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
