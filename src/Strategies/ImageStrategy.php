<?php

namespace Carone\Media\Strategies;

use Carone\Media\Contracts\MediaUploadStrategyInterface;
use Carone\Media\Contracts\MediaRetrievalStrategyInterface;
use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageStrategy implements MediaUploadStrategyInterface, MediaRetrievalStrategyInterface
{
    public function getType(): MediaType
    {
        return MediaType::IMAGE;
    }

    public function supports(UploadedFile $file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $allowedMimes);
    }

    public function upload(UploadedFile $file, array $data): MediaResource
    {
        $storagePath = MediaUtilities::getStoragePath($this->getType());
        $disk = config('media.disk', 'public');
        
        // Ensure directory exists
        Storage::disk($disk)->makeDirectory($storagePath);
        Storage::disk($disk)->makeDirectory($storagePath . '/thumbnails');

        // Generate filename
        $baseName = $data['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = MediaUtilities::generateUniqueFilename($storagePath, $baseName, 'jpg', $disk);

        // Process and store image as JPEG
        $image = Image::read($file)->encode(new JpegEncoder(90));
        Storage::disk($disk)->put($storagePath . '/' . $filename, (string) $image);

        // Create thumbnail if enabled
        if (config('media.generate_thumbnails', true)) {
            MediaUtilities::createThumbnail($image, $this->getType(), $filename, $disk);
        }

        return MediaResource::create([
            'type' => $this->getType(),
            'source' => 'local',
            'file_name' => $filename,
            'path' => $storagePath . '/' . $filename,
            'name' => $data['name'] ?? $baseName,
            'description' => $data['description'] ?? null,
            'date' => $data['date'] ?? now()->toDateString(),
            'meta' => array_merge($data['meta'] ?? [], [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]),
        ]);
    }

    public function uploadExternal(string $url, array $data): MediaResource
    {
        return MediaResource::create([
            'type' => $this->getType(),
            'source' => 'external',
            'url' => $url,
            'name' => $data['name'] ?? 'External Image',
            'description' => $data['description'] ?? null,
            'date' => $data['date'] ?? now()->toDateString(),
            'meta' => $data['meta'] ?? [],
        ]);
    }

    public function getMedia(MediaResource $media): BinaryFileResponse
    {
        if ($media->source === 'external') {
            abort(404, 'External media cannot be served directly');
        }

        $disk = config('media.disk', 'public');
        $path = $media->path ?? MediaUtilities::getStoragePath($media->type) . '/' . $media->file_name;

        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'Media file not found');
        }

        $fullPath = Storage::disk($disk)->path($path);
        $mimeType = MediaUtilities::getMimeType($media->file_name, 'image/jpeg');

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function getThumbnail(MediaResource $media): ?BinaryFileResponse
    {
        if ($media->source === 'external') {
            return null;
        }

        $disk = config('media.disk', 'public');
        $thumbnailPath = MediaUtilities::getThumbnailPath($media->type, $media->file_name);

        if (!Storage::disk($disk)->exists($thumbnailPath)) {
            return null;
        }

        $fullPath = Storage::disk($disk)->path($thumbnailPath);

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function supportsThumbnails(): bool
    {
        return true;
    }

    public function getApiData(MediaResource $media): array
    {
        $data = [
            'id' => $media->id,
            'name' => $media->name,
            'description' => $media->description,
            'date' => $media->date,
            'type' => $media->type,
            'source' => $media->source,
        ];

        if ($media->source === 'external') {
            $data['url'] = $media->url;
        } else {
            $data['original'] = $media->file_name;
            if ($this->supportsThumbnails()) {
                $data['thumbnail'] = pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
            }
        }

        return $data;
    }
}