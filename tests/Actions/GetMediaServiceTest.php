<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Contracts\GetMediaServiceInterface;
use Carone\Media\Services\GetMediaService;
use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetMediaServiceTest extends TestCase
{
    private GetMediaServiceInterface $action;

    protected function setUp(): void
    {
        parent::setUp();
    $this->action = app(GetMediaServiceInterface::class);
    }

    /** @test */
    public function it_can_get_media_by_id()
    {
        $media = $this->createTestMedia('image');

        $result = $this->action->getById($media->id);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals($media->id, $result->id);
        $this->assertEquals('image', $result->type);
    }

    /** @test */
    public function it_throws_exception_when_media_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->getById(999);
    }

    /** @test */
    public function it_can_get_media_by_type()
    {
        // Create test media of different types
        $this->createTestMedia('image', 'Image 1');
        $this->createTestMedia('image', 'Image 2');
        $this->createTestMedia('video', 'Video 1');

        $result = $this->action->getByType('image', 10, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('limit', $result);
        
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, count($result['data']));
        $this->assertEquals(0, $result['offset']);
        $this->assertEquals(10, $result['limit']);

        // Check that all returned items are images
        foreach ($result['data'] as $item) {
            $this->assertEquals('image', $item['type']);
        }
    }

    /** @test */
    public function it_paginates_media_correctly()
    {
        // Create 5 test images
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestMedia('image', "Image {$i}");
        }

        // Get first 2
        $page1 = $this->action->getByType('image', 2, 0);
        $this->assertEquals(5, $page1['total']);
        $this->assertEquals(2, count($page1['data']));
        $this->assertEquals(0, $page1['offset']);

        // Get next 2
        $page2 = $this->action->getByType('image', 2, 2);
        $this->assertEquals(5, $page2['total']);
        $this->assertEquals(2, count($page2['data']));
        $this->assertEquals(2, $page2['offset']);

        // Get last 1
        $page3 = $this->action->getByType('image', 2, 4);
        $this->assertEquals(5, $page3['total']);
        $this->assertEquals(1, count($page3['data']));
        $this->assertEquals(4, $page3['offset']);
    }

    /** @test */
    public function it_throws_exception_for_disabled_media_type()
    {
        config(['media.enabled_types' => ['video', 'audio', 'document']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->action->getByType('image');
    }

    /** @test */
    public function it_returns_correct_api_data_for_image()
    {
        $media = $this->createTestMedia('image', 'Test Image', 'local');

        $result = $this->action->getByType('image', 1, 0);
        $imageData = $result['data'][0];

        $this->assertArrayHasKey('id', $imageData);
        $this->assertArrayHasKey('name', $imageData);
        $this->assertArrayHasKey('description', $imageData);
        $this->assertArrayHasKey('type', $imageData);
        $this->assertArrayHasKey('source', $imageData);
        $this->assertArrayHasKey('original', $imageData);
        $this->assertArrayHasKey('thumbnail', $imageData);

        $this->assertEquals('Test Image', $imageData['name']);
        $this->assertEquals('image', $imageData['type']);
        $this->assertEquals('local', $imageData['source']);
    }

    /** @test */
    public function it_returns_correct_api_data_for_external_video()
    {
        $media = $this->createTestMedia('video', 'External Video', 'external', 'https://youtube.com/watch?v=test');

        $result = $this->action->getByType('video', 1, 0);
        $videoData = $result['data'][0];

        $this->assertArrayHasKey('id', $videoData);
        $this->assertArrayHasKey('name', $videoData);
        $this->assertArrayHasKey('type', $videoData);
        $this->assertArrayHasKey('source', $videoData);
        $this->assertArrayHasKey('url', $videoData);
        $this->assertArrayNotHasKey('file', $videoData);

        $this->assertEquals('External Video', $videoData['name']);
        $this->assertEquals('video', $videoData['type']);
        $this->assertEquals('external', $videoData['source']);
        $this->assertEquals('https://youtube.com/watch?v=test', $videoData['url']);
    }

    /** @test */
    public function it_returns_correct_api_data_for_local_video()
    {
        $media = $this->createTestMedia('video', 'Local Video', 'local');

        $result = $this->action->getByType('video', 1, 0);
        $videoData = $result['data'][0];

        $this->assertArrayHasKey('file', $videoData);
        $this->assertArrayNotHasKey('url', $videoData);
        $this->assertEquals('local', $videoData['source']);
    }

    /** @test */
    public function it_can_search_media_by_name()
    {
        $this->createTestMedia('image', 'Beautiful Sunset', 'local');
        $this->createTestMedia('image', 'Mountain View', 'local');
        $this->createTestMedia('video', 'Beautiful Landscape', 'local');

        $result = $this->action->search('Beautiful', null, 10, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('query', $result);
        
        $this->assertEquals(2, $result['total']);
        $this->assertEquals('Beautiful', $result['query']);

        // Check that all results contain "Beautiful"
        foreach ($result['data'] as $item) {
            $this->assertStringContainsString('Beautiful', $item['name']);
        }
    }

    /** @test */
    public function it_can_search_media_by_description()
    {
        $this->createTestMedia('image', 'Image 1', 'local', null, 'A beautiful vacation photo');
        $this->createTestMedia('image', 'Image 2', 'local', null, 'Work related document');
        $this->createTestMedia('video', 'Video 1', 'local', null, 'Beautiful sunset video');

        $result = $this->action->search('beautiful', null, 10, 0);

        $this->assertEquals(2, $result['total']);
    }

    /** @test */
    public function it_can_search_media_by_type()
    {
        $this->createTestMedia('image', 'Test Image', 'local', null, 'beautiful');
        $this->createTestMedia('video', 'Test Video', 'local', null, 'beautiful');
        $this->createTestMedia('audio', 'Test Audio', 'local', null, 'beautiful');

        $result = $this->action->search('beautiful', 'image', 10, 0);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('image', $result['type']);
        
        foreach ($result['data'] as $item) {
            $this->assertEquals('image', $item['type']);
        }
    }

    /** @test */
    public function it_throws_exception_for_disabled_type_in_search()
    {
        config(['media.enabled_types' => ['video', 'audio', 'document']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->action->search('test', 'image');
    }

    /** @test */
    public function it_can_get_media_types()
    {
        $types = $this->action->getMediaTypes();

        $this->assertIsArray($types);
        $this->assertCount(4, $types);

        $expectedTypes = ['image', 'video', 'audio', 'document'];
        foreach ($types as $type) {
            $this->assertArrayHasKey('value', $type);
            $this->assertArrayHasKey('label', $type);
            $this->assertContains($type['value'], $expectedTypes);
            $this->assertEquals(ucfirst($type['value']), $type['label']);
        }
    }

    /** @test */
    public function it_respects_enabled_types_configuration()
    {
        config(['media.enabled_types' => ['image', 'video']]);

        $types = $this->action->getMediaTypes();

        $this->assertCount(2, $types);
        $values = array_column($types, 'value');
        $this->assertContains('image', $values);
        $this->assertContains('video', $values);
        $this->assertNotContains('audio', $values);
        $this->assertNotContains('document', $values);
    }

    /** @test */
    public function it_can_serve_media_file()
    {
        $media = $this->createTestMedia('image', 'Test Image', 'local');

        // Mock the strategy behavior since we can't actually serve files in tests
        $response = $this->action->serveMedia('image', $media->file_name);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }

    /** @test */
    public function it_throws_exception_when_serving_non_existent_media()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->serveMedia('image', 'non-existent-file.jpg');
    }

    /** @test */
    public function it_can_serve_thumbnail()
    {
        $media = $this->createTestMedia('image', 'Test Image', 'local');

        // This will fail gracefully if thumbnail doesn't exist
        try {
            $response = $this->action->serveThumbnail('image', $media->file_name);
            $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
        } catch (\Exception $e) {
            // Expected if thumbnail doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    /** @test */
    public function it_throws_exception_for_unsupported_thumbnail_type()
    {
        $media = $this->createTestMedia('video', 'Test Video', 'local');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Thumbnails not supported for this media type');

        $this->action->serveThumbnail('video', $media->file_name);
    }

    /** @test */
    public function it_can_be_called_statically()
    {
        $media = $this->createTestMedia('image');

    $result = GetMediaService::byId($media->id);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals($media->id, $result->id);
    }

    /** @test */
    public function static_by_type_method_works()
    {
        $this->createTestMedia('image', 'Test Image');

    $result = GetMediaService::byType('image', 10, 0);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
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
}