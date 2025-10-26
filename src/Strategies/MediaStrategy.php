<?php

namespace App\Strategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class MediaStrategy
{
    abstract public function getType(): MediaType;
    abstract public function getMediaFile(MediaResource $media): BinaryFileResponse;
    abstract public function storeLocalFile(StoreLocalMediaData $data): MediaResource;
    abstract public function storeExternalFile(StoreExternalMediaData $data): MediaResource;

    /**
     * Create a unique file reference for the given local media data.
     * @param StoreLocalMediaData $data
     * @throws \InvalidArgumentException if a user-specified filename already exists.
     * @return MediaFileReference
     */
    protected function createUniqueFileReference(StoreLocalMediaData $data): MediaFileReference
    {
        $storageBase = MediaStorageHelper::resolveStoragePath($data->directory);
        $diskName = config('media.disk', 'public');

        $extension = strtolower($data->file->getClientOriginalExtension());
        $base = $data->fileName ?? $data->name ?? pathinfo($data->file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = MediaStorageHelper::sanitizeFilename($base);

        // User explicitly set a filename —> must fail if it exists
        // Else, auto-generate a unique filename
        if ($data->fileName) {
            $fullPath = "{$storageBase}/{$base}.{$extension}";
            if (MediaStorageHelper::doesFileExist($diskName, $fullPath)) {
                throw new \InvalidArgumentException("File '{$base}.{$extension}' already exists in '{$storageBase}'.");
            }
            $finalName = $base;
        } else {
            $finalName = pathinfo(
                MediaStorageHelper::generateUniqueFilename($diskName, $storageBase, $base, $extension),
                PATHINFO_FILENAME
            );
        }

        return new MediaFileReference($finalName, $extension, $diskName, $storageBase);
    }
}