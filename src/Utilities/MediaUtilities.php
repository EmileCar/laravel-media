<?php

namespace Carone\Media\Utilities;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;

class MediaUtilities
{
    /**
     * Generate a unique filename in the given directory
     *
     * @param string $directory The disk-relative directory
     * @param string $baseName The base name for the file
     * @param string $extension The file extension
     * @param string $disk The storage disk to use
     * @return string
     */
    public static function generateUniqueFilename(string $directory, string $baseName, string $extension, string $disk = 'public'): string
    {
        $storage = Storage::disk($disk);
        $baseName = self::sanitizeFilename($baseName) ?: (string) time();
        $filename = $baseName . '.' . $extension;
        $counter = 1;

        while ($storage->exists($directory . '/' . $filename)) {
            $filename = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Sanitize a filename by creating a URL-friendly slug
     *
     * @param string $name
     * @return string
     */
    public static function sanitizeFilename(string $name): string
    {
        $slug = Str::slug($name);
        
        if (empty($slug)) {
            return uniqid('file_');
        }

        return $slug;
    }

    /**
     * Create a thumbnail for an image
     *
     * @param mixed $imageFile The image file (UploadedFile or Image instance)
     * @param string $type The media type
     * @param string $filename The original filename
     * @param string $disk The storage disk
     * @param int $width Thumbnail width
     * @param int $quality JPEG quality
     * @return void
     */
    public static function createThumbnail($imageFile, string $type, string $filename, string $disk = 'public', int $width = 200, int $quality = 60): void
    {
        try {
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $thumbnailPath = sprintf('media/%s/thumbnails/%s.jpg', $type, $baseName);

            // Ensure thumbnails directory exists
            Storage::disk($disk)->makeDirectory(dirname($thumbnailPath));

            $image = $imageFile instanceof \Intervention\Image\Image 
                ? $imageFile 
                : Image::read($imageFile);

            $image->scaleDown(width: $width);
            $jpegData = (string) $image->encode(new JpegEncoder($quality));

            Storage::disk($disk)->put($thumbnailPath, $jpegData);
        } catch (\Exception $e) {
            logger()->error('Failed to create thumbnail: ' . $e->getMessage(), [
                'type' => $type,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get storage path for media type
     *
     * @param string $type
     * @return string
     */
    public static function getStoragePath(string $type): string
    {
        $configPath = config('media.storage_path', 'media/{type}');
        return str_replace('{type}', $type, $configPath);
    }

    /**
     * Get thumbnail path for a media file
     *
     * @param string $type
     * @param string $filename
     * @return string
     */
    public static function getThumbnailPath(string $type, string $filename): string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        return sprintf('media/%s/thumbnails/%s.jpg', $type, $baseName);
    }

    /**
     * Delete media files and thumbnails
     *
     * @param string $type
     * @param string $filename
     * @param string $disk
     * @return void
     */
    public static function deleteMediaFiles(string $type, string $filename, string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);
        
        // Delete main file
        $mainPath = self::getStoragePath($type) . '/' . $filename;
        if ($storage->exists($mainPath)) {
            $storage->delete($mainPath);
        }

        // Delete thumbnail
        $thumbnailPath = self::getThumbnailPath($type, $filename);
        if ($storage->exists($thumbnailPath)) {
            $storage->delete($thumbnailPath);
        }
    }

    /**
     * Get MIME type for media serving
     *
     * @param string $filename
     * @param string $defaultType
     * @return string
     */
    public static function getMimeType(string $filename, string $defaultType = 'application/octet-stream'): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
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
}