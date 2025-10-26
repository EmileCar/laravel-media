<?php

namespace Carone\Media\Contracts;

interface DeleteMediaServiceInterface
{
    public function delete(int $id): bool;

    public function deleteMultiple(array $ids): array;

    public function deleteByType(string $type, array $filters = []): array;

    public function cleanupOrphanedFiles(string $type): array;
}
