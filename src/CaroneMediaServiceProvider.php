<?php

namespace Carone\Media;

use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Enums\MediaType;
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
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/media.php',
            'media'
        );

        // Register strategy classes as singletons, but don't instantiate them yet
        foreach (MediaType::cases() as $mediaType) {
            $this->app->singleton($mediaType->getStrategyClass());
        }

        // Register services without pre-resolved strategies
        $this->app->singleton(StoreMediaServiceInterface::class, StoreMediaService::class);
        $this->app->singleton(GetMediaServiceInterface::class, GetMediaService::class);
        $this->app->singleton(DeleteMediaServiceInterface::class, DeleteMediaService::class);
        $this->app->singleton('carone.media', MediaManager::class);
    }
}
