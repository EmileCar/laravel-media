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