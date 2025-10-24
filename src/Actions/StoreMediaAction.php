<?php

namespace Carone\Media\Actions;

use Carone\Media\Contracts\MediaUploadStrategyInterface;
use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class StoreMediaAction
{
    use AsAction;

    private array $strategies = [];

    public function __construct()
    {
        // Strategies will be injected via the service provider
    }

    public function setStrategies(array $strategies): void
    {
        $this->strategies = $strategies;
    }

    /**
     * Handle media upload
     *
     * @param array $data
     * @return MediaResource
     * @throws \InvalidArgumentException
     */
    public function handle(array $data): MediaResource
    {
        $this->validateData($data);

        $typeString = $data['type'];
        $source = $data['source'] ?? 'local';

        // Use the enum to validate and get the media type
        $mediaType = MediaType::tryFrom($typeString);
        if (!$mediaType) {
            throw new \InvalidArgumentException("Invalid media type '{$typeString}'");
        }

        if (!MediaType::isEnabled($typeString)) {
            throw new \InvalidArgumentException("Media type '{$typeString}' is not enabled");
        }

        $strategy = $this->getStrategy($typeString);

        if ($source === 'external') {
            if (empty($data['url'])) {
                throw new \InvalidArgumentException('URL is required for external media');
            }
            return $strategy->uploadExternal($data['url'], $data);
        }

        // Local upload
        if (empty($data['file']) || !($data['file'] instanceof UploadedFile)) {
            throw new \InvalidArgumentException('File is required for local uploads');
        }

        $file = $data['file'];

        // Validate file with strategy
        if (!$strategy->supports($file)) {
            throw new \InvalidArgumentException("File type not supported for {$typeString} media");
        }

        // Additional validation based on enum and config
        $this->validateFile($file, $mediaType);

        return $strategy->upload($file, $data);
    }

    /**
     * Get the appropriate strategy for the media type
     *
     * @param string $type
     * @return MediaUploadStrategyInterface
     * @throws \InvalidArgumentException
     */
    private function getStrategy(string $type): MediaUploadStrategyInterface
    {
        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException("No strategy found for media type: {$type}");
        }

        return $this->strategies[$type];
    }

    /**
     * Validate the input data
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        $enabledTypes = array_map(function($type) {
            return $type->value;
        }, MediaType::getEnabled());
        
        $rules = [
            'type' => 'required|string|in:' . implode(',', $enabledTypes),
            'source' => 'string|in:local,external',
            'name' => 'string|max:255',
            'description' => 'string|max:1000',
            'date' => 'date',
            'meta' => 'array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Validation failed: ' . $validator->errors()->first());
        }
    }

    /**
     * Validate file based on enum and configuration
     *
     * @param UploadedFile $file
     * @param MediaType $mediaType
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file, MediaType $mediaType): void
    {
        // Check banned file types
        $extension = strtolower($file->getClientOriginalExtension());
        $bannedTypes = config('media.banned_file_types', []);
        
        if (in_array($extension, $bannedTypes)) {
            throw new \InvalidArgumentException("File type '.{$extension}' is not allowed");
        }

        // Check if file extension is supported by this media type
        if (!in_array($extension, $mediaType->getSupportedExtensions())) {
            $supportedTypes = implode(', ', $mediaType->getSupportedExtensions());
            throw new \InvalidArgumentException("File extension '.{$extension}' is not supported for {$mediaType->getLabel()}. Supported types: {$supportedTypes}");
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $mediaType->getSupportedMimeTypes())) {
            throw new \InvalidArgumentException("MIME type '{$mimeType}' is not supported for {$mediaType->getLabel()}");
        }

        // Apply type-specific validation rules from config
        $validationRules = $mediaType->getValidationRules();
        
        if (!empty($validationRules)) {
            $validator = Validator::make(['file' => $file], ['file' => $validationRules]);
            
            if ($validator->fails()) {
                throw new \InvalidArgumentException('File validation failed: ' . $validator->errors()->first());
            }
        }
    }

    /**
     * Static method for easier usage
     *
     * @param array $data
     * @return MediaResource
     */
    public static function run(array $data): MediaResource
    {
        $action = app(static::class);
        return $action->handle($data);
    }
}