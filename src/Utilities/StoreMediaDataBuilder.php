<?php

namespace Carone\Media\Utilities;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carone\Media\Traits\BuildsCommonMediaData;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\UploadedFile;

/**
 * Flexible builder for creating StoreMediaData objects
 *
 * Usage:
 * - StoreMediaDataBuilder::fromFile($file)->type(MediaType::IMAGE)->name('My Image')->build()
 * - StoreMediaDataBuilder::fromExternalUrl($url)->type(MediaType::VIDEO)->name('My Video')->build()
 */
class StoreMediaDataBuilder
{
    /**
     * Create a builder for local media storage
     *
     * @param UploadedFile $file
     * @return \Carone\Media\Utilities\StoreLocalMediaDataBuilder
     */
    public static function fromFile(UploadedFile $file): StoreLocalMediaDataBuilder
    {
        return new StoreLocalMediaDataBuilder($file);
    }

    /**
     * Create a builder for external media storage
     *
     * @param string $url
     * @return \Carone\Media\Utilities\StoreExternalMediaDataBuilder
     */
    public static function fromExternalUrl(string $url): StoreExternalMediaDataBuilder
    {
        return new StoreExternalMediaDataBuilder($url);
    }
}

class StoreLocalMediaDataBuilder
{
    use BuildsCommonMediaData;

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

class StoreExternalMediaDataBuilder
{
    use BuildsCommonMediaData;

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