<?php

namespace Carone\Media\Utilities;

use Carone\Media\Models\MediaResource;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Utility class for resolving the configured media model
 *
 * This class provides a centralized way to access the media model class
 * as configured in the media.php config file, with proper validation.
 */
class MediaModel
{
    private static ?string $resolvedModel = null;

    /**
     * Get the configured media model class
     */
    public static function getClass(): string
    {
        if (self::$resolvedModel === null) {
            self::$resolvedModel = self::resolveModel();
        }

        return self::$resolvedModel;
    }

    /**
     * Create a new instance of the configured media model
     */
    public static function make(array $attributes = []): Model
    {
        $modelClass = self::getClass();
        return new $modelClass($attributes);
    }

    /**
     * Create and save a new instance of the configured media model
     */
    public static function create(array $attributes = []): Model
    {
        $modelClass = self::getClass();
        return $modelClass::create($attributes);
    }

    /**
     * Get a query builder for the configured media model
     */
    public static function query()
    {
        $modelClass = self::getClass();
        return $modelClass::query();
    }

    /**
     * Find a model by ID or fail
     */
    public static function findOrFail($id): Model
    {
        $modelClass = self::getClass();
        return $modelClass::findOrFail($id);
    }

    /**
     * Apply a where clause to the model
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $modelClass = self::getClass();
        return $modelClass::where($column, $operator, $value, $boolean);
    }

    /**
     * Validate that the configured model is valid
     */
    public static function validateModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class [{$modelClass}] does not exist.");
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("Model class [{$modelClass}] must extend Illuminate\\Database\\Eloquent\\Model.");
        }

        if (!is_subclass_of($modelClass, MediaResource::class) && $modelClass !== MediaResource::class) {
            throw new InvalidArgumentException("Model class [{$modelClass}] must extend Carone\\Media\\Models\\MediaResource.");
        }
    }

    /**
     * Resolve the model class from config with validation
     */
    private static function resolveModel(): string
    {
        $modelClass = config('media.model', MediaResource::class);

        if (!is_string($modelClass)) {
            throw new InvalidArgumentException('Media model configuration must be a string class name.');
        }

        self::validateModel($modelClass);

        return $modelClass;
    }

    /**
     * Reset the resolved model (useful for testing)
     */
    public static function reset(): void
    {
        self::$resolvedModel = null;
    }
}