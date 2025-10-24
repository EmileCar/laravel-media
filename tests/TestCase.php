<?php

namespace Carone\Media\Tests;

use Carone\Media\CaroneMediaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup storage for testing
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function getPackageProviders($app): array
    {
        return [
            CaroneMediaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('media.disk', 'local');
        $app['config']->set('media.storage_path', 'media/{type}');
        $app['config']->set('media.generate_thumbnails', true);
        $app['config']->set('media.enabled_types', ['image', 'video', 'audio', 'document']);
        $app['config']->set('media.validation', [
            'image' => ['mimes:jpg,jpeg,png,gif', 'max:5120'],
            'video' => ['mimes:mp4,mov,avi', 'max:20480'],
            'audio' => ['mimes:mp3,wav', 'max:10240'],
            'document' => ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Create a fake image file for testing
     */
    protected function createFakeImageFile(string $name = 'test-image.jpg', int $width = 100, int $height = 100): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($tempPath, 'fake image content');
        return new UploadedFile($tempPath, $name, 'image/jpeg', null, true);
    }

    /**
     * Create a fake video file for testing
     */
    protected function createFakeVideoFile(string $name = 'test-video.mp4'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_video');
        file_put_contents($tempPath, 'fake video content');

        return new UploadedFile(
            $tempPath,
            $name,
            'video/mp4',
            null,
            true
        );
    }

    /**
     * Create a fake audio file for testing
     */
    protected function createFakeAudioFile(string $name = 'test-audio.mp3'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_audio');
        file_put_contents($tempPath, 'fake audio content');

        return new UploadedFile(
            $tempPath,
            $name,
            'audio/mpeg',
            null,
            true
        );
    }

    /**
     * Create a fake document file for testing
     */
    protected function createFakeDocumentFile(string $name = 'test-document.pdf'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_document');
        file_put_contents($tempPath, 'fake PDF content');

        return new UploadedFile(
            $tempPath,
            $name,
            'application/pdf',
            null,
            true
        );
    }

    /**
     * Create a fake unsupported file for testing
     */
    protected function createFakeUnsupportedFile(string $name = 'test-file.exe'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_exe');
        file_put_contents($tempPath, 'fake executable content');

        return new UploadedFile(
            $tempPath,
            $name,
            'application/x-msdownload',
            null,
            true
        );
    }

    /**
     * Assert that a file exists in storage
     */
    protected function assertFileExistsInStorage(string $disk, string $path): void
    {
        $this->assertTrue(
            Storage::disk($disk)->exists($path),
            "File {$path} does not exist in {$disk} storage"
        );
    }

    /**
     * Assert that a file does not exist in storage
     */
    protected function assertFileNotExistsInStorage(string $disk, string $path): void
    {
        $this->assertFalse(
            Storage::disk($disk)->exists($path),
            "File {$path} should not exist in {$disk} storage"
        );
    }
}