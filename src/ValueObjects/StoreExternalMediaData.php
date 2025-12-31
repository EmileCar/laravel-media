<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carbon\CarbonInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\MediaType;

final class StoreExternalMediaData extends StoreMediaData
{
    public function __construct(
        MediaType $type,
        public readonly string $url,
        ?string $name,
        ?string $description,
        ?CarbonInterface $date,
        array $meta = [],
        public readonly ?string $thumbnailUrl = null,
        public readonly ?string $thumbnailPath = null,
        public readonly mixed $thumbnailFile = null,
        public readonly array $tags = [],
    ) {
        parent::__construct($type, $name, $description, $date, $meta);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnailUrl,
            'thumbnail_path' => $this->thumbnailPath,
            'thumbnail_file' => $this->thumbnailFile,
            'tags' => $this->tags,
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::baseRules(), [
            'url' => 'required|url|max:1000',
            'thumbnail_url' => 'nullable|url|max:1000',
            'thumbnail_path' => 'nullable|string|max:500',
            'thumbnail_file' => 'nullable|file',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
        ]);
    }

    public function storeWith(UploadMediaStrategy $strategy): MediaResource
    {
        return $strategy->storeExternalFile($this);
    }
}
