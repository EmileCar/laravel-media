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
        return self::MIME_TYPES[strtolower($extension)] ?? $defaultType;
    }

    /**
     * Content types used when serving files, by extension
     */
    private const MIME_TYPES = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'avif' => 'image/avif',
        'bmp' => 'image/bmp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        // Video
        'mp4' => 'video/mp4',
        'm4v' => 'video/x-m4v',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'webm' => 'video/webm',
        '3gp' => 'video/3gpp',
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'flac' => 'audio/flac',
        'opus' => 'audio/opus',
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'rtf' => 'application/rtf',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        // Other
        'zip' => 'application/zip',
        '7z' => 'application/x-7z-compressed',
        'rar' => 'application/vnd.rar',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        'gpx' => 'application/gpx+xml',
        'kml' => 'application/vnd.google-earth.kml+xml',
        'kmz' => 'application/vnd.google-earth.kmz',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'epub' => 'application/epub+zip',
        'eml' => 'message/rfc822',
        'vcf' => 'text/vcard',
        'ics' => 'text/calendar',
    ];

    /**
     * Auto-detect media type from file extension
     */
    public static function autoDetectTypeFromExtension($extension): MediaType
    {
        $type = MediaType::fromExtension((string) $extension);

        if ($type !== null) {
            return $type;
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

        return new MediaFileReference($finalName, $extension, $diskName, $data->directory ?? '');
    }
}
