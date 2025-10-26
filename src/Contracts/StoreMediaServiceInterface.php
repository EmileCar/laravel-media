<?php

namespace Carone\Media\Contracts;

use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\StoreMediaData;

interface StoreMediaServiceInterface
{
    /**
     * Handle media upload
     *
     * @param \Carone\Media\ValueObjects\StoreLocalMediaData|\Carone\Media\ValueObjects\StoreExternalMediaData $data
     * @return MediaResource
     */
    public function store(StoreMediaData $data): MediaResource;
}
