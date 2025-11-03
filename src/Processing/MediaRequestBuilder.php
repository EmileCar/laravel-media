<?php

namespace Carone\Media\Processing;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;

/**
 * Builder for creating media requests
 */
class MediaRequestBuilder
{
    /**
     * Create a builder for local media
     */
    public static function forLocalFile(UploadedFile $file): LocalMediaRequestBuilder
    {
        return new LocalMediaRequestBuilder($file);
    }

    /**
     * Create a builder for external media
     */
    public static function forExternalUrl(string $url): ExternalMediaRequestBuilder
    {
        return new ExternalMediaRequestBuilder($url);
    }
}

class LocalMediaRequestBuilder
{
    private UploadedFile $file;
    private ?MediaType $type = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?CarbonInterface $date = null;
    private ?string $fileName = null;
    private ?string $directory = null;
    private ?string $disk = null;
    private bool $generateThumbnail = false;

    public function __construct(UploadedFile $file)
    {
        $this->file = $file;
    }

    public function type(MediaType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function name(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function date(?CarbonInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function fileName(?string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function directory(?string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    public function disk(?string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    public function withThumbnail(bool $generateThumbnail = true): self
    {
        $this->generateThumbnail = $generateThumbnail;
        return $this;
    }

    public function build(): LocalMediaRequest
    {
        $type = $this->type ?? $this->autoDetectType();

        return new LocalMediaRequest(
            type: $type,
            file: $this->file,
            name: $this->name,
            description: $this->description,
            date: $this->date ?? Carbon::now(),
            fileName: $this->fileName,
            directory: $this->directory,
            disk: $this->disk,
            generateThumbnail: $this->generateThumbnail,
        );
    }

    private function autoDetectType(): MediaType
    {
        return MediaUtilities::autoDetectTypeFromExtension(
            $this->file->getClientOriginalExtension()
        );
    }
}

class ExternalMediaRequestBuilder
{
    private string $url;
    private ?MediaType $type = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?CarbonInterface $date = null;
    private ?array $meta = null;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function type(MediaType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function name(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function date(?CarbonInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function meta(?array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function build(): ExternalMediaRequest
    {
        $type = $this->type ?? $this->autoDetectType();

        return new ExternalMediaRequest(
            type: $type,
            url: $this->url,
            name: $this->name,
            description: $this->description,
            date: $this->date ?? Carbon::now(),
            meta: $this->meta,
        );
    }

    private function autoDetectType(): MediaType
    {
        $extension = pathinfo(parse_url($this->url, PHP_URL_PATH), PATHINFO_EXTENSION);
        return MediaUtilities::autoDetectTypeFromExtension($extension);
    }
}
