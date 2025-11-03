<?php

namespace Carone\Media\Services;

use Carone\Media\Processing\MediaProcessor;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;

/**
 * Abstract base class for all media services
 * Provides common functionality and utilities
 */
abstract class MediaService
{
    protected function getProcessor(): MediaProcessor
    {
        return app(MediaProcessor::class);
    }

    /**
     * Validate uploaded file against media type constraints
     */
    protected function validateFile(UploadedFile $file, MediaType $mediaType): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $bannedTypes = config('media.banned_file_types', []);

        if (in_array($extension, $bannedTypes)) {
            throw new \InvalidArgumentException("File type '.{$extension}' is not allowed");
        }

        if (!in_array($extension, $mediaType->getSupportedExtensions())) {
            $supportedTypes = implode(', ', $mediaType->getSupportedExtensions());
            throw new \InvalidArgumentException("File extension '.{$extension}' is not supported for {$mediaType->getLabel()}. Supported types: {$supportedTypes}");
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $mediaType->getSupportedMimeTypes())) {
            throw new \InvalidArgumentException("MIME type '{$mimeType}' is not supported for {$mediaType->getLabel()}");
        }
    }

    /**
     * Get file size in human readable format
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
