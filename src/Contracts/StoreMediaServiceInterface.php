<?php

namespace Carone\Media\Contracts;

use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\StoreMediaData;

interface StoreMediaServiceInterface
{
    /**
     * Store a media resource, either for a local file or for an external source
     *
     * @param \Carone\Media\ValueObjects\StoreLocalMediaData|\Carone\Media\ValueObjects\StoreExternalMediaData $data
     * @return MediaResource
     */
    public function store(StoreMediaData $data): MediaResource;
}
