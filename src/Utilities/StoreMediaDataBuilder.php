<?php

namespace Carone\Media\Utilities;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\UploadedFile;

/**
 * Flexible builder for creating StoreMediaData objects
 *
 * Usage:
 * - StoreMediaDataBuilder::fromLocalSource($file)->type(MediaType::IMAGE)->name('My Image')->build()
 * - StoreMediaDataBuilder::fromExternalSource($url)->type(MediaType::VIDEO)->name('My Video')->build()
 */
class StoreMediaDataBuilder
{
    /**
     * Create a builder for local media storage
     */
    public static function fromLocalSource(UploadedFile $file): StoreLocalMediaDataBuilder
    {
        return new StoreLocalMediaDataBuilder($file);
    }

    /**
     * Create a builder for external media storage
     */
    public static function fromExternalSource(string $url): StoreExternalMediaDataBuilder
    {
        return new StoreExternalMediaDataBuilder($url);
    }
}

abstract class BaseStoreMediaDataBuilder
{
    protected ?MediaType $type = null;
    protected ?string $name = null;
    protected ?string $description = null;
    protected ?CarbonInterface $date = null;
    protected array $meta = [];

    abstract protected function autoDetectType(): MediaType;

    /**
     * Set the media type
     */
    public function forType(MediaType|string $type): self
    {
        $type = $type instanceof MediaType ? $type : MediaType::tryFrom($type);
        if (!$type) {
            throw new \InvalidArgumentException("Invalid media type: {$type}");
        }
        $this->type = $type;
        return $this;
    }


    /**
     * Set the display name
     */
    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the description
     */
    public function withDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the date
     */
    public function withDate(CarbonInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Add metadata
     */
    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * Add a single metadata key-value pair
     */
    public function addMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }
}

/**
 * Builder for local media storage
 */
class StoreLocalMediaDataBuilder extends BaseStoreMediaDataBuilder
{
    protected UploadedFile $file;
    protected ?string $fileName = null;
    protected ?string $directory = null;

    public function __construct(UploadedFile $file)
    {
        $this->file = $file;
    }

    /**
     * Set a custom filename (without extension)
     */
    public function useFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Set the directory path
     */
    public function useDirectory(?string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * Use original filename as display name if name is not set
     */
    public function useOriginalName(): self
    {
        if (!$this->name) {
            $this->name = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
        }
        return $this;
    }

    /**
     * Auto-detect media type from file extension
     */
    protected function autoDetectType(): MediaType
    {
        $type = MediaUtilities::autoDetectTypeFromExtension($this->file->getClientOriginalExtension());
        return $type;
    }

    /**
     * Build the StoreLocalMediaData object
     */
    public function build(): StoreLocalMediaData
    {
        return new StoreLocalMediaData(
            type: $this->type ?? $this->autoDetectType(),
            file: $this->file,
            fileName: $this->fileName,
            name: $this->name,
            description: $this->description,
            date: $this->date,
            directory: $this->directory
        );
    }
}

/**
 * Builder for external media storage
 */
class StoreExternalMediaDataBuilder extends BaseStoreMediaDataBuilder
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Use filename from URL as display name if name is not set
     */
    public function useUrlFilename(): self
    {
        if (!$this->name) {
            $parsedUrl = parse_url($this->url);
            $path = $parsedUrl['path'] ?? '';
            $filename = pathinfo($path, PATHINFO_FILENAME);

            if ($filename) {
                $this->name = $filename;
            }
        }
        return $this;
    }

    protected function autoDetectType(): MediaType
    {
        $extension = pathinfo(parse_url($this->url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $type = MediaUtilities::autoDetectTypeFromExtension($extension);
        return $type;
    }

    /**
     * Build the StoreExternalMediaData object
     */
    public function build(): StoreExternalMediaData
    {
        if (!$this->type) {
            throw new \InvalidArgumentException('Media type must be set. Use type(), typeFromString(), or autoDetectType()');
        }

        return new StoreExternalMediaData(
            type: $this->type,
            url: $this->url,
            name: $this->name,
            description: $this->description,
            date: $this->date
        );
    }
}