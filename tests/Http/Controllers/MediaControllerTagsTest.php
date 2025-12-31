<?php

namespace Tests\Http\Controllers;

use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class MediaControllerTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Config::set('media.tags.enabled', true);
    }

    /** @test */
    public function it_can_upload_media_with_tags_via_api()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'description' => 'Test description',
            'tags' => ['Nature', 'Photography'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'type'],
        ]);

        $this->assertDatabaseHas('media_tags', ['name' => 'Nature']);
        $this->assertDatabaseHas('media_tags', ['name' => 'Photography']);
    }

    /** @test */
    public function it_validates_tags_must_be_array()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'tags' => 'NotAnArray',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags');
    }

    /** @test */
    public function it_validates_tag_names_must_be_strings()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'tags' => [123, 456],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags.0');
    }

    /** @test */
    public function it_validates_tag_names_max_length()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'tags' => [str_repeat('a', 51)],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags.0');
    }

    /** @test */
    public function it_validates_max_tags_limit()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $tags = array_map(fn($i) => "Tag$i", range(1, 21));

        $response = $this->postJson('/api/media/upload', [
            'type' => 'image',
            'source' => 'local',
            'file' => $file,
            'name' => 'Test Image',
            'tags' => $tags,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags');
    }

    /** @test */
    public function it_can_search_media_by_tags()
    {
        $media1 = MediaResource::factory()->create([
            'type' => 'image',
            'display_name' => 'Image 1',
        ]);
        $media1->syncTags(['Nature', 'Photography']);

        $media2 = MediaResource::factory()->create([
            'type' => 'image',
            'display_name' => 'Image 2',
        ]);
        $media2->syncTags(['Urban']);

        $response = $this->getJson('/api/media/search?q=Image&tags[]=Nature');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Image 1');
    }

    /** @test */
    public function it_can_search_with_multiple_tag_filters()
    {
        $media1 = MediaResource::factory()->create(['display_name' => 'Image 1']);
        $media1->syncTags(['Nature']);

        $media2 = MediaResource::factory()->create(['display_name' => 'Image 2']);
        $media2->syncTags(['Wildlife']);

        $media3 = MediaResource::factory()->create(['display_name' => 'Image 3']);
        $media3->syncTags(['Urban']);

        $response = $this->getJson('/api/media/search?q=Image&tags[]=Nature&tags[]=Wildlife');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_includes_tags_in_search_results()
    {
        Config::set('media.tags.enabled', true);

        $media = MediaResource::factory()->create(['display_name' => 'Test Image']);
        $media->syncTags(['Nature', 'Photography']);

        $response = $this->getJson('/api/media/search?q=Test');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.tags', ['Nature', 'Photography']);
    }

    /** @test */
    public function it_can_get_all_tags()
    {
        $media1 = MediaResource::factory()->create();
        $media1->syncTags(['Nature', 'Photography']);

        $media2 = MediaResource::factory()->create();
        $media2->syncTags(['Nature', 'Wildlife']);

        $response = $this->getJson('/api/media/tags');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tags' => [
                '*' => ['id', 'name', 'slug', 'count']
            ]
        ]);

        $tags = $response->json('tags');
        $this->assertCount(3, $tags);

        $natureTag = collect($tags)->firstWhere('name', 'Nature');
        $this->assertEquals(2, $natureTag['count']);
    }

    /** @test */
    public function it_returns_403_when_getting_tags_with_tags_disabled()
    {
        Config::set('media.tags.enabled', false);

        $response = $this->getJson('/api/media/tags');

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Tags functionality is disabled');
    }

    /** @test */
    public function it_does_not_include_tags_in_response_when_disabled()
    {
        Config::set('media.tags.enabled', false);

        $media = MediaResource::factory()->create(['display_name' => 'Test Image']);

        $response = $this->getJson('/api/media/search?q=Test');

        $response->assertStatus(200);
        $response->assertJsonMissing(['tags']);
    }

    /** @test */
    public function it_includes_tags_when_getting_media_by_id()
    {
        Config::set('media.tags.enabled', true);

        $media = MediaResource::factory()->create();
        $media->syncTags(['Nature', 'Photography']);

        $response = $this->getJson("/api/media/{$media->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.tags', ['Nature', 'Photography']);
    }

    /** @test */
    public function it_can_upload_external_media_with_tags()
    {
        $response = $this->postJson('/api/media/upload', [
            'type' => 'video',
            'source' => 'external',
            'url' => 'https://example.com/video.mp4',
            'name' => 'External Video',
            'tags' => ['Video', 'External'],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('media_tags', ['name' => 'Video']);
        $this->assertDatabaseHas('media_tags', ['name' => 'External']);
    }
}
