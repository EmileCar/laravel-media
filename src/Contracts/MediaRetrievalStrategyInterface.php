<?php

namespace Carone\Media\Contracts;

use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface MediaRetrievalStrategyInterface
{
    /**
     * Get the media file response
     *
     * @param MediaResource $media
     * @return BinaryFileResponse
     */
    public function getMedia(MediaResource $media): BinaryFileResponse;

    /**
     * Get the thumbnail response (if applicable)
     *
     * @param MediaResource $media
     * @return BinaryFileResponse|null
     */
    public function getThumbnail(MediaResource $media): ?BinaryFileResponse;

    /**
     * Get the media type this strategy handles
     *
     * @return \Carone\Media\Enums\MediaType
     */
    public function getType(): MediaType;

    /**
     * Check if this strategy supports thumbnails
     *
     * @return bool
     */
    public function supportsThumbnails(): bool;

    /**
     * Get the API representation of the media
     *
     * @param MediaResource $media
     * @return array
     */
    public function getApiData(MediaResource $media): array;
}