<?php

namespace Carone\Media\Tests\Http\Controllers;

use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.storage_path' => 'media/{path}']);
        config(['media.disk' => 'public']);
        config(['media.management_middleware' => []]); // Disable auth for tests
        Storage::fake('public');
    }

    /** @test */
    public function it_returns_enabled_media_types()
    {
        config(['media.enabled_types' => ['image', 'video']]);

        $response = $this->getJson('/api/media/types');

        $response->assertOk()
            ->assertJsonStructure(['types']);
    }

    /** @test */
    public function it_gets_media_by_type_with_pagination()
    {
        MediaResource::factory()->count(5)->create(['type' => 'image']);
        MediaResource::factory()->count(3)->create(['type' => 'video']);

        $response = $this->getJson('/api/media/type/image?limit=10&offset=0');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'total',
                'limit',
                'offset',
            ])
            ->assertJson([
                'total' => 5,
                'limit' => 10,
                'offset' => 0,
            ]);
    }

    /** @test */
    public function it_validates_media_type_parameter()
    {
        $response = $this->getJson('/api/media/type/invalid_type');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid media type']);
    }

    /** @test */
    public function it_limits_maximum_items_per_page()
    {
        $response = $this->getJson('/api/media/type/image?limit=200');

        $response->assertOk();
        $this->assertEquals(100, $response->json('limit'));
    }

    /** @test */
    public function it_searches_media_by_query()
    {
        MediaResource::factory()->create([
            'display_name' => 'Beach Photo',
            'type' => 'image',
        ]);
        MediaResource::factory()->create([
            'display_name' => 'Mountain Video',
            'type' => 'video',
        ]);

        $response = $this->getJson('/api/media/search?q=Beach');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'total',
                'limit',
                'offset',
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('total'));
    }

    /** @test */
    public function it_validates_search_query_is_required()
    {
        $response = $this->getJson('/api/media/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_validates_search_query_length()
    {
        $response = $this->getJson('/api/media/search?q=' . str_repeat('a', 256));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_filters_search_by_type()
    {
        MediaResource::factory()->create([
            'display_name' => 'Beach Photo',
            'type' => 'image',
        ]);
        MediaResource::factory()->create([
            'display_name' => 'Beach Video',
            'type' => 'video',
        ]);

        $response = $this->getJson('/api/media/search?q=Beach&type=image');

        $response->assertOk();

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('image', $item['type']);
        }
    }

    /** @test */
    public function it_uploads_local_image_file()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'description' => 'A test image',
            'directory' => 'uploads',
            'generate_thumbnail' => false,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'source',
                    'display_name',
                ],
            ]);

        $this->assertDatabaseHas('media_resources', [
            'display_name' => 'Test Image',
            'type' => 'image',
            'source' => 'local',
        ]);
    }

    /** @test */
    public function it_uploads_external_media_url()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'external',
            'url' => 'https://example.com/image.jpg',
            'name' => 'External Image',
            'description' => 'An external image',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Media uploaded successfully',
            ]);

        $this->assertDatabaseHas('media_resources', [
            'display_name' => 'External Image',
            'type' => 'image',
            'source' => 'external',
            'url' => 'https://example.com/image.jpg',
        ]);
    }

    /** @test */
    public function it_validates_upload_request()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'invalid',
            'source' => 'local',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'file', 'name']);
    }

    /** @test */
    public function it_requires_file_for_local_upload()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'name' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_requires_url_for_external_upload()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'external',
            'name' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_rejects_unsupported_file_types()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test File',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_gets_media_by_id()
    {
        $media = MediaResource::factory()->create([
            'display_name' => 'Test Media',
            'type' => 'image',
            'source' => 'local',
        ]);

        $response = $this->getJson("/api/media/{$media->id}");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson([
                'data' => [
                    'id' => $media->id,
                    'name' => 'Test Media',
                    'type' => 'image',
                    'source' => 'local',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'date',
                    'type',
                    'source',
                    'thumbnail_url',
                    'media_url',
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_media()
    {
        $response = $this->getJson('/api/media/99999');

        $response->assertNotFound()
            ->assertJson(['error' => 'Media not found']);
    }

    /** @test */
    public function it_deletes_media()
    {
        $media = MediaResource::factory()->create();

        $response = $this->deleteJson("/api/media/{$media->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        $this->assertDatabaseMissing('media_resources', [
            'id' => $media->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_media()
    {
        $response = $this->deleteJson('/api/media/99999');

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Media not found',
            ]);
    }

    /** @test */
    public function it_bulk_deletes_media()
    {
        $media1 = MediaResource::factory()->create();
        $media2 = MediaResource::factory()->create();
        $media3 = MediaResource::factory()->create();

        $response = $this->deleteJson('/api/media/bulk', [
            'ids' => [$media1->id, $media2->id, $media3->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => 3,
                'failed' => 0,
            ]);

        $this->assertDatabaseMissing('media_resources', ['id' => $media1->id]);
        $this->assertDatabaseMissing('media_resources', ['id' => $media2->id]);
        $this->assertDatabaseMissing('media_resources', ['id' => $media3->id]);
    }

    /** @test */
    public function it_validates_bulk_delete_ids()
    {
        $response = $this->deleteJson('/api/media/bulk', [
            'ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_limits_bulk_delete_to_100_items()
    {
        $ids = range(1, 101);

        $response = $this->deleteJson('/api/media/bulk', [
            'ids' => $ids,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function it_handles_partial_bulk_delete_failures()
    {
        $media1 = MediaResource::factory()->create();

        $response = $this->deleteJson('/api/media/bulk', [
            'ids' => [$media1->id, 99999], // One valid, one invalid
        ]);

        $response->assertOk();

        $this->assertEquals(1, $response->json('deleted'));
        $this->assertEquals(1, $response->json('failed'));
        $this->assertDatabaseMissing('media_resources', ['id' => $media1->id]);
    }

    /** @test */
    public function it_serves_local_media_file()
    {
        config(['media.storage_path' => 'media/{path}']);
        config(['media.disk' => 'public']);

        $testContent = 'test image content';
        Storage::disk('public')->put('media/test/image.jpg', $testContent);

        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/image.jpg',
            'type' => 'image'
        ]);

        // Now use path instead of ID
        $response = $this->get("/media/{$media->path}");

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_media_file()
    {
        config(['media.disk' => 'public']);

        $response = $this->get('/media/nonexistent/path.jpg');

        $response->assertNotFound();
    }

    /** @test */
    public function it_serves_thumbnail()
    {
        config(['media.storage_path' => 'media/{path}']);
        config(['media.thumbnails.storage_path' => 'media/thumbnails/{path}']);

        $testContent = 'test thumbnail content';
        Storage::disk('public')->put('media/thumbnails/test/image_thumb.jpg', $testContent);

        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/image.jpg',
            'thumbnail_path' => 'test/image_thumb.jpg',
            'type' => 'image',
        ]);

        $response = $this->get("/media/thumbnails/{$media->id}");

        $response->assertOk();
    }

    /** @test */
    public function it_returns_404_for_nonexistent_thumbnail()
    {
        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'test/image.jpg',
            'type' => 'image'
        ]);

        $response = $this->get("/media/thumbnails/{$media->id}");

        $response->assertNotFound();
    }
}
