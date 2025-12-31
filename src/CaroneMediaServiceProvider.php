<?php

namespace Carone\Media;

use Carone\Media\Contracts\DeleteMediaServiceInterface;
use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\MediaManager;
use Carone\Media\Console\Commands\MediaInfo;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager as InterventionImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class CaroneMediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->publishes([
            __DIR__ . '/../config/media.php' => config_path('media.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MediaInfo::class,
            ]);
        }

        $this->configureInterventionImage();

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
     * Configure Intervention Image with the selected driver
     */
    private function configureInterventionImage(): void
    {
        $driver = config('media.image_driver', 'imagick');

        // Warn if Imagick is configured but not available
        if ($driver === 'imagick' && !extension_loaded('imagick')) {
            logger()->warning(
                'Imagick driver configured but extension not installed. Falling back to GD. ' .
                'For better memory efficiency with large images, install Imagick: ' .
                'https://www.php.net/manual/en/book.imagick.php'
            );
            $driver = 'gd';
        }

        // Configure Intervention Image driver
        $driverInstance = match($driver) {
            'imagick' => new ImagickDriver(),
            'gd' => new GdDriver(),
            default => new ImagickDriver(),
        };

        // Bind the configured image manager
        $this->app->singleton(InterventionImageManager::class, function () use ($driverInstance) {
            return new InterventionImageManager($driverInstance);
        });
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
