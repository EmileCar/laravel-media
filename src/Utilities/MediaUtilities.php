<?php

namespace Carone\Media\Utilities;

use Carone\Media\ValueObjects\MediaType;

class MediaUtilities
{
    /**
     * Get all enabled media types from configuration
     */
    public static function getEnabled(): array
    {
        $enabledTypes = config('media.enabled_types', ['image', 'video', 'audio', 'document']);

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
}