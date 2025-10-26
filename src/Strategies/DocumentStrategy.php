<?php

namespace Carone\Media\Strategies;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\Models\MediaResource;

class DocumentStrategy extends MediaStrategy
{
    public function getType(): MediaType
    {
        return MediaType::DOCUMENT;
    }

    public function storeLocalFile(StoreLocalMediaData $data): MediaResource
    {
        $fileReference = $this->createUniqueFileReference($data);

        // Store the original document file without processing
        MediaStorageHelper::storeFile($fileReference, file_get_contents($data->file->getRealPath()));

        return MediaResource::create([
            'type' => $this->getType()->value,
            'source' => 'local',
            'file_name' => $fileReference->filename,
            'extension' => $fileReference->extension,
            'disk' => $fileReference->disk,
            'directory' => $fileReference->directory,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => [
                'original_name' => $data->file->getClientOriginalName(),
                'size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
            ],
        ]);
    }
}