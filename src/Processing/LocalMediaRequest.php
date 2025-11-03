<?php

namespace Carone\Media\Processing;

use Carbon\CarbonInterface;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;

/**
 * Request object for storing local media files
 */
readonly class LocalMediaRequest
{
    public function __construct(
        public MediaType $type,
        public UploadedFile $file,
        public ?string $name = null,
        public ?string $description = null,
        public ?CarbonInterface $date = null,
        public ?string $fileName = null,
        public ?string $directory = null,
        public ?string $disk = null,
        public bool $generateThumbnail = false,
    ) {}

    /**
     * Get validation rules for this request
     */
    public function getValidationRules(): array
    {
        $baseRules = [
            'type' => 'required|string',
            'file' => 'required|file',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date' => 'nullable|date',
            'fileName' => 'nullable|string|max:255',
            'directory' => 'nullable|string|max:500',
            'disk' => 'nullable|string|max:255',
            'generateThumbnail' => 'boolean',
        ];

        // Add type-specific validation rules
        $typeRules = $this->type->getValidationRules();
        if (!empty($typeRules)) {
            $baseRules['file'] = array_merge(
                ['required', 'file'],
                is_array($typeRules) ? $typeRules : [$typeRules]
            );
        }

        return $baseRules;
    }

    /**
     * Convert to array for validation
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'file' => $this->file,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date?->toDateString(),
            'fileName' => $this->fileName,
            'directory' => $this->directory,
            'disk' => $this->disk,
            'generateThumbnail' => $this->generateThumbnail,
        ];
    }
}
