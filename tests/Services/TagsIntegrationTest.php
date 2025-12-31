<?php

namespace Tests\Services;

use Carone\Media\Models\MediaResource;
use Carone\Media\Models\Tag;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class TagsIntegrationTest extends TestCase
{
    private StoreMediaService $storeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storeService = app(StoreMediaService::class);

        Storage::fake('public');
        Config::set('media.tags.enabled', true);
    }

    /** @test */
    public function it_can_upload_media_with_tags()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: 'Test description',
            date: now(),
            meta: [],
            directory: 'images',
            generateThumbnail: false,
            tags: ['Nature', 'Photography', 'Landscape'],
        );

        $media = $this->storeService->store($data);

        $this->assertNotNull($media);
        $this->assertCount(3, $media->tags);

        $tagNames = $media->tags->pluck('name')->toArray();
        $this->assertContains('Nature', $tagNames);
        $this->assertContains('Photography', $tagNames);
        $this->assertContains('Landscape', $tagNames);
    }

    /** @test */
    public function it_creates_tags_that_do_not_exist()
    {
        $this->assertEquals(0, Tag::count());

        $file = UploadedFile::fake()->image('test.jpg');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: now(),
            tags: ['NewTag1', 'NewTag2'],
        );

        $this->storeService->store($data);

        $this->assertEquals(2, Tag::count());
        $this->assertDatabaseHas('media_tags', ['name' => 'NewTag1']);
        $this->assertDatabaseHas('media_tags', ['name' => 'NewTag2']);
    }

    /** @test */
    public function it_reuses_existing_tags()
    {
        $existingTag = Tag::create(['name' => 'Nature', 'slug' => 'nature']);

        $file = UploadedFile::fake()->image('test.jpg');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: now(),
            tags: ['Nature', 'Wildlife'],
        );

        $media = $this->storeService->store($data);

        // Should have created only one new tag (Wildlife)
        $this->assertEquals(2, Tag::count());
        $this->assertTrue($media->tags->contains('id', $existingTag->id));
    }

    /** @test */
    public function it_can_upload_external_media_with_tags()
    {
        $data = new StoreExternalMediaData(
            type: MediaType::VIDEO,
            url: 'https://example.com/video.mp4',
            name: 'External Video',
            description: null,
            date: now(),
            tags: ['Video', 'External'],
        );

        $media = $this->storeService->store($data);

        $this->assertCount(2, $media->tags);
        $tagNames = $media->tags->pluck('name')->toArray();
        $this->assertContains('Video', $tagNames);
        $this->assertContains('External', $tagNames);
    }

    /** @test */
    public function it_does_not_add_tags_when_tags_are_disabled()
    {
        Config::set('media.tags.enabled', false);

        $file = UploadedFile::fake()->image('test.jpg');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: now(),
            tags: ['Nature', 'Wildlife'],
        );

        $media = $this->storeService->store($data);

        // Tags should not be synced when disabled
        $this->assertCount(0, $media->tags);
        $this->assertEquals(0, Tag::count());
    }

    /** @test */
    public function it_can_sync_tags_on_existing_media()
    {
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'local',
        ]);

        $media->syncTags(['Tag1', 'Tag2', 'Tag3']);

        $this->assertCount(3, $media->fresh()->tags);

        // Update tags
        $media->syncTags(['Tag2', 'Tag4']);

        $this->assertCount(2, $media->fresh()->tags);
        $tagNames = $media->fresh()->tags->pluck('name')->toArray();
        $this->assertContains('Tag2', $tagNames);
        $this->assertContains('Tag4', $tagNames);
        $this->assertNotContains('Tag1', $tagNames);
    }

    /** @test */
    public function it_handles_empty_tags_array()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: now(),
            tags: [],
        );

        $media = $this->storeService->store($data);

        $this->assertCount(0, $media->tags);
    }
}
