<?php

namespace Carone\Media\Services;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\ValueObjects\MediaType;

abstract class MediaService
{
    protected function getStrategy(string|MediaType $type): MediaStrategy
    {
        $mediaType = $type instanceof MediaType ? $type : MediaType::tryFrom($type);
        if (!$mediaType)
            throw new \InvalidArgumentException("Invalid media type: {$type}");

        if (!$mediaType->isEnabled())
            throw new \InvalidArgumentException("Media type '{$mediaType->value}' is not enabled in this project");

        return app($mediaType->getStrategyClass());
    }

    /**
     * Get file size in human readable format
     */
    protected function formatFileSize(int $bytes): string|MediaType
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}