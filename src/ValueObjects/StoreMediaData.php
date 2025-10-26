<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\Strategies\MediaStrategy;
use Carbon\CarbonInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaType;

abstract class StoreMediaData
{
    protected function __construct(
        public readonly MediaType $type,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?CarbonInterface $date,
    ) {}

    /** Base data for validation/serialization */
    protected function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->date->toDateString(),
        ];
    }

    /** Base rules that subclasses may extend */
    protected function baseRules(): array
    {
        $enabledTypes = array_map(fn($t) => $t->value, MediaUtilities::getEnabled());
        return [
            'type' => 'required|string|in:' . implode(',', $enabledTypes),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date',
        ];
    }

    /** Subclasses must implement their additional rules */
    abstract public function rules(): array;

    /** Polymorphic storage */
    abstract public function storeWith(MediaStrategy $strategy): MediaResource;
}
