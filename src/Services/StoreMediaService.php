<?php

namespace Carone\Media\Services;

use Carone\Media\Services\MediaService;
use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\StoreMediaData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class StoreMediaService extends MediaService implements StoreMediaServiceInterface
{
    public function __construct() { }

    public function store(StoreMediaData $data): MediaResource
    {
        $rules = $data->rules();
        $validator = Validator::make($data->toArray(), $rules);
        if ($validator->fails()) {
            throw new \InvalidArgumentException('Validation failed: ' . $validator->errors()->first());
        }

        if ($data instanceof StoreLocalMediaData) {
            $this->validateFile($data->file, $data->type);
        }

        $strategy = $this->getStrategy($data->type);
        return $data->storeWith($strategy);
    }

    private function validateFile(UploadedFile $file, MediaType $mediaType): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $bannedTypes = config('media.banned_file_types', []);

        if (in_array($extension, $bannedTypes)) {
            throw new \InvalidArgumentException("File type '.{$extension}' is not allowed");
        }

        if (!in_array($extension, $mediaType->getSupportedExtensions())) {
            $supportedTypes = implode(', ', $mediaType->getSupportedExtensions());
            throw new \InvalidArgumentException("File extension '.{$extension}' is not supported for {$mediaType->getLabel()}. Supported types: {$supportedTypes}");
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $mediaType->getSupportedMimeTypes())) {
            throw new \InvalidArgumentException("MIME type '{$mimeType}' is not supported for {$mediaType->getLabel()}");
        }

        $validationRules = $mediaType->getValidationRules();

        if (!empty($validationRules)) {
            $validator = Validator::make(['file' => $file], ['file' => $validationRules]);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('File validation failed: ' . $validator->errors()->first());
            }
        }
    }
}
