<?php

namespace Carone\Media\ValueObjects;

use Carone\Media\Strategies\MediaStrategy;
use Carbon\CarbonInterface;
use Carone\Media\Models\MediaResource;
use Carone\Media\ValueObjects\MediaType;

final class StoreExternalMediaData extends StoreMediaData
{
    public function __construct(
        MediaType $type,
        public readonly string $url,
        string $name,
        ?string $description,
        ?CarbonInterface $date,
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
        return array_merge(parent::baseRules(), [
            'url' => 'required|url|max:1000',
        ]);
    }

    public function storeWith(MediaStrategy $strategy): MediaResource
    {
        return $strategy->storeExternalFile($this);
    }
}
