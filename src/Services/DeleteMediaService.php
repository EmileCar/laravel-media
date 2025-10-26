<?php

namespace Carone\Media\Services;

use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaStorageHelper;

class DeleteMediaService implements DeleteMediaServiceInterface
{
    public function __construct() {}

    public function delete(int $id): bool
    {
        $media = MediaResource::findOrFail($id);

        try {
            if ($fileReference = $media->loadFileReference()) {
                MediaStorageHelper::deleteFile($fileReference);
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

    public function deleteByType(MediaType $type): array
    {
        if (!$type->isEnabled()) {
            throw new \InvalidArgumentException("Media type '{$type->value}' is not enabled");
        }

        $query = MediaResource::where('type', $type);


        $mediaItemIds = $query->pluck('id');

        return $this->deleteMultiple($mediaItemIds->toArray());
    }
}
