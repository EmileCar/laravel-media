<?php

namespace Carone\Media\ValueObjects;

use App\Strategies\MediaStrategy;
use Carbon\CarbonInterface;
use Carone\Media\Models\MediaResource;
use Illuminate\Http\UploadedFile;
use Carone\Media\ValueObjects\MediaType;

abstract class StoreMediaData
{
    protected function __construct(
        public readonly MediaType $type,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly CarbonInterface $date,
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
        $enabledTypes = array_map(fn($t) => $t->value, MediaType::getEnabled());
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

final class StoreLocalMediaData extends StoreMediaData
{
    public function __construct(
        public readonly MediaType $type,
        public readonly UploadedFile $file,
        public readonly ?string $fileName,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?CarbonInterface $date,
        public readonly ?string $directory,
    ) {
        parent::__construct($type, $name, $description, $date);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'file' => $this->file,
            'file_name' => $this->fileName ?? $this->file->getClientOriginalName(),
            'directory' => $this->directory,
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'file' => 'required|file',
            'file_name' => 'nullable|string|max:255',
            'path' => 'nullable|string|max:500',
        ]);
    }

    public function storeWith(MediaStrategy $strategy): MediaResource
    {
        return $strategy->storeLocalFile($this);
    }
}

final class StoreExternalMediaData extends StoreMediaData
{
    public function __construct(
        public readonly MediaType $type,
        public readonly string $url,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?CarbonInterface $date,
    ) {
        parent::__construct($type, $name, $description, $date);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'url' => $this->url,
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'url' => 'required|url|max:1000',
        ]);
    }

    public function storeWith(MediaStrategy $strategy): MediaResource
    {
        return $strategy->storeExternalFile($this);
    }
}
