<?php

namespace Carone\Media\Processing;

use Carbon\CarbonInterface;
use Carone\Media\ValueObjects\MediaType;

/**
 * Request object for storing external media references
 */
readonly class ExternalMediaRequest
{
    public function __construct(
        public MediaType $type,
        public string $url,
        public ?string $name = null,
        public ?string $description = null,
        public ?CarbonInterface $date = null,
        public ?array $meta = null,
    ) {}

    /**
     * Get validation rules for this request
     */
    public function getValidationRules(): array
    {
        return [
            'type' => 'required|string',
            'url' => 'required|url|max:1000',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date' => 'nullable|date',
            'meta' => 'nullable|array',
        ];
    }

    /**
     * Convert to array for validation
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'url' => $this->url,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date?->toDateString(),
            'meta' => $this->meta,
        ];
    }
}
