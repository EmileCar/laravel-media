<?php

namespace Carone\Media\Strategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class MediaStrategy
{
    abstract public function getType(): MediaType;

    protected function processFile(UploadedFile $file): ?string
    {
        return null;
    }

    protected function generateThumbnail(MediaFileReference $fileReference): ?MediaFileReference
    {
        return null;
    }

    public function storeLocalFile(StoreLocalMediaData $data): MediaResource
    {
        $fileReference = $this->createUniqueFileReference($data);

        $processedPath = $this->processFile($data->file);

        try {
            $finalPath = $processedPath ?: $data->file->getRealPath();
            MediaStorageHelper::storeFile($fileReference, file_get_contents($finalPath));
        } finally {
            if ($processedPath && file_exists($processedPath)) {
                @unlink($processedPath);
            }
        }

        $model = MediaModel::create([
            'type' => $this->getType()->value,
            'source' => 'local',
            'path' => $fileReference->getPath(),
            'disk' => $fileReference->disk,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => [
                'original_name' => $data->file->getClientOriginalName(),
                'size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
            ],
        ]);

        if ($data->generateThumbnail) {
            $this->generateThumbnail($fileReference);
        }

        return $model;
    }

    public function storeExternalFile(StoreExternalMediaData $data): MediaResource
    {
        return MediaModel::create([
            'type' => $this->getType()->value,
            'source' => 'external',
            'url' => $data->url,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => array_merge($data->meta ?? [], [
                'host' => parse_url($data->url, PHP_URL_HOST),
            ]),
        ]);
    }

    public function getMediaFile(MediaResource $media): BinaryFileResponse
    {
        $fileReference = $media->loadFileReference();
        if (!MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getPath())) {
            abort(404, 'Media file not found');
        }

        $path = MediaStorageHelper::getPhysicalPath($fileReference);
        $mimeType = MediaUtilities::getMimeType($fileReference->extension, 'video/mp4');

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Create a unique file reference for the given local media data.
     * @param StoreLocalMediaData $data
     * @throws \InvalidArgumentException if a user-specified filename already exists.
     * @return MediaFileReference
     */
    protected function createUniqueFileReference(StoreLocalMediaData $data): MediaFileReference // use interface?
    {
        $storageBase = MediaStorageHelper::resolveStoragePath($data->directory);
        $diskName = $data->disk ?? config('media.disk', 'public');

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

        return new MediaFileReference($finalName, $extension, $diskName, $data->directory);
    }
}
