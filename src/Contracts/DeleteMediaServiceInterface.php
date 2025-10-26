<?php

namespace Carone\Media\Contracts;

use Carone\Media\ValueObjects\MediaType;

interface DeleteMediaServiceInterface
{
    public function delete(int $id): bool;

    public function deleteMultiple(array $ids): array;

    public function deleteByType(MediaType $type): array;
}
