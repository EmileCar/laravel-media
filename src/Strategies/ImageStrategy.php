<?php

namespace Carone\Media\Strategies;

use App\Strategies\MediaStrategy;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


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
            'meta' => array_merge($data->meta ?? [], [
                'original_name' => $data->file->getClientOriginalName(),
                'size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
            ]),
        ]);
    }

    public function storeExternalFile(StoreExternalMediaData $data): MediaResource
    {
        return MediaResource::create([
            'type' => $this->getType()->value,
            'source' => 'external',
            'url' => $data->url,
            'display_name' => $data->name,
            'description' => $data->description,
            'date' => $data->date,
            'meta' => array_merge($data->meta ?? [], [
                'host' => parse_url($data->url, PHP_URL_HOST),
            ]),
        ]);
    }

    public function getMediaFile(MediaResource $media): BinaryFileResponse
    {
        $fileReference = $media->loadFileReference();
        if (!MediaStorageHelper::doesFileExist($fileReference->disk, $fileReference->getFullPath())) {
            abort(404, 'Media file not found');
        }

        $path = MediaStorageHelper::getPhysicalPath($fileReference);
        $mimeType = MediaUtilities::getMimeType($fileReference->extension, 'image/jpeg');

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}