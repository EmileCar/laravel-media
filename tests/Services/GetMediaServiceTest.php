<?php

namespace Carone\Media\Tests\Services;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Media\Models\MediaResource;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class GetMediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private GetMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GetMediaService();
    }

    public function test_get_by_id_returns_media_resource()
    {
        // Arrange
        $mediaResource = MediaResource::create([
            'name' => 'Test Media',
            'type' => MediaType::IMAGE->value,
            'source' => 'local',
            'path' => 'uploads/test.jpg',
            'file_name' => 'test.jpg',
            'date' => Carbon::now(),
        ]);

        // Act
        $result = $this->service->getResourceById($mediaResource->id);

        // Assert
        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals($mediaResource->id, $result->id);
        $this->assertEquals('Test Media', $result->name);
    }

    public function test_get_by_id_throws_exception_when_not_found()
    {
        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->service->getResourceById(999);
    }

    public function test_search_returns_paginated_results()
    {
        // Arrange
        MediaResource::create([
            'name' => 'Test Image 1',
            'type' => MediaType::IMAGE->value,
            'source' => 'local',
            'path' => 'uploads/test1.jpg',
            'file_name' => 'test1.jpg',
            'date' => Carbon::now(),
        ]);

        MediaResource::create([
            'name' => 'Test Image 2',
            'type' => MediaType::IMAGE->value,
            'source' => 'local',
            'path' => 'uploads/test2.jpg',
            'file_name' => 'test2.jpg',
            'date' => Carbon::now(),
        ]);

        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm('test'),
            filters: ['type' => ['image']]
        );

        // Act
        $result = $this->service->search($criteria, 0, 20);

        // Assert
        $this->assertCount(2, $result->items());
        $this->assertEquals('Test Image 1', $result->items()[0]->name);
    }

    public function test_serve_media_throws_exception_for_missing_file()
    {
        // Arrange
        Storage::fake('local');

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->service->serveMedia('nonexistent.jpg');
    }

    public function test_get_media_types_returns_enabled_types()
    {
        // Act
        $result = $this->service->getMediaTypes();

        // Assert
        $this->assertIsArray($result);
    }

    public function test_serve_media_file_returns_response()
    {
        // Arrange
        Storage::fake('local');
        Storage::disk('local')->put('uploads/test.jpg', 'fake image content');

        $mediaResource = new MediaResource([
            'id' => 1,
            'name' => 'Test Media',
            'type' => MediaType::IMAGE->value,
            'path' => 'uploads/test.jpg',
            'source' => 'local',
            'disk' => 'local',
        ]);

        // Act
        $response = $this->service->serveMediaFile($mediaResource);

        // Assert
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }
}
