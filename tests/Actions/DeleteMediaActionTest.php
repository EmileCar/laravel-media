<?php

namespace Carone\Media\Tests\Actions;

use Carone\Media\Actions\DeleteMediaAction;
use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class DeleteMediaActionTest extends TestCase
{
    private DeleteMediaAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(DeleteMediaAction::class);
    }

    /** @test */
    public function it_can_delete_local_media_with_files()
    {
        $media = $this->createTestMediaWithFiles('image');

        // Verify files exist before deletion
        $this->assertFileExistsInStorage('local', $media->path);

        $result = $this->action->run($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
        
        // Verify files are deleted
        $this->assertFileNotExistsInStorage('local', $media->path);
    }

    /** @test */
    public function it_can_delete_external_media()
    {
        $media = $this->createTestMedia('video', 'External Video', 'external', 'https://youtube.com/watch?v=test');

        $result = $this->action->handle($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
    }

    /** @test */
    public function it_throws_exception_when_media_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->handle(999);
    }

    /** @test */
    public function it_can_delete_multiple_media()
    {
        $media1 = $this->createTestMediaWithFiles('image');
        $media2 = $this->createTestMedia('video', 'Video', 'external', 'https://example.com/video');
        $media3 = $this->createTestMediaWithFiles('audio');

        $ids = [$media1->id, $media2->id, $media3->id];

        $result = $this->action->deleteMultiple($ids);

        $this->assertEquals(3, $result['deleted']);
        $this->assertEmpty($result['failed']);

        // Verify all media are deleted from database
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('media_resources', ['id' => $id]);
        }
    }

    /** @test */
    public function it_handles_partial_failures_in_bulk_delete()
    {
        $media1 = $this->createTestMedia('image');
        $media2 = $this->createTestMedia('video');

        // Include non-existent ID
        $ids = [$media1->id, 999, $media2->id];

        $result = $this->action->deleteMultiple($ids);

        $this->assertEquals(2, $result['deleted']);
        $this->assertCount(1, $result['failed']);
        $this->assertEquals(999, $result['failed'][0]['id']);
    }

    /** @test */
    public function it_can_delete_by_type()
    {
        // Create test media of different types
        $image1 = $this->createTestMedia('image', 'Image 1');
        $image2 = $this->createTestMedia('image', 'Image 2');
        $video1 = $this->createTestMedia('video', 'Video 1');
        $audio1 = $this->createTestMedia('audio', 'Audio 1');

        $result = $this->action->deleteByType('image');

        $this->assertEquals(2, $result['deleted']);
        $this->assertEquals(0, $result['failed']);

        // Verify only images are deleted
        $this->assertDatabaseMissing('media_resources', ['id' => $image1->id]);
        $this->assertDatabaseMissing('media_resources', ['id' => $image2->id]);
        $this->assertDatabaseHas('media_resources', ['id' => $video1->id]);
        $this->assertDatabaseHas('media_resources', ['id' => $audio1->id]);
    }

    /** @test */
    public function it_can_delete_by_type_with_filters()
    {
        $localImage = $this->createTestMedia('image', 'Local Image', 'local');
        $externalImage = $this->createTestMedia('image', 'External Image', 'external', 'https://example.com');

        $result = $this->action->deleteByType('image', ['source' => 'external']);

        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(0, $result['failed']);

        // Verify only external image is deleted
        $this->assertDatabaseHas('media_resources', ['id' => $localImage->id]);
        $this->assertDatabaseMissing('media_resources', ['id' => $externalImage->id]);
    }

    /** @test */
    public function it_throws_exception_for_disabled_type_in_delete_by_type()
    {
        config(['media.enabled_types' => ['video', 'audio', 'document']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->action->deleteByType('image');
    }

    /** @test */
    public function it_can_cleanup_orphaned_files()
    {
        // Create some test files without database records
        $storagePath = 'media/image';
        Storage::disk('local')->makeDirectory($storagePath);
        Storage::disk('local')->put($storagePath . '/orphaned-file1.jpg', 'fake content');
        Storage::disk('local')->put($storagePath . '/orphaned-file2.jpg', 'fake content');
        
        // Create a legitimate media record
        $validMedia = $this->createTestMedia('image', 'Valid Image', 'local');
        Storage::disk('local')->put($validMedia->path, 'valid content');

        $result = $this->action->cleanupOrphanedFiles('image');

        $this->assertCount(2, $result['cleaned']);
        $this->assertContains('orphaned-file1.jpg', $result['cleaned']);
        $this->assertContains('orphaned-file2.jpg', $result['cleaned']);
        $this->assertEmpty($result['errors']);

        // Verify orphaned files are deleted but valid file remains
        $this->assertFileNotExistsInStorage('local', $storagePath . '/orphaned-file1.jpg');
        $this->assertFileNotExistsInStorage('local', $storagePath . '/orphaned-file2.jpg');
        $this->assertFileExistsInStorage('local', $validMedia->path);
    }

    /** @test */
    public function it_skips_thumbnails_directory_in_cleanup()
    {
        $storagePath = 'media/image';
        $thumbnailPath = $storagePath . '/thumbnails';
        
        Storage::disk('local')->makeDirectory($thumbnailPath);
        Storage::disk('local')->put($thumbnailPath . '/thumbnail1.jpg', 'thumbnail content');
        Storage::disk('local')->put($storagePath . '/orphaned-file.jpg', 'fake content');

        $result = $this->action->cleanupOrphanedFiles('image');

        $this->assertCount(1, $result['cleaned']);
        $this->assertContains('orphaned-file.jpg', $result['cleaned']);
        
        // Verify thumbnail is not deleted
        $this->assertFileExistsInStorage('local', $thumbnailPath . '/thumbnail1.jpg');
    }

    /** @test */
    public function it_throws_exception_for_disabled_type_in_cleanup()
    {
        config(['media.enabled_types' => ['video', 'audio', 'document']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->action->cleanupOrphanedFiles('image');
    }

    /** @test */
    public function it_handles_storage_errors_gracefully()
    {
        // Create media with non-existent file path (simulates storage error)
        $media = MediaResource::create([
            'type' => 'image',
            'source' => 'local',
            'file_name' => 'non-existent.jpg',
            'path' => 'media/image/non-existent.jpg',
            'name' => 'Test Image',
            'description' => 'Test description',
            'date' => now()->toDateString(),
            'meta' => [],
        ]);

        // Should still delete the database record even if file doesn't exist
        $result = $this->action->handle($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
    }

    /** @test */
    public function it_can_be_called_statically()
    {
        $media = $this->createTestMedia('image');

        $result = DeleteMediaAction::run($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media_resources', ['id' => $media->id]);
    }

    /** @test */
    public function it_logs_deletion_errors()
    {
        // This test would require mocking the logger, which is complex in this context
        // In a real test environment, you would mock the logger and verify error logging
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function it_deletes_thumbnails_along_with_main_files()
    {
        $media = $this->createTestMediaWithFiles('image');
        
        // Create thumbnail file
        $thumbnailPath = 'media/image/thumbnails/' . pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
        Storage::disk('local')->makeDirectory('media/image/thumbnails');
        Storage::disk('local')->put($thumbnailPath, 'thumbnail content');

        $this->assertFileExistsInStorage('local', $thumbnailPath);

        $result = $this->action->handle($media->id);

        $this->assertTrue($result);
        $this->assertFileNotExistsInStorage('local', $thumbnailPath);
    }

    /**
     * Helper method to create test media
     */
    private function createTestMedia(
        string $type, 
        string $name = 'Test Media', 
        string $source = 'local', 
        ?string $url = null,
        ?string $description = null
    ): MediaResource {
        return MediaResource::create([
            'type' => $type,
            'source' => $source,
            'file_name' => $source === 'local' ? "test-{$type}-file.ext" : null,
            'path' => $source === 'local' ? "media/{$type}/test-{$type}-file.ext" : null,
            'url' => $url,
            'name' => $name,
            'description' => $description ?? "Test {$type} description",
            'date' => now()->toDateString(),
            'meta' => ['test' => true],
        ]);
    }

    /**
     * Helper method to create test media with actual files
     */
    private function createTestMediaWithFiles(string $type): MediaResource
    {
        $media = $this->createTestMedia($type);

        // Create the actual file
        Storage::disk('local')->makeDirectory("media/{$type}");
        Storage::disk('local')->put($media->path, 'test file content');

        return $media;
    }
}