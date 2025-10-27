<?php

namespace Carone\Media\Traits;

use Carbon\CarbonInterface;
use Carone\Media\ValueObjects\MediaType;

trait BuildsCommonMediaData
{
    protected ?MediaType $type = null;
    protected ?string $name = null;
    protected ?string $description = null;
    protected ?CarbonInterface $date = null;
    protected array $meta = [];

    public function forType(MediaType|string $type): self
    {
        $type = $type instanceof MediaType ? $type : MediaType::tryFrom($type);
        if (!$type) {
            throw new \InvalidArgumentException("Invalid media type: {$type}");
        }
        $this->type = $type;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function withDate(CarbonInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    public function addMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }
}

