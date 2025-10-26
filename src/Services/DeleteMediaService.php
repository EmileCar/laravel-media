<?php

namespace Carone\Media\Services;

use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;

class DeleteMediaService implements DeleteMediaServiceInterface
{
    public function __construct() {}

    public function delete(int $id): bool
    {
        $media = MediaResource::findOrFail($id);

        try {
            if ($media->file_name && $media->source === 'local') {
                $disk = config('media.disk', 'public');
                MediaUtilities::deleteMediaFiles($media->type, $media->file_name, $disk);
            }

            $media->delete();

            return true;

        } catch (\Exception $e) {
            logger()->error('Media deletion error: ' . $e->getMessage(), [
                'media_id' => $id,
                'media_type' => $media->type,
                'media_file' => $media->file_name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function deleteMultiple(array $ids): array
    {
        $deleted = 0;
        $failed = [];

        foreach ($ids as $id) {
            try {
                $this->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    public function deleteByType(string $type, array $filters = []): array
    {
        if (!MediaType::isEnabled($type)) {
            throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
        }

        $query = MediaResource::where('type', $type);

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        $mediaItems = $query->get();
        $deleted = 0;
        $failed = 0;

        foreach ($mediaItems as $media) {
            try {
                $this->delete($media->id);
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
                logger()->error('Bulk deletion failed for media: ' . $e->getMessage(), [
                    'media_id' => $media->id,
                    'media_type' => $media->type,
                ]);
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    public function cleanupOrphanedFiles(string $type): array
    {
        if (!MediaType::isEnabled($type)) {
            throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
        }

        $disk = config('media.disk', 'public');
        $storagePath = MediaUtilities::getStoragePath($type);

        $cleaned = [];
        $errors = [];

        try {
            $storage = \Illuminate\Support\Facades\Storage::disk($disk);

            if (!$storage->exists($storagePath)) {
                return ['cleaned' => $cleaned, 'errors' => $errors];
            }

            $files = $storage->files($storagePath);
            $dbFiles = MediaResource::where('type', $type)
                ->where('source', 'local')
                ->pluck('file_name')
                ->toArray();

            foreach ($files as $file) {
                $filename = basename($file);

                if (strpos($file, '/thumbnails/') !== false) {
                    continue;
                }

                if (!in_array($filename, $dbFiles)) {
                    try {
                        $storage->delete($file);
                        $cleaned[] = $filename;

                        $thumbnailPath = MediaUtilities::getThumbnailPath($type, $filename);
                        if ($storage->exists($thumbnailPath)) {
                            $storage->delete($thumbnailPath);
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'file' => $filename,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = [
                'general' => $e->getMessage()
            ];
        }

        return [
            'cleaned' => $cleaned,
            'errors' => $errors,
        ];
    }
}
