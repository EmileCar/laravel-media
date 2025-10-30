<?php

namespace Carone\Media\Strategies;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\ValueObjects\MediaType;

class DocumentStrategy extends MediaStrategy
{
    public function getType(): MediaType
    {
        return MediaType::DOCUMENT;
    }
}
