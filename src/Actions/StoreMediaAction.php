<?php

namespace Carone\Media\Actions;

use Carone\Media\Contracts\MediaUploadStrategyInterface;
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

        $type = $data['type'];
        $source = $data['source'] ?? 'local';

        if (!in_array($type, config('media.enabled_types', ['image', 'video', 'audio', 'document']))) {
            throw new \InvalidArgumentException("Media type '{$type}' is not enabled");
        }

        $strategy = $this->getStrategy($type);

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
            throw new \InvalidArgumentException("File type not supported for {$type} media");
        }

        // Additional validation based on config
        $this->validateFile($file, $type);

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
        $rules = [
            'type' => 'required|string|in:image,video,audio,document',
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
     * Validate file based on configuration
     *
     * @param UploadedFile $file
     * @param string $type
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file, string $type): void
    {
        // Check banned file types
        $extension = strtolower($file->getClientOriginalExtension());
        $bannedTypes = config('media.banned_file_types', []);
        
        if (in_array($extension, $bannedTypes)) {
            throw new \InvalidArgumentException("File type '.{$extension}' is not allowed");
        }

        // Apply type-specific validation rules
        $validationRules = config("media.validation.{$type}", []);
        
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