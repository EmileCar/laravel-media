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
        public string $directory,
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
}
