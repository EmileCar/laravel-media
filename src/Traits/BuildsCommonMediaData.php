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
    protected ?string $thumbnailUrl = null;
    protected ?string $thumbnailPath = null;
    protected mixed $thumbnailFile = null;
    protected ?string $disk = null;
    protected ?string $directory = null;
    protected ?array $processingConfig = null;

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

    /**
     * Set an external thumbnail URL
     */
    public function withThumbnailUrl(string $url): self
    {
        $this->thumbnailUrl = $url;
        return $this;
    }

    /**
     * Set a thumbnail from a local file path (relative to storage)
     */
    public function withThumbnailPath(string $path): self
    {
        $this->thumbnailPath = $path;
        return $this;
    }

    /**
     * Set a thumbnail from an uploaded file
     */
    public function withThumbnailFile($file): self
    {
        $this->thumbnailFile = $file;
        return $this;
    }

    /**
     * Override the default disk
     */
    public function useDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Override the default directory
     */
    public function useDirectory(?string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * Override processing configuration
     */
    public function withProcessingConfig(array $config): self
    {
        $this->processingConfig = $config;
        return $this;
    }
}

