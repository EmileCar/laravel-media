<?php

namespace Carone\Media\Services;

use Carone\Common\BulkOperations\BulkOperation;
use Carone\Common\BulkOperations\BulkOperationResult;
use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Services\MediaService;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaType;

class DeleteMediaService extends MediaService implements DeleteMediaServiceInterface
{
    public function delete(int $id): bool
    {
        $media = MediaModel::findOrFail($id);

        try {
            $fileReference = $media->loadFileReference();
            if (!empty($fileReference)) {
                MediaStorageHelper::deleteFile($fileReference);

                $thumbnailFileReference = $media->loadThumbnailFileReference();
                if (!empty($thumbnailFileReference)) {
                    MediaStorageHelper::deleteFile($thumbnailFileReference);
                }
            }

            $media->delete();
            return true;

        } catch (\Exception $e) {
            logger()->error('Media deletion error: ' . $e->getMessage(), [
                'media_id' => $id,
                'media_type' => $media->type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteMultiple(array $ids): BulkOperationResult
    {
        $bulkOperation = BulkOperation::create(function ($id) {
            return $this->delete($id);
        });

        return $bulkOperation->execute($ids);
    }

    public function deleteByType(MediaType $type): BulkOperationResult
    {
        if (!$type->isEnabled()) {
            throw new \InvalidArgumentException("Media type '{$type->value}' is not enabled");
        }

        $mediaItemIds = MediaModel::where('type', $type->value)->pluck('id');
        return $this->deleteMultiple($mediaItemIds->toArray());
    }
}
