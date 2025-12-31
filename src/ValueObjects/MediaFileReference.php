<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\Utilities\MediaStorageHelper;

/**
 * A reference to a file that is stored or to be stored
 */
final readonly class MediaFileReference
{
    public function __construct(
        public string $filename,
        public string $extension,
        public string $disk,
        public string $directory = '',
    ) {}

    /**
     * Create a MediaFileReference from a file path
     */
    public static function fromPath(string $path, string $disk): self
    {
        $pathInfo = pathinfo($path);

        return new self(
            filename: $pathInfo['filename'] ?? '',
            extension: $pathInfo['extension'] ?? '',
            disk: $disk,
            directory: ($pathInfo['dirname'] ?? '') !== '.' ? ($pathInfo['dirname'] ?? '') : '',
        );
    }

    /**
     * Create a MediaFileReference from a thumbnail path
     * This reference will use getThumbnailStoragePath() for proper resolution
     */
    public static function fromThumbnailPath(string $path, string $disk): self
    {
        return self::fromPath($path, $disk);
    }

    /**
     * Create a MediaFileReference from a file path
     */
    public static function forThumbnail(MediaFileReference $fileReference, string $thumbnailFileNameWithExtension): self
    {
        $pathInfo = pathinfo($thumbnailFileNameWithExtension);

        return new self(
            filename: $pathInfo['filename'] ?? '',
            extension: $pathInfo['extension'] ?? '',
            disk: $fileReference->disk,
            directory: $fileReference->directory,
        );
    }

    /**
     * Create a thumbnail file reference with proper disk and directory configuration
     */
    public static function createForThumbnail(MediaFileReference $originalFileReference, string $extension): self
    {
        $thumbnailDisk = config('media.thumbnails.disk') ?? $originalFileReference->disk;
        $thumbnailFilename = $originalFileReference->filename . '_thumb';

        // Keep the same directory structure as the original file
        // The storage_path config will handle the 'media/thumbnails/{path}' pattern
        $directory = $originalFileReference->directory;

        return new self(
            filename: $thumbnailFilename,
            extension: $extension,
            disk: $thumbnailDisk,
            directory: $directory,
        );
    }

    public function getFileNameWithExtension(): string
    {
        return "{$this->filename}.{$this->extension}";
    }

    /**
    * Get the path relative to the storage_path configuration
     * @return string
     */
    public function getPath(): string
    {
        $dir = trim($this->directory, '/');

        if ($dir === '') {
            return $this->getFileNameWithExtension();
        }

        return $dir . '/' . $this->getFileNameWithExtension();
    }

    /**
     * Get the full storage location path for this file reference
     * @return string
     */
    public function getStoragePath(): string
    {
        return MediaStorageHelper::resolveStoragePath($this->getPath());
    }

    /**
     * Get the full storage location path for thumbnails
     * @return string
     */
    public function getThumbnailStoragePath(): string
    {
        return MediaStorageHelper::resolveThumbnailStoragePath($this->getPath());
    }
}
