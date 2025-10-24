<?php

namespace Carone\Media\Actions;

use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteMediaAction
{
    use AsAction;

    /**
     * Delete a media resource and its associated files
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function handle(int $id): bool
    {
        $media = MediaResource::findOrFail($id);

        try {
            // Delete files from storage if they exist and it's a local file
            if ($media->file_name && $media->source === 'local') {
                $disk = config('media.disk', 'public');
                MediaUtilities::deleteMediaFiles($media->type, $media->file_name, $disk);
            }

            // Delete database record
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

    /**
     * Delete multiple media resources
     *
     * @param array $ids
     * @return array ['deleted' => int, 'failed' => array]
     */
    public function deleteMultiple(array $ids): array
    {
        $deleted = 0;
        $failed = [];

        foreach ($ids as $id) {
            try {
                $this->handle($id);
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

    /**
     * Delete media by type (bulk operation)
     *
     * @param string $type
     * @param array $filters Optional filters (e.g., ['source' => 'external'])
     * @return array ['deleted' => int, 'failed' => int]
     */
    public function deleteByType(string $type, array $filters = []): array
    {
        if (!MediaType::isEnabled($type)) {
            throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
        }

        $query = MediaResource::where('type', $type);

        // Apply additional filters
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        $mediaItems = $query->get();
        $deleted = 0;
        $failed = 0;

        foreach ($mediaItems as $media) {
            try {
                $this->handle($media->id);
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

    /**
     * Clean up orphaned files (files that exist on disk but not in database)
     *
     * @param string $type
     * @return array ['cleaned' => array, 'errors' => array]
     */
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
                
                // Skip thumbnails directory
                if (strpos($file, '/thumbnails/') !== false) {
                    continue;
                }

                // If file is not in database, it's orphaned
                if (!in_array($filename, $dbFiles)) {
                    try {
                        $storage->delete($file);
                        $cleaned[] = $filename;
                        
                        // Also clean up potential thumbnail
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