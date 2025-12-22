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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
