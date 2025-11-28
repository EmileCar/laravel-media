<?php

namespace Carone\Media\Tests\Services;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Media\Models\MediaResource;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Utilities\MediaModel;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\Utilities\MediaUtilities;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GetMediaServiceTest extends TestCase
{
    private GetMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GetMediaService();
    }

    public function test_getResourceById_returns_media_resource()
    {
        // Create a real media resource for testing
        $media = MediaResource::factory()->create([
            'id' => 1,
            'type' => 'image',
            'display_name' => 'Test Image'
        ]);

        $result = $this->service->getResourceById(1);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Image', $result->display_name);
    }

    public function test_getResourceById_throws_exception_when_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->getResourceById(999);
    }

    public function test_getMediaTypes_returns_enabled_types()
    {
        $result = $this->service->getMediaTypes();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // MediaUtilities::getEnabled() returns MediaType enums
        foreach ($result as $type) {
            $this->assertInstanceOf(\Carone\Media\ValueObjects\MediaType::class, $type);
        }
    }

    public function test_serveMedia_returns_binary_file_response()
    {
        // Create media resource
        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/image.jpg',
            'meta' => ['original_name' => 'image.jpg']
        ]);

        // Mock file reference and storage helper
        $fileReference = new MediaFileReference('image', 'jpg', 'public', 'test');

        $this->mock(MediaStorageHelper::class, function ($mock) use ($fileReference) {
            $mock->shouldReceive('doesFileExist')
                 ->with('public', 'test/image.jpg')
                 ->andReturn(true);
            $mock->shouldReceive('getPhysicalPath')
                 ->with($fileReference)
                 ->andReturn('/path/to/image.jpg');
        });

        $this->mock(MediaUtilities::class, function ($mock) {
            $mock->shouldReceive('getMimeType')
                 ->with('jpg', 'image/jpg')
                 ->andReturn('image/jpeg');
        });

        // Mock the loadFileReference method
        $media->shouldReceive('loadFileReference')
              ->andReturn($fileReference);

        config(['media.cache_minutes' => 60]);

        $result = $this->service->serveMedia('test/image.jpg');

        $this->assertInstanceOf(BinaryFileResponse::class, $result);
    }

    public function test_serveMedia_aborts_404_when_file_not_exists()
    {
        // Create media resource
        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/missing.jpg'
        ]);

        $fileReference = new MediaFileReference('missing', 'jpg', 'public', 'test');

        $this->mock(MediaStorageHelper::class, function ($mock) {
            $mock->shouldReceive('doesFileExist')
                 ->andReturn(false);
        });

        $media->shouldReceive('loadFileReference')
              ->andReturn($fileReference);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->service->serveMedia('test/missing.jpg');
    }

    public function test_serveThumbnail_returns_binary_file_response()
    {
        // Create media resource
        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/image.jpg'
        ]);

        $thumbnailReference = new MediaFileReference('image_thumb', 'jpg', 'public', 'test/thumbnails');

        $this->mock(MediaStorageHelper::class, function ($mock) use ($thumbnailReference) {
            $mock->shouldReceive('doesFileExist')
                 ->with('public', 'test/thumbnails/image_thumb.jpg')
                 ->andReturn(true);
            $mock->shouldReceive('getPhysicalPath')
                 ->with($thumbnailReference)
                 ->andReturn('/path/to/thumb.jpg');
        });

        $this->mock(MediaUtilities::class, function ($mock) {
            $mock->shouldReceive('getMimeType')
                 ->andReturn('image/jpeg');
        });

        $media->shouldReceive('loadThumbnailFileReference')
              ->andReturn($thumbnailReference);

        $result = $this->service->serveThumbnail('test/image.jpg');

        $this->assertInstanceOf(BinaryFileResponse::class, $result);
    }

    public function test_search_returns_paginated_results()
    {
        // Create test data
        MediaResource::factory()->count(5)->create(['type' => 'image']);
        MediaResource::factory()->count(3)->create(['type' => 'video']);

        $searchTerm = new SearchTerm('test');
        $criteria = new SearchCriteria($searchTerm, []);

        $result = $this->service->search($criteria, 0, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertLessThanOrEqual(10, $result->count());
    }

    public function test_search_filters_by_type()
    {
        // Create test data
        MediaResource::factory()->count(3)->create(['type' => 'image']);
        MediaResource::factory()->count(2)->create(['type' => 'video']);

        $searchTerm = new SearchTerm('');
        $criteria = new SearchCriteria($searchTerm, ['type' => ['image']]);

        $result = $this->service->search($criteria, 0, 10);

        foreach ($result->items() as $item) {
            $this->assertEquals('image', $item->type);
        }
    }

    public function test_search_with_search_term()
    {
        // Create test data
        MediaResource::factory()->create([
            'display_name' => 'Test Image',
            'description' => 'A test image'
        ]);
        MediaResource::factory()->create([
            'display_name' => 'Another File',
            'description' => 'Different content'
        ]);

        $searchTerm = new SearchTerm('Test');
        $criteria = new SearchCriteria($searchTerm, []);

        $result = $this->service->search($criteria, 0, 10);

        $this->assertGreaterThan(0, $result->total());
        foreach ($result->items() as $item) {
            $this->assertTrue(
                stripos($item->display_name, 'Test') !== false ||
                stripos($item->description, 'Test') !== false
            );
        }
    }

    public function test_applySearchCriteria_builds_correct_query()
    {
        $searchTerm = new SearchTerm('test search');
        $criteria = new SearchCriteria($searchTerm, ['type' => ['image', 'video']]);

        $query = $this->service->applySearchCriteria($criteria);

        // Test that we get a query builder
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);

        // We can't easily test the actual SQL without executing,
        // but we can verify the query runs without error
        $results = $query->get();
        $this->assertNotNull($results);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
