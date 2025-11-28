<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Models\MediaResource;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\UploadStrategies\UploadImageStrategy;
use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class StoreMediaServiceTest extends TestCase
{
    private StoreMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StoreMediaService();
        Storage::fake('public');
    }

    public function test_store_validates_data_before_processing()
    {
        // Create invalid data that should fail validation
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.txt',
            name: '', // Empty name should fail validation
            description: 'Test description',
            date: now(),
            meta: [],
            directory: 'test',
            generateThumbnail: false
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->service->store($data);
    }

    public function test_store_local_image_creates_media_resource()
    {
        // Create a fake image file
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: 'A test image',
            date: now(),
            meta: [],
            directory: 'images',
            generateThumbnail: false
        );

        // For this test, we'll actually test the flow without heavy mocking
        // since the upload strategies handle the actual storage
        $result = $this->service->store($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Test Image', $result->display_name);
        $this->assertEquals('image', $result->type);
    }

    public function test_store_external_media_creates_media_resource()
    {
        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'External Image',
            description: 'An external image',
            date: now(),
            meta: [],
        );

        $result = $this->service->store($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('External Image', $result->display_name);
        $this->assertEquals('external', $result->source);
        $this->assertEquals('https://example.com/image.jpg', $result->url);
    }

    public function test_getUploadStrategy_returns_image_strategy_for_images()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test',
            description: '',
            date: now(),
            meta: [],
            directory: 'test'
        );

        $strategy = $this->invokePrivateMethod($this->service, 'getUploadStrategy', [$data]);

        $this->assertInstanceOf(UploadImageStrategy::class, $strategy);
    }

    public function test_getUploadStrategy_returns_media_strategy_for_other_types()
    {
        $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');
        $data = new StoreLocalMediaData(
            type: MediaType::VIDEO,
            file: $file,
            fileName: 'test.mp4',
            name: 'Test',
            description: '',
            date: now(),
            meta: [],
            directory: 'videos'
        );

        $strategy = $this->invokePrivateMethod($this->service, 'getUploadStrategy', [$data]);

        $this->assertInstanceOf(UploadMediaStrategy::class, $strategy);
        $this->assertNotInstanceOf(UploadImageStrategy::class, $strategy);
    }

    public function test_validateFile_throws_exception_for_banned_file_types()
    {
        config(['media.banned_file_types' => ['exe', 'bat']]);

        $file = UploadedFile::fake()->create('malware.exe', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File type '.exe' is not allowed");

        $this->invokePrivateMethod($this->service, 'validateFile', [$file, MediaType::DOCUMENT]);
    }

    public function test_validateFile_throws_exception_for_unsupported_extensions()
    {
        $file = UploadedFile::fake()->create('test.xyz', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File extension \'.xyz\' is not supported');

        $this->invokePrivateMethod($this->service, 'validateFile', [$file, MediaType::IMAGE]);
    }

    public function test_validateFile_throws_exception_for_unsupported_mime_types()
    {
        // Create a file with wrong mime type for the media type
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type \'text/plain\' is not supported');

        $this->invokePrivateMethod($this->service, 'validateFile', [$file, MediaType::IMAGE]);
    }

    public function test_validateFile_passes_for_valid_file()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        // This should not throw any exception
        $this->invokePrivateMethod($this->service, 'validateFile', [$file, MediaType::IMAGE]);

        // If we reach this point, the validation passed
        $this->assertTrue(true);
    }

    public function test_store_with_thumbnail_generation_enabled()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: 'Test with thumbnail',
            date: now(),
            meta: [],
            directory: 'images',
            generateThumbnail: true
        );

        $result = $this->service->store($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertTrue($data->generateThumbnail);
    }

    public function test_validateFile_with_file_size_validation()
    {
        // Mock config to set strict validation rules
        config(['media.validation.image' => ['max:1024']]); // 1MB max

        // Create a small valid image
        $file = UploadedFile::fake()->image('test.jpg', 100, 100)->size(500); // 500KB

        // This should pass validation
        $this->invokePrivateMethod($this->service, 'validateFile', [$file, MediaType::IMAGE]);

        $this->assertTrue(true);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
