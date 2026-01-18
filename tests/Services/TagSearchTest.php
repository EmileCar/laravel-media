<?php

namespace Tests\Services;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Media\Models\MediaResource;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class TagSearchTest extends TestCase
{
    private GetMediaService $getMediaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMediaService = app(GetMediaService::class);
        Config::set('media.tags.enabled', true);
    }

    /** @test */
    public function it_can_search_media_by_single_tag()
    {
        // Create media with tags
        $media1 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 1']);
        $media1->syncTags(['Nature', 'Photography']);

        $media2 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 2']);
        $media2->syncTags(['Wildlife', 'Photography']);

        $media3 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 3']);
        $media3->syncTags(['Urban']);

        // Search by single tag
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: ['tags' => ['Nature']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('Image 1', $results->items()[0]->display_name);
    }

    /** @test */
    public function it_can_search_media_by_multiple_tags()
    {
        $media1 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 1']);
        $media1->syncTags(['Nature', 'Photography']);

        $media2 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 2']);
        $media2->syncTags(['Wildlife']);

        $media3 = MediaResource::factory()->create(['type' => 'image', 'display_name' => 'Image 3']);
        $media3->syncTags(['Urban', 'Photography']);

        // Search by multiple tags (OR logic)
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: ['tags' => ['Nature', 'Wildlife']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(2, $results->total());
    }

    /** @test */
    public function it_can_search_by_tag_slug()
    {
        $media = MediaResource::factory()->create(['type' => 'image']);
        $media->syncTags(['Beautiful Landscape']);

        // Search using slugified version
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: ['tags' => ['beautiful-landscape']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(1, $results->total());
    }

    /** @test */
    public function it_can_combine_tag_and_type_filters()
    {
        $image1 = MediaResource::factory()->create(['type' => 'image']);
        $image1->syncTags(['Nature']);

        $video1 = MediaResource::factory()->create(['type' => 'video']);
        $video1->syncTags(['Nature']);

        $image2 = MediaResource::factory()->create(['type' => 'image']);
        $image2->syncTags(['Urban']);

        // Search for images with Nature tag
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: [
                'type' => ['image'],
                'tags' => ['Nature'],
            ]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('image', $results->items()[0]->type);
    }

    /** @test */
    public function it_can_combine_search_term_with_tag_filter()
    {
        $media1 = MediaResource::factory()->create([
            'type' => 'image',
            'display_name' => 'Beautiful Sunset',
        ]);
        $media1->syncTags(['Nature']);

        $media2 = MediaResource::factory()->create([
            'type' => 'image',
            'display_name' => 'Beautiful Mountain',
        ]);
        $media2->syncTags(['Landscape']);

        $media3 = MediaResource::factory()->create([
            'type' => 'image',
            'display_name' => 'City Lights',
        ]);
        $media3->syncTags(['Nature']);

        // Search for "Beautiful" with Nature tag
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm('Beautiful'),
            filters: ['tags' => ['Nature']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('Beautiful Sunset', $results->items()[0]->display_name);
    }

    /** @test */
    public function it_returns_empty_results_when_tag_does_not_exist()
    {
        $media = MediaResource::factory()->create(['type' => 'image']);
        $media->syncTags(['Nature']);

        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: ['tags' => ['NonExistentTag']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        $this->assertEquals(0, $results->total());
    }

    /** @test */
    public function it_does_not_filter_by_tags_when_tags_are_disabled()
    {
        Config::set('media.tags.enabled', false);

        $media1 = MediaResource::factory()->create(['type' => 'image']);
        $media2 = MediaResource::factory()->create(['type' => 'image']);

        // Try to filter by tags (should be ignored)
        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: ['tags' => ['Nature']]
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        // Should return all media since tag filter is ignored
        $this->assertEquals(2, $results->total());
    }

    /** @test */
    public function it_returns_all_tags_with_counts()
    {
        $media1 = MediaResource::factory()->create(['type' => 'image']);
        $media1->syncTags(['Nature', 'Photography']);

        $media2 = MediaResource::factory()->create(['type' => 'image']);
        $media2->syncTags(['Nature', 'Wildlife']);

        $media3 = MediaResource::factory()->create(['type' => 'image']);
        $media3->syncTags(['Urban']);

        $tags = $this->getMediaService->getAllTags();

        $this->assertCount(4, $tags);

        $natureTag = collect($tags)->firstWhere('name', 'Nature');
        $this->assertEquals(2, $natureTag['count']);

        $urbanTag = collect($tags)->firstWhere('name', 'Urban');
        $this->assertEquals(1, $urbanTag['count']);
    }

    /** @test */
    public function it_returns_empty_tags_when_disabled()
    {
        Config::set('media.tags.enabled', false);

        $media = MediaResource::factory()->create(['type' => 'image']);
        $media->syncTags(['Nature']);

        $tags = $this->getMediaService->getAllTags();

        $this->assertEmpty($tags);
    }

    /** @test */
    public function it_eager_loads_tags_when_searching()
    {
        Config::set('media.tags.enabled', true);

        $media = MediaResource::factory()->create(['type' => 'image']);
        $media->syncTags(['Nature']);

        $criteria = new SearchCriteria(
            searchTerm: new SearchTerm(''),
            filters: []
        );

        $results = $this->getMediaService->search($criteria, 0, 20);

        // Tags should be eager loaded
        $this->assertTrue($results->items()[0]->relationLoaded('tags'));
    }
}
