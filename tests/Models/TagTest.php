<?php

namespace Tests\Models;

use Carone\Media\Models\MediaResource;
use Carone\Media\Models\Tag;
use Carone\Media\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_tag()
    {
        $tag = Tag::create([
            'name' => 'Nature',
            'slug' => 'nature',
        ]);

        $this->assertDatabaseHas('media_tags', [
            'name' => 'Nature',
            'slug' => 'nature',
        ]);
    }

    /** @test */
    public function it_automatically_generates_slug_when_creating()
    {
        $tag = Tag::create(['name' => 'Beautiful Landscape']);

        $this->assertEquals('beautiful-landscape', $tag->slug);
    }

    /** @test */
    public function it_can_find_or_create_tag_by_name()
    {
        $tag1 = Tag::findOrCreateByName('Wildlife');

        $this->assertDatabaseHas('media_tags', [
            'name' => 'Wildlife',
            'slug' => 'wildlife',
        ]);

        // Should not create duplicate
        $tag2 = Tag::findOrCreateByName('Wildlife');
        $this->assertEquals($tag1->id, $tag2->id);
        $this->assertEquals(1, Tag::count());
    }

    /** @test */
    public function it_finds_existing_tag_with_different_casing()
    {
        Tag::findOrCreateByName('Nature');
        $tag = Tag::findOrCreateByName('NATURE');

        $this->assertEquals(1, Tag::count());
        $this->assertEquals('nature', $tag->slug);
    }

    /** @test */
    public function it_can_find_or_create_multiple_tags()
    {
        $tags = Tag::findOrCreateByNames(['Nature', 'Wildlife', 'Photography']);

        $this->assertCount(3, $tags);
        $this->assertEquals(3, Tag::count());
        $this->assertTrue($tags->pluck('name')->contains('Nature'));
    }

    /** @test */
    public function it_generates_slug_from_tag_name()
    {
        $this->assertEquals('travel-photos', Tag::generateSlug('Travel Photos'));
        $this->assertEquals('nature-wildlife', Tag::generateSlug('Nature & Wildlife'));
        $this->assertEquals('my-tag', Tag::generateSlug('  My Tag  '));
    }

    /** @test */
    public function it_has_media_resources_relationship()
    {
        $tag = Tag::create(['name' => 'Test Tag']);
        $media = MediaResource::factory()->create([
            'type' => 'image',
            'source' => 'local',
        ]);

        $tag->mediaResources()->attach($media->id);

        $this->assertCount(1, $tag->mediaResources);
        $this->assertEquals($media->id, $tag->mediaResources->first()->id);
    }
}
