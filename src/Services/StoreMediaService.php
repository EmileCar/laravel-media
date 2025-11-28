<?php

namespace Carone\Media\Services;

use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\UploadStrategies\UploadImageStrategy;
use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\StoreMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class StoreMediaService implements StoreMediaServiceInterface
{
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

        $strategy = $this->getUploadStrategy($data);
        return $data->storeWith($strategy);
    }

    private function getUploadStrategy(StoreMediaData $data): UploadMediaStrategy
    {
        return match ($data->type) {
            MediaType::IMAGE => new UploadImageStrategy($data),
            default => new UploadMediaStrategy($data),
        };
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
            throw new \InvalidArgumentException("File extension '.{$extension}' is not supported for {$mediaType->value}. Supported types: {$supportedTypes}");
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $mediaType->getSupportedMimeTypes())) {
            throw new \InvalidArgumentException("MIME type '{$mimeType}' is not supported for {$mediaType->value}");
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
