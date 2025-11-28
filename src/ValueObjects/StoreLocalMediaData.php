<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\UploadStrategies\UploadMediaStrategy;
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
        array $meta = [],
        public readonly ?string $directory = null,
        public readonly ?string $disk = null,
        public readonly bool $generateThumbnail = false,
        public readonly ?string $thumbnailUrl = null,
        public readonly ?string $thumbnailPath = null,
        public readonly mixed $thumbnailFile = null,
        public readonly ?array $processingConfig = null,
    ) {
        parent::__construct($type, $name, $description, $date, $meta);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'file' => $this->file,
            'file_name' => $this->fileName ?? $this->file->getClientOriginalName(),
            'directory' => $this->directory,
            'disk' => $this->disk,
            'generate_thumbnail' => $this->generateThumbnail,
            'thumbnail_url' => $this->thumbnailUrl,
            'thumbnail_path' => $this->thumbnailPath,
            'thumbnail_file' => $this->thumbnailFile,
            'processing_config' => $this->processingConfig,
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::baseRules(), [
            'file' => 'required|file',
            'file_name' => 'nullable|string|max:255',
            'directory' => 'nullable|string|max:500',
            'disk' => 'nullable|string|max:255',
            'generate_thumbnail' => 'boolean',
            'thumbnail_url' => 'nullable|url|max:1000',
            'thumbnail_path' => 'nullable|string|max:500',
            'thumbnail_file' => 'nullable|file',
        ]);
    }

    public function storeWith(UploadMediaStrategy $strategy): MediaResource
    {
        return $strategy->storeLocalFile($this);
    }
}
