<?php

namespace Carone\Media;

use Carone\Media\Actions\DeleteMediaAction;
use Carone\Media\Actions\GetMediaAction;
use Carone\Media\Actions\StoreMediaAction;
use Carone\Media\Strategies\AudioStrategy;
use Carone\Media\Strategies\DocumentStrategy;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Strategies\VideoStrategy;
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

        $this->app->singleton(ImageStrategy::class);
        $this->app->singleton(VideoStrategy::class);
        $this->app->singleton(AudioStrategy::class);
        $this->app->singleton(DocumentStrategy::class);

        $this->app->singleton(StoreMediaAction::class);
        $this->app->singleton(GetMediaAction::class);
        $this->app->singleton(DeleteMediaAction::class);
    }

    /**
     * Register strategies with the Actions
     */
    protected function registerStrategies(): void
    {
        $strategies = [
            'image' => $this->app->make(ImageStrategy::class),
            'video' => $this->app->make(VideoStrategy::class),
            'audio' => $this->app->make(AudioStrategy::class),
            'document' => $this->app->make(DocumentStrategy::class),
        ];

        // Only register enabled strategies
        $enabledTypes = config('media.enabled_types', ['image', 'video', 'audio', 'document']);
        $enabledStrategies = array_intersect_key($strategies, array_flip($enabledTypes));

        // Set strategies on Actions
        $this->app->make(StoreMediaAction::class)->setStrategies($enabledStrategies);
        $this->app->make(GetMediaAction::class)->setStrategies($enabledStrategies);
    }
}
