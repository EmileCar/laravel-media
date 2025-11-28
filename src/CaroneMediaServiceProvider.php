<?php

namespace Carone\Media;

use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Processing\MediaProcessor;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\MediaManager;
use Illuminate\Support\ServiceProvider;

class CaroneMediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->publishes([
            __DIR__ . '/../config/media.php' => config_path('media.php'),
        ], 'config');

        $this->validateMediaModel();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/media.php',
            'media'
        );

        $this->app->bind(StoreMediaServiceInterface::class, StoreMediaService::class);
        $this->app->bind(GetMediaServiceInterface::class, GetMediaService::class);
        $this->app->bind(DeleteMediaServiceInterface::class, DeleteMediaService::class);

        $this->app->singleton(MediaManager::class);
        $this->app->singleton('carone.media', MediaManager::class);
    }

    /**
     * Validate the configured media model
     */
    private function validateMediaModel(): void
    {
        try {
            MediaModel::getClass();
        } catch (\InvalidArgumentException $e) {
            // Log the error but don't break the application boot process
            if ($this->app->hasBeenBootstrapped()) {
                throw $e;
            }
            // During testing or console commands, we might want to be more lenient
            if (!$this->app->runningInConsole() && !$this->app->environment('testing')) {
                throw $e;
            }
        }
    }
}
