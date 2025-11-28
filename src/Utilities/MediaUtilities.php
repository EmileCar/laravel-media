<?php

namespace Carone\Media\Utilities;

use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Intervention\Image\Drivers\SpecializableEncoder;

class MediaUtilities
{
    /**
     * Get all enabled media types from configuration
     */
    public static function getEnabled(): array
    {
        $enabledTypes = config('media.enabled_types', []);

        return array_filter(MediaType::cases(), function($case) use ($enabledTypes) {
            return in_array($case->value, $enabledTypes);
        });
    }

    /**
     * Get MIME type for media serving
     *
     * @param string $filename
     * @param string $defaultType
     * @return string
     */
    public static function getMimeType(string $extension, string $defaultType = 'application/octet-stream'): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $mimeTypes[$extension] ?? $defaultType;
    }

    /**
     * Auto-detect media type from file extension
     */
    public static function autoDetectTypeFromExtension($extension): MediaType
    {
        $extension = strtolower($extension);

        $typeMap = [
            'jpg' => MediaType::IMAGE,
            'jpeg' => MediaType::IMAGE,
            'png' => MediaType::IMAGE,
            'gif' => MediaType::IMAGE,
            'webp' => MediaType::IMAGE,
            'mp4' => MediaType::VIDEO,
            'mov' => MediaType::VIDEO,
            'avi' => MediaType::VIDEO,
            'mp3' => MediaType::AUDIO,
            'wav' => MediaType::AUDIO,
            'pdf' => MediaType::DOCUMENT,
            'doc' => MediaType::DOCUMENT,
            'docx' => MediaType::DOCUMENT,
            'xls' => MediaType::DOCUMENT,
            'xlsx' => MediaType::DOCUMENT,
        ];

        if (isset($typeMap[$extension])) {
            return $typeMap[$extension];
        }

        throw new \InvalidArgumentException("Could not auto-detect media type for extension: {$extension}");
    }

    /**
     * Save image with specific format
     */
    public static function getPossibleEncoder(string $format, int $quality): SpecializableEncoder
    {
        return match (strtolower($format)) {
            'jpg', 'jpeg' => new \Intervention\Image\Encoders\JpegEncoder($quality),
            'png' => new \Intervention\Image\Encoders\PngEncoder(),
            'webp' => new \Intervention\Image\Encoders\WebpEncoder($quality),
            default => new \Intervention\Image\Encoders\JpegEncoder($quality),
        };
    }

    /**
     * Create a unique file reference for the given local media data.
     * @param \Carone\Media\ValueObjects\StoreLocalMediaData $data
     * @throws \InvalidArgumentException if a user-specified filename already exists.
     * @return MediaFileReference
     */
    public static function createUniqueFileReference(StoreLocalMediaData $data): MediaFileReference
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
