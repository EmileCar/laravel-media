<?php

namespace Carone\Media\Utilities;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageHelper
{

    public static function getPhysicalPath(MediaFileReference $fileReference): string
    {
        return Storage::disk($fileReference->disk)->path($fileReference->getFullPath());
    }

    public static function storeFile(MediaFileReference $fileReference, string $data): void
    {
        Storage::disk($fileReference->disk)->put($fileReference->directory . DIRECTORY_SEPARATOR . $fileReference->getFileNameWithExtension(), $data);
    }

    public static function deleteFile(MediaFileReference $fileReference): void
    {
        Storage::disk($fileReference->disk)->delete($fileReference->getFullPath());
    }

    /**
     * Check if a file exists in the given disk and path
     *
     * @param string $disk Name of the storage disk
     * @param string $path Path to the file within the disk
     * @return bool True if the file exists, false otherwise
     */
    public static function doesFileExist(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Sanitize a filename by creating a URL-friendly slug
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        $slug = Str::slug($filename);
        return $slug ?: uniqid('file_');
    }

    /**
     * Generate a unique filename in the given directory
     *
     * @param string $disk Name of disk to check
     * @param string $directory Directory path relative to disk
     * @param string $baseName Base name without extension
     * @param string $extension File extension
     * @return string Unique filename with extension
     */
    public static function generateUniqueFilename(string $disk, string $directory, string $baseName, string $extension): string
    {
        $disk = Storage::disk($disk);
        $filenameToSearch = "$baseName.$extension";
        $i = 1;
        while ($disk->exists($directory . DIRECTORY_SEPARATOR . $filenameToSearch)) {
            $filenameToSearch = "{$baseName}_{$i}.$extension";
            $i++;
        }
        return $filenameToSearch;
    }

    public static function resolveStoragePath(?string $path = null): string
    {
        $storageBase = config('media.storage_path', 'media/{path}');
        return str_replace('{path}', $path ?? '', $storageBase);
    }
}
