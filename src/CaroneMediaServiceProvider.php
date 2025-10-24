<?php

namespace Carone\Media;

use Carone\Media\Actions\DeleteMediaAction;
use Carone\Media\Actions\GetMediaAction;
use Carone\Media\Actions\StoreMediaAction;
use Carone\Media\Enums\MediaType;
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

        $this->app->booted(function () {
            $this->registerStrategies();
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/media.php',
            'media'
        );

        // Register strategy classes dynamically from MediaType enum
        foreach (MediaType::cases() as $mediaType) {
            $strategyClass = $mediaType->getStrategyClass();
            $this->app->singleton($strategyClass);
        }

        $this->app->singleton(StoreMediaAction::class);
        $this->app->singleton(GetMediaAction::class);
        $this->app->singleton(DeleteMediaAction::class);
    }

    /**
     * Register strategies with the Actions
     */
    protected function registerStrategies(): void
    {
        $enabledStrategies = MediaType::getEnabledStrategies();

        // Set strategies on Actions
        $this->app->make(StoreMediaAction::class)->setStrategies($enabledStrategies);
        $this->app->make(GetMediaAction::class)->setStrategies($enabledStrategies);
    }
}
