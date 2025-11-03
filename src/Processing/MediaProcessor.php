<?php

namespace Carone\Media\Processing;

use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Processing\Plugins\ImageProcessingPlugin;
use Illuminate\Http\UploadedFile;

/**
 * Unified media processor that handles all media types
 * with optional type-specific processing plugins
 */
class MediaProcessor
{
    private array $plugins = [];

    public function __construct()
    {
        $this->plugins[MediaType::IMAGE->value] = new ImageProcessingPlugin();
    }

    /**
     * Process and store a local media file
     */
    public function processLocalFile(LocalMediaRequest $request): MediaResource
    {
        $fileReference = $this->createUniqueFileReference($request);

        // Apply type-specific processing if available
        $processedPath = $this->applyProcessing($request->file, $request->type);

        try {
            $finalPath = $processedPath ?: $request->file->getRealPath();
            MediaStorageHelper::storeFile($fileReference, file_get_contents($finalPath));
        } finally {
            if ($processedPath && file_exists($processedPath)) {
                @unlink($processedPath);
            }
        }

        $meta = [
            'original_name' => $request->file->getClientOriginalName(),
            'size' => $request->file->getSize(),
            'mime_type' => $request->file->getMimeType(),
            'processed' => $processedPath !== null,
            'final_extension' => $fileReference->extension,
        ];

        // Generate thumbnail if requested and supported
        if ($request->generateThumbnail && $this->supportsThumbnails($request->type)) {
            $thumbnailRef = $this->generateThumbnail($fileReference, $request->type);
            if ($thumbnailRef) {
                $meta['thumbnail_path'] = $thumbnailRef->getPath();
            }
        }

        return MediaModel::create([
            'type' => $request->type->value,
            'source' => 'local',
            'path' => $fileReference->getPath(),
            'disk' => $fileReference->disk,
            'display_name' => $request->name,
            'description' => $request->description,
            'date' => $request->date,
            'meta' => $meta,
        ]);
    }

    /**
     * Store external media reference
     */
    public function processExternalMedia(ExternalMediaRequest $request): MediaResource
    {
        return MediaModel::create([
            'type' => $request->type->value,
            'source' => 'external',
            'url' => $request->url,
            'display_name' => $request->name,
            'description' => $request->description,
            'date' => $request->date,
            'meta' => array_merge($request->meta ?? [], [
                'host' => parse_url($request->url, PHP_URL_HOST),
            ]),
        ]);
    }

    /**
     * Apply type-specific processing if a plugin exists
     */
    private function applyProcessing(UploadedFile $file, MediaType $type): ?string
    {
        if (!isset($this->plugins[$type->value])) {
            return null;
        }

        return $this->plugins[$type->value]->process($file);
    }

    /**
     * Generate thumbnail if type supports it
     */
    private function generateThumbnail(MediaFileReference $fileReference, MediaType $type): ?MediaFileReference
    {
        if (!isset($this->plugins[$type->value])) {
            return null;
        }

        return $this->plugins[$type->value]->generateThumbnail($fileReference);
    }

    /**
     * Check if type supports thumbnails
     */
    private function supportsThumbnails(MediaType $type): bool
    {
        return isset($this->plugins[$type->value])
            && method_exists($this->plugins[$type->value], 'generateThumbnail');
    }

    /**
     * Create a unique file reference for the media file
     */
    private function createUniqueFileReference(LocalMediaRequest $request): MediaFileReference
    {
        $storageBase = MediaStorageHelper::resolveStoragePath($request->directory);
        $diskName = $request->disk ?? config('media.disk', 'public');

        $extension = strtolower($request->file->getClientOriginalExtension());
        $base = $request->fileName ?? $request->name ?? pathinfo($request->file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = MediaStorageHelper::sanitizeFilename($base);

        // If user specified filename, it must not exist
        if ($request->fileName) {
            $fullPath = "{$storageBase}/{$base}.{$extension}";
            if (MediaStorageHelper::doesFileExist($diskName, $fullPath)) {
                throw new \InvalidArgumentException("File '{$base}.{$extension}' already exists in '{$storageBase}'.");
            }
            $finalName = $base;
        } else {
            // Auto-generate unique filename
            $finalName = pathinfo(
                MediaStorageHelper::generateUniqueFilename($diskName, $storageBase, $base, $extension),
                PATHINFO_FILENAME
            );
        }

        return new MediaFileReference($finalName, $extension, $diskName, $request->directory ?? '');
    }
}
