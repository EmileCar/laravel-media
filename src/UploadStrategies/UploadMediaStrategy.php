<?php

namespace Carone\Media\UploadStrategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
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

        // Determine if we should auto-generate thumbnail
        $shouldGenerateThumbnail = $data->generateThumbnail
            || (config('media.thumbnails.auto_generate_for_images', false) && $data->type === MediaType::IMAGE);

        $thumbnailData = $this->handleThumbnail($data, $fileReference, $shouldGenerateThumbnail);

        $model = MediaModel::create([
            'type' => $data->type->value,
            'source' => 'local',
            'path' => $fileReference->getPath(),
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => array_merge([
                'original_name' => $data->file->getClientOriginalName(),
                'size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
            ], $data->meta ?? []),
            'thumbnail_path' => $thumbnailData['path'] ?? null,
            'thumbnail_url' => $thumbnailData['url'] ?? null,
        ]);

        return $model;
    }

    public function storeExternalFile(StoreExternalMediaData $data): MediaResource
    {
        $thumbnailData = $this->handleExternalThumbnail($data);

        return MediaModel::create([
            'type' => $data->type->value,
            'source' => 'external',
            'url' => $data->url,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => array_merge([
                'host' => parse_url($data->url, PHP_URL_HOST),
            ], $data->meta ?? []),
            'thumbnail_path' => $thumbnailData['path'] ?? null,
            'thumbnail_url' => $thumbnailData['url'] ?? null,
        ]);
    }

    /**
     * Handle thumbnail for local media
     */
    protected function handleThumbnail(StoreLocalMediaData $data, MediaFileReference $fileReference, bool $shouldGenerateThumbnail): array
    {
        $result = [];

        // Priority 1: Uploaded thumbnail file (highest priority)
        if (!empty($data->thumbnailFile)) {
            $thumbnailRef = $this->storeThumbnailFile($data->thumbnailFile, $fileReference);
            if ($thumbnailRef) {
                $result['path'] = $thumbnailRef->getPath();
            }
            // Also store URL if provided as a fallback
            if (!empty($data->thumbnailUrl)) {
                $result['url'] = $data->thumbnailUrl;
            }
            return $result;
        }

        // Priority 2: Explicit thumbnail URL
        if (!empty($data->thumbnailUrl)) {
            $result['url'] = $data->thumbnailUrl;
            return $result;
        }

        // Priority 3: Explicit thumbnail path
        if (!empty($data->thumbnailPath)) {
            $result['path'] = $data->thumbnailPath;
            return $result;
        }

        // Priority 4: Auto-generate thumbnail (for images only)
        if ($shouldGenerateThumbnail && config('media.thumbnails.enabled', true)) {
            $thumbnailRef = $this->generateThumbnail($fileReference);
            if ($thumbnailRef) {
                $result['path'] = $thumbnailRef->getPath();
            }
        }

        return $result;
    }

    /**
     * Handle thumbnail for external media
     */
    protected function handleExternalThumbnail(StoreExternalMediaData $data): array
    {
        $result = [];

        // Priority 1: Uploaded thumbnail file (highest priority)
        if (!empty($data->thumbnailFile)) {
            // Create a unique reference for the thumbnail
            $disk = config('media.thumbnails.disk') ?? config('media.disk');
            $directory = 'external-thumbnails';
            $filename = uniqid('thumb_', true);

            $thumbnailRef = $this->putSuppliedThumbnail($data->thumbnailFile, $filename, $disk, $directory);

            $result['path'] = $thumbnailRef->getPath();

            // Also store URL if provided as a fallback
            if (!empty($data->thumbnailUrl)) {
                $result['url'] = $data->thumbnailUrl;
            }
            return $result;
        }

        // Priority 2: Explicit thumbnail URL
        if (!empty($data->thumbnailUrl)) {
            $result['url'] = $data->thumbnailUrl;
            return $result;
        }

        // Priority 3: Explicit thumbnail path (less common for external media)
        if (!empty($data->thumbnailPath)) {
            $result['path'] = $data->thumbnailPath;
            return $result;
        }

        return $result;
    }

    /**
     * Store an uploaded thumbnail file
     */
    protected function storeThumbnailFile($thumbnailFile, MediaFileReference $mainFileReference): ?MediaFileReference
    {
        try {
            $disk = config('media.thumbnails.disk') ?? config('media.disk');
            $directory = $mainFileReference->directory;
            $filename = $mainFileReference->filename . '_thumb';

            return $this->putSuppliedThumbnail($thumbnailFile, $filename, $disk, $directory);
        } catch (\Exception $e) {
            \Log::warning('Failed to store thumbnail file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store a supplied thumbnail file (e.g. a video poster frame generated in the browser).
     * When 'media.thumbnails.process_supplied' is enabled, the file is resized/converted
     * with the 'media.processing.thumbnail' settings; otherwise it is stored as-is.
     */
    protected function putSuppliedThumbnail($thumbnailFile, string $filename, string $disk, string $directory): MediaFileReference
    {
        $thumbnailConfig = config('media.processing.thumbnail', []);

        if (config('media.thumbnails.process_supplied', false) && !empty($thumbnailConfig)) {
            $extension = $thumbnailConfig['convert_format'] ?? 'jpg';
            $thumbnailRef = new MediaFileReference($filename, $extension, $disk, $directory);

            try {
                ImageProcessor::generateThumbnail($thumbnailFile->getRealPath(), $thumbnailRef, $thumbnailConfig);
                return $thumbnailRef;
            } catch (\Throwable $e) {
                \Log::warning('Failed to process supplied thumbnail, storing original: ' . $e->getMessage());
            }
        }

        $extension = strtolower($thumbnailFile->getClientOriginalExtension()) ?: 'jpg';
        $thumbnailRef = new MediaFileReference($filename, $extension, $disk, $directory);
        MediaStorageHelper::storeThumbnailFile($thumbnailRef, file_get_contents($thumbnailFile->getRealPath()));

        return $thumbnailRef;
    }

    public function getMediaFile(MediaResource $media): BinaryFileResponse
    {
        $fileReference = $media->loadFileReference();
        if (!MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getStoragePath())) {
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
