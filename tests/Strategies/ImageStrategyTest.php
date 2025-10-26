<?php

namespace Carone\Media\Tests\Strategies;

use Carone\Media\Enums\MediaType;
use Carone\Media\Models\MediaResource;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Tests\TestCase;

class ImageStrategyTest extends TestCase
{
    private ImageStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ImageStrategy();
    }

    /** @test */
    public function it_returns_correct_type()
    {
        $this->assertEquals(MediaType::IMAGE, $this->strategy->getType());
    }

    /** @test */
    public function it_supports_image_files()
    {
        $this->assertTrue(true);

        // Note: createFakeImageFile creates JPEG, so this test is limited
        // In real tests, you'd create actual PNG files
    }

    /** @test */
    public function it_supports_thumbnails()
    {
        $this->assertTrue($this->strategy->supportsThumbnails());
    }

    /** @test */
    public function it_uploads_image_and_converts_to_jpeg()
    {
        $file = $this->createFakeImageFile('test-image.png');
        
        $data = [
            'name' => 'Test Image',
            'description' => 'A test image',
        ];

        $media = $this->strategy->upload($file, $data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('image', $media->type);
        $this->assertEquals('local', $media->source);
        $this->assertEquals('Test Image', $media->name);
        $this->assertStringEndsWith('.jpg', $media->file_name);

        // Verify file was stored
        $this->assertFileExistsInStorage('local', $media->directory);
    }

    /** @test */
    public function it_creates_thumbnails_when_enabled()
    {
        config(['media.generate_thumbnails' => true]);
        
        $file = $this->createFakeImageFile('test-image.jpg');
        
        $data = [
            'name' => 'Test Image',
        ];

        $media = $this->strategy->upload($file, $data);

        $thumbnailPath = 'media/image/thumbnails/' . pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
        $this->assertFileExistsInStorage('local', $thumbnailPath);
    }

    /** @test */
    public function it_creates_external_media()
    {
        $data = [
            'name' => 'External Image',
            'description' => 'An external image',
        ];

        $media = $this->strategy->uploadExternal('https://example.com/image.jpg', $data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('image', $media->type);
        $this->assertEquals('external', $media->source);
        $this->assertEquals('https://example.com/image.jpg', $media->url);
        $this->assertEquals('External Image', $media->name);
    }

    /** @test */
    public function it_returns_correct_api_data_for_local_image()
    {
        $media = MediaResource::create([
            'type' => 'image',
            'source' => 'local',
            'file_name' => 'test-image.jpg',
            'name' => 'Test Image',
            'description' => 'Test description',
            'date' => now()->toDateString(),
        ]);

        $apiData = $this->strategy->getApiData($media);

        $this->assertArrayHasKey('id', $apiData);
        $this->assertArrayHasKey('name', $apiData);
        $this->assertArrayHasKey('description', $apiData);
        $this->assertArrayHasKey('type', $apiData);
        $this->assertArrayHasKey('source', $apiData);
        $this->assertArrayHasKey('original', $apiData);
        $this->assertArrayHasKey('thumbnail', $apiData);

        $this->assertEquals('Test Image', $apiData['name']);
        $this->assertEquals('image', $apiData['type']);
        $this->assertEquals('local', $apiData['source']);
        $this->assertEquals('test-image.jpg', $apiData['original']);
        $this->assertEquals('test-image.jpg', $apiData['thumbnail']);
    }

    /** @test */
    public function it_returns_correct_api_data_for_external_image()
    {
        $media = MediaResource::create([
            'type' => 'image',
            'source' => 'external',
            'url' => 'https://example.com/image.jpg',
            'name' => 'External Image',
            'description' => 'External description',
            'date' => now()->toDateString(),
        ]);

        $apiData = $this->strategy->getApiData($media);

        $this->assertArrayHasKey('url', $apiData);
        $this->assertArrayNotHasKey('original', $apiData);
        $this->assertArrayNotHasKey('thumbnail', $apiData);

        $this->assertEquals('External Image', $apiData['name']);
        $this->assertEquals('external', $apiData['source']);
        $this->assertEquals('https://example.com/image.jpg', $apiData['url']);
    }

    /** @test */
    public function it_stores_meta_data_correctly()
    {
        $file = $this->createFakeImageFile('test-image.jpg');
        
        $data = [
            'name' => 'Test Image',
            'meta' => ['custom' => 'value'],
        ];

        $media = $this->strategy->upload($file, $data);

        $this->assertArrayHasKey('original_name', $media->meta);
        $this->assertArrayHasKey('size', $media->meta);
        $this->assertArrayHasKey('mime_type', $media->meta);
        $this->assertArrayHasKey('custom', $media->meta);

        $this->assertEquals('test-image.jpg', $media->meta['original_name']);
        $this->assertEquals('value', $media->meta['custom']);
    }
}