<?php

namespace Carone\Media\Contracts;

use Carone\Common\BulkOperations\BulkOperationResult;
use Carone\Media\ValueObjects\MediaType;

interface DeleteMediaServiceInterface
{
    /**
     * Delete media resource by ID
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Delete multiple media resources by their IDs
     *
     * @param array $ids
     * @return array
     */
    public function deleteMultiple(array $ids): BulkOperationResult;

    /**
     * Delete media resources by type
     *
     * @param MediaType $type
     * @return array
     */
    public function deleteByType(MediaType $type): BulkOperationResult;
}
