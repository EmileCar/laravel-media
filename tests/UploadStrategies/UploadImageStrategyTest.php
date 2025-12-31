<?php

namespace Carone\Media\Tests\UploadStrategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\UploadStrategies\UploadImageStrategy;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class UploadImageStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_storeLocalFile_creates_image_media_resource()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: 'A test image file',
            date: now(),
            directory: 'images',
            generateThumbnail: false
        );

        $strategy = new UploadImageStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Test Image', $result->display_name);
        $this->assertEquals(MediaType::IMAGE->value, $result->type);
        $this->assertEquals('local', $result->source);
        $this->assertNotNull($result->path);
    }

    public function test_processFile_returns_null_when_processing_disabled()
    {
        config(['media.processing.image.enabled' => false]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        // Access protected method using reflection
        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNull($result);
    }

    public function test_processFile_returns_null_when_no_config()
    {
        config(['media.processing.image' => []]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNull($result);
    }

    public function test_processFile_processes_image_with_resize()
    {
        config([
            'media.processing.image' => [
                'enabled' => true,
                'convert_format' => 'jpg',
                'quality' => 85,
                'resize' => [
                    'enabled' => true,
                    'width' => 400,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ],
                'crop' => ['enabled' => false],
                'watermark' => ['enabled' => false],
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNotNull($result);
        $this->assertFileExists($result);

        // Clean up
        if ($result && file_exists($result)) {
            unlink($result);
        }
    }

    public function test_processFile_processes_image_with_crop()
    {
        config([
            'media.processing.image' => [
                'enabled' => true,
                'convert_format' => 'jpg',
                'quality' => 85,
                'resize' => ['enabled' => false],
                'crop' => [
                    'enabled' => true,
                    'width' => 400,
                    'height' => 300,
                    'position' => 'center',
                ],
                'watermark' => ['enabled' => false],
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNotNull($result);
        $this->assertFileExists($result);

        // Clean up
        if ($result && file_exists($result)) {
            unlink($result);
        }
    }

    public function test_processFile_processes_image_with_watermark()
    {
        // Create a watermark file
        $watermarkFile = UploadedFile::fake()->image('watermark.png', 100, 50);
        $watermarkPath = $watermarkFile->storeAs('temp', 'watermark.png');
        $fullWatermarkPath = Storage::path($watermarkPath);

        config([
            'media.processing.image' => [
                'enabled' => true,
                'convert_format' => 'jpg',
                'quality' => 85,
                'resize' => ['enabled' => false],
                'crop' => ['enabled' => false],
                'watermark' => [
                    'enabled' => true,
                    'path' => $fullWatermarkPath,
                    'position' => 'bottom-right',
                    'opacity' => 80,
                    'margin' => 10,
                ],
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNotNull($result);
        $this->assertFileExists($result);

        // Clean up
        if ($result && file_exists($result)) {
            unlink($result);
        }
    }

    public function test_processFile_converts_format()
    {
        config([
            'media.processing.image' => [
                'enabled' => true,
                'convert_format' => 'png',
                'quality' => 85,
                'resize' => ['enabled' => false],
                'crop' => ['enabled' => false],
                'watermark' => ['enabled' => false],
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 400, 300);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNotNull($result);
        $this->assertStringEndsWith('.png', $result);
        $this->assertFileExists($result);

        // Clean up
        if ($result && file_exists($result)) {
            unlink($result);
        }
    }

    public function test_generateThumbnail_creates_thumbnail()
    {
        config(['media.storage_path' => 'media/{path}']);
        config([
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 300,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ]
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        // Create a file reference
        $fileReference = new MediaFileReference('test', 'jpg', 'public', 'images');

        // Store the original file first so generateThumbnail can read it
        Storage::disk('public')->put($fileReference->getStoragePath(), $file->getContent());

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('generateThumbnail');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $fileReference);

        $this->assertInstanceOf(MediaFileReference::class, $result);
        $this->assertEquals('jpg', $result->extension);
    }

    public function test_generateThumbnail_returns_null_when_no_config()
    {
        config(['media.processing.thumbnail' => []]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        $fileReference = new MediaFileReference('test', 'jpg', 'public', 'images');

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('generateThumbnail');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $fileReference);

        $this->assertNull($result);
    }

    public function test_generateThumbnail_handles_exceptions_gracefully()
    {
        config([
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 300,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ]
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: '',
            date: now(),
            directory: 'images'
        );

        $strategy = new UploadImageStrategy($data);

        // Create a file reference to a non-existent file
        $fileReference = new MediaFileReference('nonexistent', 'jpg', 'public', 'images');

        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('generateThumbnail');
        $method->setAccessible(true);

        // Should not throw exception, just return null
        $result = $method->invoke($strategy, $fileReference);

        $this->assertNull($result);
    }

    public function test_storeLocalFile_with_thumbnail_generation()
    {
        config([
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 300,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ]
            ]
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: 'Test with thumbnail',
            date: now(),
            directory: 'images',
            generateThumbnail: true
        );

        $strategy = new UploadImageStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Test Image', $result->display_name);
        $this->assertTrue($data->generateThumbnail);
    }

    public function test_full_workflow_with_all_processing_enabled()
    {
        config([
            'media.storage_path' => 'media/{path}',
            'media.disk' => 'public',
            'media.processing.image' => [
                'enabled' => true,
                'convert_format' => 'jpg',
                'quality' => 85,
                'resize' => [
                    'enabled' => true,
                    'width' => 800,
                    'height' => 600,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ],
                'crop' => ['enabled' => false],
                'watermark' => ['enabled' => false],
                'optimize' => true,
            ],
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 300,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ]
            ]
        ]);

        $file = UploadedFile::fake()->image('large-image.png', 1600, 1200);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'large-image.png',
            name: 'Large Test Image',
            description: 'A large test image that will be processed',
            date: now(),
            directory: 'images',
            generateThumbnail: true
        );

        $strategy = new UploadImageStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Large Test Image', $result->display_name);
        $this->assertEquals(MediaType::IMAGE->value, $result->type);
        $fileRef = $result->loadFileReference();
        $this->assertTrue(Storage::disk('public')->exists($fileRef->getStoragePath()));
    }

    public function test_auto_generated_thumbnail_does_not_overwrite_original_image()
    {
        // Enable thumbnail generation
        config([
            'media.thumbnails.enabled' => true,
            'media.thumbnails.auto_generate_for_images' => true,
            'media.thumbnails.storage_path' => 'media/thumbnails/{path}',
            'media.storage_path' => 'media/{path}',
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 300,
                    'height' => 300,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ],
            ],
        ]);

        $file = UploadedFile::fake()->image('original.jpg', 1920, 1080);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'original.jpg',
            name: 'Original Image',
            description: 'Should not be overwritten by thumbnail',
            date: now(),
            directory: 'images/2024',
            generateThumbnail: true
        );

        $strategy = new UploadImageStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);

        // Get the stored paths from the database
        $this->assertNotNull($result->path, 'Original path should be stored');
        $this->assertNotNull($result->thumbnail_path, 'Thumbnail path should be stored');

        // Load file references
        $originalFileRef = $result->loadFileReference();
        $this->assertNotNull($originalFileRef, 'Original file reference should exist');

        $thumbnailFileRef = $result->loadThumbnailFileReference();
        $this->assertNotNull($thumbnailFileRef, 'Thumbnail file reference should exist');

        // Verify original file exists at the correct storage path
        $originalStoragePath = $originalFileRef->getStoragePath();
        $this->assertTrue(
            Storage::disk($originalFileRef->disk)->exists($originalStoragePath),
            "Original image should exist at: {$originalStoragePath}"
        );

        // Verify thumbnail exists at the correct storage path
        $thumbnailStoragePath = $thumbnailFileRef->getThumbnailStoragePath();
        $this->assertTrue(
            Storage::disk($thumbnailFileRef->disk)->exists($thumbnailStoragePath),
            "Thumbnail should exist at: {$thumbnailStoragePath}"
        );

        // Verify paths are different
        $this->assertNotEquals(
            $result->path,
            $result->thumbnail_path,
            'Thumbnail and original should have different database paths'
        );

        $this->assertNotEquals(
            $originalStoragePath,
            $thumbnailStoragePath,
            'Thumbnail and original should have different storage paths'
        );

        // Verify thumbnail filename includes _thumb suffix
        $this->assertStringContainsString('_thumb', $thumbnailFileRef->filename, 'Thumbnail filename should include _thumb suffix');

        // Verify thumbnail is stored in thumbnails directory according to config
        $this->assertStringContainsString('thumbnails', $thumbnailStoragePath, 'Thumbnail should be in thumbnails directory');

        // Verify both files are actual different sizes (original should be larger)
        $originalSize = Storage::disk($originalFileRef->disk)->size($originalStoragePath);
        $thumbnailSize = Storage::disk($thumbnailFileRef->disk)->size($thumbnailStoragePath);

        $this->assertGreaterThan(0, $originalSize, 'Original image should have content');
        $this->assertGreaterThan(0, $thumbnailSize, 'Thumbnail should have content');

        // Typically thumbnail should be smaller, but let's just ensure they're different files
        $this->assertNotEquals($originalSize, $thumbnailSize, 'Original and thumbnail should have different file sizes');
    }

    public function test_thumbnail_uses_configured_disk()
    {
        // Setup separate disk for thumbnails
        Storage::fake('thumbnails');

        config([
            'media.disk' => 'public',
            'media.thumbnails.enabled' => true,
            'media.thumbnails.disk' => 'thumbnails',
            'media.thumbnails.storage_path' => 'media/thumbnails/{path}',
            'media.storage_path' => 'media/{path}',
            'media.processing.thumbnail' => [
                'convert_format' => 'jpg',
                'quality' => 80,
                'resize' => [
                    'width' => 200,
                    'height' => 200,
                    'maintain_aspect_ratio' => true,
                    'upsize' => false,
                ],
            ],
        ]);

        $file = UploadedFile::fake()->image('test.png', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.png',
            name: 'Test Image',
            description: 'Test thumbnail disk configuration',
            date: now(),
            directory: 'test',
            generateThumbnail: true
        );

        $strategy = new UploadImageStrategy($data);
        $result = $strategy->storeLocalFile($data);

        // Original should be on public disk
        $originalFileRef = $result->loadFileReference();
        $this->assertEquals('public', $originalFileRef->disk);
        $this->assertTrue(Storage::disk('public')->exists($originalFileRef->getStoragePath()));

        // Thumbnail should be on thumbnails disk
        $thumbnailFileRef = $result->loadThumbnailFileReference();
        $this->assertNotNull($thumbnailFileRef);
        $this->assertEquals('thumbnails', $thumbnailFileRef->disk, 'Thumbnail should use configured thumbnail disk');
        $this->assertTrue(Storage::disk('thumbnails')->exists($thumbnailFileRef->getThumbnailStoragePath()));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
