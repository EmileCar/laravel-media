<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Processing\ExternalMediaRequest;
use Carone\Media\Processing\LocalMediaRequest;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreMediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private StoreMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StoreMediaService();
    }

    public function test_store_local_file_creates_media_resource()
    {
        // Arrange
        Storage::fake('local');
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $request = new LocalMediaRequest(
            type: MediaType::IMAGE,
            file: $file,
            name: 'Test Image',
            description: 'A test image',
            date: Carbon::now(),
            fileName: 'custom-name.jpg',
            directory: 'uploads',
            disk: 'local',
            generateThumbnail: false // Set to false to avoid complex processing
        );

        // Act
        $result = $this->service->storeLocalFile($request);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('Test Image', $result->name);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('local', $result->source);
    }

    public function test_store_external_media_creates_media_resource()
    {
        // Arrange
        $request = new ExternalMediaRequest(
            type: MediaType::VIDEO,
            url: 'https://example.com/video.mp4',
            name: 'External Video',
            description: 'A video from external source',
            date: Carbon::now(),
            meta: ['source' => 'youtube']
        );

        // Act
        $result = $this->service->storeExternalMedia($request);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('External Video', $result->name);
        $this->assertEquals('video', $result->type);
        $this->assertEquals('external', $result->source);
        $this->assertEquals('https://example.com/video.mp4', $result->url);
    }

    public function test_store_method_delegates_to_local_file_when_request_is_local()
    {
        // Arrange
        Storage::fake('local');
        $file = UploadedFile::fake()->image('test.jpg');

        $request = new LocalMediaRequest(
            type: MediaType::IMAGE,
            file: $file,
            name: 'Test',
            description: null,
            date: Carbon::now(),
            fileName: null,
            directory: null,
            disk: null,
            generateThumbnail: false
        );

        // Act
        $result = $this->service->store($request);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('local', $result->source);
    }

    public function test_store_method_delegates_to_external_media_when_request_is_external()
    {
        // Arrange
        $request = new ExternalMediaRequest(
            type: MediaType::VIDEO,
            url: 'https://example.com/video.mp4',
            name: 'External Video',
            description: null,
            date: Carbon::now(),
            meta: null
        );

        // Act
        $result = $this->service->store($request);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('external', $result->source);
    }
}
