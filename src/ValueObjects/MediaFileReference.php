<?php

namespace Carone\Media\ValueObjects;

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

    public function getFullPath(): string
    {
        $dir = trim($this->directory, '/');

        if ($dir === '') {
            return $this->getFileNameWithExtension();
        }

        return $dir . '/' . $this->getFileNameWithExtension();
    }
}