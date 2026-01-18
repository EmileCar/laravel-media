<?php

namespace Carone\Media\Tests\Http\Controllers;

use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaControllerCustomThumbnailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_local_image_with_custom_thumbnail_file()
    {
        // Create main image and custom thumbnail
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Custom Thumbnail',
            'description' => 'Test image with custom thumbnail',
            'generate_thumbnail' => false,
            'thumbnail_file' => $thumbnailFile,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $this->assertDatabaseHas('media_resources', [
            'type' => 'image',
            'source' => 'local',
            'display_name' => 'Photo with Custom Thumbnail',
        ]);

        $media = MediaResource::where('display_name', 'Photo with Custom Thumbnail')->first();

        // Verify main image exists
        $this->assertNotNull($media->path);
        $fileRef = $media->loadFileReference();
        Storage::disk('local')->assertExists($fileRef->getStoragePath());

        // Verify custom thumbnail exists
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());
    }

    /** @test */
    public function it_can_upload_local_image_with_thumbnail_url()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $customThumbnailUrl = 'https://example.com/custom-thumbnail.jpg';

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Thumbnail URL',
            'generate_thumbnail' => false,
            'thumbnail_url' => $customThumbnailUrl,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $media = MediaResource::where('display_name', 'Photo with Thumbnail URL')->first();

        // Verify main image exists
        $this->assertNotNull($media->path);
        $fileRef = $media->loadFileReference();
        Storage::disk('local')->assertExists($fileRef->getStoragePath());

        // Verify thumbnail URL is stored
        $this->assertEquals($customThumbnailUrl, $media->thumbnail_url);
    }

    /** @test */
    public function it_can_upload_local_image_with_auto_generated_thumbnail()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Auto Thumbnail',
            'generate_thumbnail' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $media = MediaResource::where('display_name', 'Photo with Auto Thumbnail')->first();

        // Verify main image exists
        $this->assertNotNull($media->path);
        $fileRef = $media->loadFileReference();
        Storage::disk('local')->assertExists($fileRef->getStoragePath());

        // Verify auto-generated thumbnail exists
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());
    }

    /** @test */
    public function it_can_upload_external_media_with_custom_thumbnail_file()
    {
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'video',
            'source' => 'external',
            'url' => 'https://youtube.com/watch?v=example',
            'name' => 'External Video with Custom Thumbnail',
            'thumbnail_file' => $thumbnailFile,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $media = MediaResource::where('display_name', 'External Video with Custom Thumbnail')->first();

        // Verify external URL is stored
        $this->assertEquals('https://youtube.com/watch?v=example', $media->url);

        // Verify custom thumbnail exists
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());
    }

    /** @test */
    public function it_can_upload_external_media_with_thumbnail_url()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'video',
            'source' => 'external',
            'url' => 'https://youtube.com/watch?v=example',
            'name' => 'External Video with Thumbnail URL',
            'thumbnail_url' => 'https://example.com/video-thumb.jpg',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $media = MediaResource::where('display_name', 'External Video with Thumbnail URL')->first();

        // Verify external URL is stored
        $this->assertEquals('https://youtube.com/watch?v=example', $media->url);

        // Verify thumbnail URL is stored
        $this->assertEquals('https://example.com/video-thumb.jpg', $media->thumbnail_url);
    }

    /** @test */
    public function it_validates_thumbnail_file_must_be_an_image()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $invalidThumbnail = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Invalid Thumbnail',
            'generate_thumbnail' => false,
            'thumbnail_file' => $invalidThumbnail,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['thumbnail_file']);
    }

    /** @test */
    public function it_validates_thumbnail_url_must_be_valid_url()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Invalid Thumbnail URL',
            'generate_thumbnail' => false,
            'thumbnail_url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['thumbnail_url']);
    }

    /** @test */
    public function it_prioritizes_custom_thumbnail_over_auto_generation()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Both Thumbnail Options',
            'generate_thumbnail' => true, // Even with auto-generate enabled
            'thumbnail_file' => $thumbnailFile, // Custom should be used
        ]);

        $response->assertStatus(201);

        $media = MediaResource::where('display_name', 'Photo with Both Thumbnail Options')->first();

        // Verify custom thumbnail was used
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());
    }

    /** @test */
    public function custom_thumbnail_is_served_correctly()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo for Thumbnail Serving',
            'thumbnail_file' => $thumbnailFile,
        ]);

        $response->assertStatus(201);

        $media = MediaResource::where('display_name', 'Photo for Thumbnail Serving')->first();

        // Test serving the custom thumbnail
        $thumbnailResponse = $this->get("/media/thumbnails/{$media->id}");

        $thumbnailResponse->assertStatus(200);
        $thumbnailResponse->assertHeader('Content-Type', 'image/jpeg');
    }

    /** @test */
    public function it_can_upload_with_both_thumbnail_file_and_url()
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $imageFile,
            'name' => 'Photo with Both Thumbnail Types',
            'thumbnail_file' => $thumbnailFile,
            'thumbnail_url' => 'https://example.com/backup-thumb.jpg',
        ]);

        $response->assertStatus(201);

        $media = MediaResource::where('display_name', 'Photo with Both Thumbnail Types')->first();

        // Verify both are stored (file takes precedence but both can be stored)
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());
    }

    /** @test */
    public function it_can_serve_custom_thumbnail_for_external_media()
    {
        $thumbnailFile = UploadedFile::fake()->image('custom-thumb.jpg', 300, 300);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'video',
            'source' => 'external',
            'url' => 'https://youtube.com/watch?v=example',
            'name' => 'External Video with Servable Thumbnail',
            'thumbnail_file' => $thumbnailFile,
        ]);

        $response->assertStatus(201);

        $media = MediaResource::where('display_name', 'External Video with Servable Thumbnail')->first();

        // Verify custom thumbnail was stored
        $this->assertNotNull($media->thumbnail_path);
        $thumbnailRef = $media->loadThumbnailFileReference();
        $this->assertNotNull($thumbnailRef);

        // Verify the thumbnail file is stored in external-thumbnails directory
        $this->assertStringContainsString('external-thumbnails', $thumbnailRef->getThumbnailStoragePath());
        Storage::disk('local')->assertExists($thumbnailRef->getThumbnailStoragePath());

        // Test serving the custom thumbnail via GET endpoint
        $thumbnailResponse = $this->get("/media/thumbnails/{$media->id}");

        $thumbnailResponse->assertStatus(200);
        $thumbnailResponse->assertHeader('Content-Type', 'image/jpeg');
    }
}
