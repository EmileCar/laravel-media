<?php

namespace Carone\Media\Traits;

use Carone\Media\Models\MediaResource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Carone\Media\Utilities\MediaUtilities;

trait HasThumbnails
{
    public function getThumbnail(MediaResource $media): ?BinaryFileResponse
    {
        if ($media->source === 'external') {
            return null;
        }

        $disk = config('media.disk', 'public');
        $thumbnailPath = MediaUtilities::getThumbnailPath($media->type, $media->file_name);

        if (! Storage::disk($disk)->exists($thumbnailPath)) {
            return null;
        }

        $fullPath = Storage::disk($disk)->path($thumbnailPath);

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
