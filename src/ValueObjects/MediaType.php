<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\Strategies\AudioStrategy;
use Carone\Media\Strategies\DocumentStrategy;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Strategies\VideoStrategy;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';

    /**
     * Get the strategy class for this media type
     */
    public function getStrategyClass(): string
    {
        return match($this) {
            self::IMAGE => ImageStrategy::class,
            self::VIDEO => VideoStrategy::class,
            self::AUDIO => AudioStrategy::class,
            self::DOCUMENT => DocumentStrategy::class,
        };
    }

    /**
     * Get the human-readable label for this media type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
        };
    }

    /**
     * Get validation rules for this media type
     */
    public function getValidationRules(): array
    {
        return config("media.validation.{$this->value}", []) ?? [];
    }

    /**
     * Check if this media type supports thumbnails
     */
    public function supportsThumbnails(): bool
    {
        return match($this) {
            self::IMAGE => true,
            self::VIDEO, self::AUDIO, self::DOCUMENT => false,
            default => false,
        };
    }

    /**
     * Check if a given type is enabled
     */
    public function isEnabled(): bool
    {
        $enabledTypes = config('media.enabled_types', ['image', 'video', 'audio', 'document']);
        return in_array($this->value, $enabledTypes);
    }

    /**
     * Get MIME types that this media type supports
     */
    public function getSupportedMimeTypes(): array
    {
        return match($this) {
            self::IMAGE => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
            self::VIDEO => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
            self::AUDIO => ['audio/mpeg', 'audio/wav', 'audio/mp3'],
            self::DOCUMENT => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        };
    }

    /**
     * Get file extensions that this media type supports
     */
    public function getSupportedExtensions(): array
    {
        return match($this) {
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::VIDEO => ['mp4', 'mov', 'avi'],
            self::AUDIO => ['mp3', 'wav'],
            self::DOCUMENT => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        };
    }
}