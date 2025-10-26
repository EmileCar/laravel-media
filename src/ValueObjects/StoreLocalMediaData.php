<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\Strategies\MediaStrategy;
use Carbon\CarbonInterface;
use Carone\Media\Models\MediaResource;
use Illuminate\Http\UploadedFile;
use Carone\Media\ValueObjects\MediaType;

final class StoreLocalMediaData extends StoreMediaData
{
    public function __construct(
        MediaType $type,
        public readonly UploadedFile $file,
        public readonly ?string $fileName,
        ?string $name,
        ?string $description,
        ?CarbonInterface $date,
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
        return array_merge(parent::baseRules(), [
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