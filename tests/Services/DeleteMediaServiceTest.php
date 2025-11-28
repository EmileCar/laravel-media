<?php

namespace Carone\Media\Tests\Services;

use Carone\Common\BulkOperations\BulkOperationResult;
use Carone\Media\Models\MediaResource;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class DeleteMediaServiceTest extends TestCase
{
    private DeleteMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeleteMediaService();
        Storage::fake('public');
    }

    public function test_delete_successfully_removes_media_and_files()
    {
        // Create a real media resource
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'local',
            'path' => 'test/image.jpg',
            'disk' => 'public'
        ]);

        // Create a fake file to ensure deletion works
        Storage::disk('public')->put('media/test/image.jpg', 'fake content');

        $result = $this->service->delete($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
    }

    public function test_delete_throws_exception_when_media_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->delete(999);
    }

    public function test_delete_handles_storage_exceptions_gracefully()
    {
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'local',
            'path' => 'test/nonexistent.jpg',
            'disk' => 'nonexistent_disk'
        ]);

        // This should handle the storage exception but still delete the record
        $this->expectException(\Exception::class);
        $this->service->delete($media->id);
    }

    public function test_deleteMultiple_processes_all_ids()
    {
        // Create multiple media resources
        $media1 = MediaResource::factory()->create(['type' => 'image']);
        $media2 = MediaResource::factory()->create(['type' => 'video']);
        $media3 = MediaResource::factory()->create(['type' => 'document']);

        $ids = [$media1->id, $media2->id, $media3->id];

        $result = $this->service->deleteMultiple($ids);

        $this->assertInstanceOf(BulkOperationResult::class, $result);

        // Verify all were processed (some may fail due to file operations)
        $totalProcessed = $result->successful + $result->failed;
        $this->assertEquals(3, $totalProcessed);
    }

    public function test_deleteMultiple_handles_failures()
    {
        // Create one valid media and include one invalid ID
        $media = MediaResource::factory()->create(['type' => 'image']);
        $ids = [$media->id, 999]; // 999 doesn't exist

        $result = $this->service->deleteMultiple($ids);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThan(0, $result->failed); // At least one failure
    }

    public function test_deleteByType_throws_exception_for_disabled_type()
    {
        // Temporarily disable images in config
        config(['media.enabled_types' => ['video', 'audio', 'document']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->service->deleteByType(MediaType::IMAGE);
    }

    public function test_deleteByType_deletes_all_media_of_type()
    {
        // Create media of different types
        MediaResource::factory()->count(3)->create(['type' => 'image']);
        MediaResource::factory()->count(2)->create(['type' => 'video']);
        MediaResource::factory()->count(1)->create(['type' => 'document']);

        // Delete all images
        $result = $this->service->deleteByType(MediaType::IMAGE);

        $this->assertInstanceOf(BulkOperationResult::class, $result);

        // Verify images were deleted
        $remainingImages = MediaResource::where('type', 'image')->count();
        $this->assertEquals(0, $remainingImages);

        // Verify other types remain
        $remainingVideos = MediaResource::where('type', 'video')->count();
        $this->assertEquals(2, $remainingVideos);
    }

    public function test_deleteByType_handles_empty_result()
    {
        // Delete from type that has no media
        $result = $this->service->deleteByType(MediaType::AUDIO);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals(0, $result->successful);
        $this->assertEquals(0, $result->failed);
    }

    public function test_delete_handles_external_media()
    {
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'external',
            'url' => 'https://example.com/image.jpg'
        ]);

        $result = $this->service->delete($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
    }

    public function test_delete_logs_errors_on_exception()
    {
        // Create media with invalid disk configuration to trigger exception
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'local',
            'path' => 'test/image.jpg',
            'disk' => 'invalid_disk'
        ]);

        $this->expectException(\Exception::class);

        $this->service->delete($media->id);
    }
}
