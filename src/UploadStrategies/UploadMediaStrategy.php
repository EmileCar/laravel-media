<?php

namespace Carone\Media\UploadStrategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\StoreMediaData;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadMediaStrategy
{
    protected StoreMediaData $data;

    public function __construct(StoreMediaData $data)
    {
        $this->data = $data;
    }

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
        $fileReference = MediaUtilities::createUniqueFileReference($data);

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
            'type' => $data->type,
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
            'type' => $data->type,
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
}
