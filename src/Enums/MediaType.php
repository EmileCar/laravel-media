<?php

namespace Carone\Media\Enums;

use Carone\Media\Strategies\AudioStrategy;
use Carone\Media\Strategies\DocumentStrategy;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Strategies\VideoStrategy;
use Carone\Media\Contracts\MediaUploadStrategyInterface;
use Carone\Media\Contracts\MediaRetrievalStrategyInterface;

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
     * Get a strategy instance for this media type
     */
    public function getStrategy(): MediaUploadStrategyInterface&MediaRetrievalStrategyInterface
    {
        return app($this->getStrategyClass());
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
     * Get the storage path for this media type
     */
    public function getStoragePath(): string
    {
        $configPath = config('media.storage_path', 'media/{type}');
        return str_replace('{type}', $this->value, $configPath);
    }

    /**
     * Get validation rules for this media type
     */
    public function getValidationRules(): array
    {
        return config("media.validation.{$this->value}", []);
    }

    /**
     * Check if this media type supports thumbnails
     */
    public function supportsThumbnails(): bool
    {
        return match($this) {
            self::IMAGE => true,
            self::VIDEO, self::AUDIO, self::DOCUMENT => false,
        };
    }

    /**
     * Get all enabled media types from configuration
     */
    public static function getEnabled(): array
    {
        $enabledTypes = config('media.enabled_types', ['image', 'video', 'audio', 'document']);
        
        return array_filter(self::cases(), function($case) use ($enabledTypes) {
            return in_array($case->value, $enabledTypes);
        });
    }

    /**
     * Get all enabled media types as associative array [value => label]
     */
    public static function getEnabledOptions(): array
    {
        return array_reduce(self::getEnabled(), function($carry, $case) {
            $carry[$case->value] = $case->getLabel();
            return $carry;
        }, []);
    }

    /**
     * Get all enabled media types as array with value and label structure for API
     */
    public static function getEnabledForApi(): array
    {
        return array_map(function($case) {
            return [
                'value' => $case->value,
                'label' => $case->getLabel()
            ];
        }, self::getEnabled());
    }

    /**
     * Get all enabled strategies mapped by type
     */
    public static function getEnabledStrategies(): array
    {
        return array_reduce(self::getEnabled(), function($carry, $case) {
            $carry[$case->value] = $case->getStrategy();
            return $carry;
        }, []);
    }

    /**
     * Check if a given type is enabled
     */
    public static function isEnabled(string $type): bool
    {
        $enabledTypes = config('media.enabled_types', ['image', 'video', 'audio', 'document']);
        return in_array($type, $enabledTypes);
    }

    /**
     * Get the default media type
     */
    public static function getDefault(): self
    {
        $enabled = self::getEnabled();
        return !empty($enabled) ? $enabled[0] : self::IMAGE;
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

    /**
     * Get the maximum file size for this media type (in KB)
     */
    public function getMaxFileSize(): int
    {
        return match($this) {
            self::IMAGE => 5120,    // 5MB
            self::VIDEO => 20480,   // 20MB
            self::AUDIO => 10240,   // 10MB
            self::DOCUMENT => 10240, // 10MB
        };
    }
}