<?php

namespace Carone\Media\Strategies;

use Carone\Media\Contracts\MediaUploadStrategyInterface;
use Carone\Media\Contracts\MediaRetrievalStrategyInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AudioStrategy implements MediaUploadStrategyInterface, MediaRetrievalStrategyInterface
{
    public function getType(): string
    {
        return 'audio';
    }

    public function supports(UploadedFile $file): bool
    {
        $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/mp3'];
        return in_array($file->getMimeType(), $allowedMimes);
    }

    public function upload(UploadedFile $file, array $data): MediaResource
    {
        $storagePath = MediaUtilities::getStoragePath($this->getType());
        $disk = config('media.disk', 'public');
        
        // Ensure directory exists
        Storage::disk($disk)->makeDirectory($storagePath);

        // Generate filename with original extension
        $baseName = $data['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'mp3';
        $filename = MediaUtilities::generateUniqueFilename($storagePath, $baseName, $extension, $disk);

        // Store the file
        $file->storeAs($storagePath, $filename, $disk);

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
            'name' => $data['name'] ?? 'External Audio',
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
        $mimeType = MediaUtilities::getMimeType($media->file_name, 'audio/mpeg');

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function getThumbnail(MediaResource $media): ?BinaryFileResponse
    {
        // Audio files don't support thumbnails
        return null;
    }

    public function supportsThumbnails(): bool
    {
        return false;
    }

    public function getApiData(MediaResource $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'description' => $media->description,
            'date' => $media->date,
            'type' => $media->type,
            'source' => $media->source,
            'file' => $media->file_name,
        ];
    }
}