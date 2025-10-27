<?php

namespace Carone\Media\Services;

use Carone\Common\BulkOperations\BulkOperation;
use Carone\Common\BulkOperations\BulkOperationResult;
use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;

class DeleteMediaService implements DeleteMediaServiceInterface
{
    public function __construct() {}

    public function delete(int $id): bool
    {
        $media = MediaModel::findOrFail($id);

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

        $query = MediaModel::where('type', $type);
        $mediaItemIds = $query->pluck('id');

        return $this->deleteMultiple($mediaItemIds->toArray());
    }
}
