<?php

namespace Carone\Media\Strategies;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\Models\MediaResource;

class VideoStrategy extends MediaStrategy
{
    public function getType(): MediaType
    {
        return MediaType::VIDEO;
    }
}
