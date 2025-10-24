<?php

namespace Carone\Media\Contracts;

use Carone\Media\Enums\MediaType;
use Illuminate\Http\UploadedFile;
use Carone\Media\Models\MediaResource;

interface MediaUploadStrategyInterface
{
    /**
     * Handle the upload of a media file
     *
     * @param UploadedFile $file
     * @param array $data Additional data (name, description, etc.)
     * @return MediaResource
     */
    public function upload(UploadedFile $file, array $data): MediaResource;

    /**
     * Handle external media (URL)
     *
     * @param string $url
     * @param array $data Additional data (name, description, etc.)
     * @return MediaResource
     */
    public function uploadExternal(string $url, array $data): MediaResource;

    /**
     * Get the media type this strategy handles
     *
     * @return \Carone\Media\Enums\MediaType
     */
    public function getType(): MediaType;

    /**
     * Validate if the file is supported by this strategy
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function supports(UploadedFile $file): bool;
}