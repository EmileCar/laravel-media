<?php

namespace Carone\Media\Strategies;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\Models\MediaResource;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;


class ImageStrategy extends MediaStrategy
{
    public function getType(): MediaType
    {
        return MediaType::IMAGE;
    }

    public function storeLocalFile(StoreLocalMediaData $data): MediaResource
    {
        $fileReference = $this->createUniqueFileReference($data);

        $image = Image::read($data->file)->encode(new JpegEncoder(90));
        MediaStorageHelper::storeFile($fileReference, (string) $image);

        return MediaModel::create([
            'type' => $this->getType()->value,
            'source' => 'local',
            'path' => $fileReference->getPath(),
            'disk' => $fileReference->disk,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => array_merge($data->meta ?? [], [
                'original_name' => $data->file->getClientOriginalName(),
                'size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
            ]),
        ]);
    }
}
