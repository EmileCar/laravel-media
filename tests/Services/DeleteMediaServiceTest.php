<?php

namespace Carone\Media\Tests\Services;

use Carone\Common\BulkOperations\BulkOperationResult;
use Carone\Media\Models\MediaResource;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mockery;

class DeleteMediaServiceTest extends TestCase
{
    private DeleteMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeleteMediaService();
    }

    public function test_delete_removes_media_resource_and_files()
    {
        // Arrange
        Storage::fake('local');
        Storage::disk('local')->put('uploads/test.jpg', 'fake content');
        Storage::disk('local')->put('uploads/test_thumb.jpg', 'fake thumb');

        $mediaResource = new MediaResource([
            'id' => 1,
            'name' => 'Test Media',
            'type' => MediaType::IMAGE->value,
            'path' => 'uploads/test.jpg',
            'thumbnail_path' => 'uploads/test_thumb.jpg',
            'source' => 'local',
            'disk' => 'local',
        ]);

        $this->mockMediaFind(1, $mediaResource);
        $this->mockMediaDelete($mediaResource);

        // Act
        $result = $this->service->delete(1);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('local')->exists('uploads/test.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('uploads/test_thumb.jpg'));
    }

    public function test_delete_returns_false_when_media_not_found()
    {
        // Arrange
        $this->mockMediaNotFound(999);

        // Act
        $result = $this->service->delete(999);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_multiple_processes_array_of_ids()
    {
        // Arrange
        Storage::fake('local');

        $mediaResources = [
            new MediaResource([
                'id' => 1,
                'name' => 'Media 1',
                'type' => MediaType::IMAGE->value,
                'path' => 'uploads/test1.jpg',
                'source' => 'local',
                'disk' => 'local',
            ]),
            new MediaResource([
                'id' => 2,
                'name' => 'Media 2',
                'type' => MediaType::IMAGE->value,
                'path' => 'uploads/test2.jpg',
                'source' => 'local',
                'disk' => 'local',
            ]),
        ];

        $this->mockMediaWhereIn([1, 2], $mediaResources);
        $this->mockBulkDelete($mediaResources);

        // Act
        $result = $this->service->deleteMultiple([1, 2]);

        // Assert
        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals(2, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
    }

    public function test_delete_by_type_removes_all_media_of_type()
    {
        // Arrange
        Storage::fake('local');

        $mediaResources = [
            new MediaResource([
                'id' => 1,
                'type' => MediaType::IMAGE->value,
                'path' => 'uploads/image1.jpg',
                'source' => 'local',
                'disk' => 'local',
            ]),
            new MediaResource([
                'id' => 2,
                'type' => MediaType::IMAGE->value,
                'path' => 'uploads/image2.jpg',
                'source' => 'local',
                'disk' => 'local',
            ]),
        ];

        $this->mockMediaByType('image', $mediaResources);
        $this->mockBulkDelete($mediaResources);

        // Act
        $result = $this->service->deleteByType(MediaType::IMAGE);

        // Assert
        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals(2, $result->getSucceededCount());
    }

    public function test_delete_external_media_only_removes_database_record()
    {
        // Arrange
        $mediaResource = new MediaResource([
            'id' => 1,
            'name' => 'External Media',
            'type' => MediaType::VIDEO->value,
            'path' => 'https://example.com/video.mp4',
            'source' => 'external',
        ]);

        $this->mockMediaFind(1, $mediaResource);
        $this->mockMediaDelete($mediaResource);

        // Act
        $result = $this->service->delete(1);

        // Assert
        $this->assertTrue($result);
        // Should not attempt to delete any local files
    }

    private function mockMediaFind(int $id, MediaResource $resource): void
    {
        $mock = Mockery::mock('alias:' . MediaResource::class);
        $mock->shouldReceive('find')
             ->with($id)
             ->andReturn($resource);
    }

    private function mockMediaNotFound(int $id): void
    {
        $mock = Mockery::mock('alias:' . MediaResource::class);
        $mock->shouldReceive('find')
             ->with($id)
             ->andReturn(null);
    }

    private function mockMediaDelete(MediaResource $resource): void
    {
        $resource->shouldReceive('delete')
                 ->once()
                 ->andReturn(true);
    }

    private function mockMediaWhereIn(array $ids, array $resources): void
    {
        $mock = Mockery::mock('alias:' . MediaResource::class);
        $mock->shouldReceive('whereIn')
             ->with('id', $ids)
             ->andReturnSelf();
        $mock->shouldReceive('get')
             ->andReturn(collect($resources));
    }

    private function mockMediaByType(string $type, array $resources): void
    {
        $mock = Mockery::mock('alias:' . MediaResource::class);
        $mock->shouldReceive('where')
             ->with('type', $type)
             ->andReturnSelf();
        $mock->shouldReceive('get')
             ->andReturn(collect($resources));
    }

    private function mockBulkDelete(array $resources): void
    {
        foreach ($resources as $resource) {
            $resource->shouldReceive('delete')
                     ->once()
                     ->andReturn(true);
        }
    }
}
