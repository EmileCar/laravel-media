<?php

namespace Carone\Media\Tests\Strategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\StoreMediaDataBuilder;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageStrategyTest extends TestCase
{
    protected ImageStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ImageStrategy();

        // Create a real test image for processing
        $this->createRealTestImage();
    }

    protected function createRealTestImage(): string
    {
        $image = Image::create(400, 300)->fill('ff0000'); // Red image
        $path = storage_path('app/test-image.jpg');
        $image->save($path);
        return $path;
    }

    protected function createRealUploadedFile(): UploadedFile
    {
        $testImagePath = $this->createRealTestImage();
        return new UploadedFile(
            $testImagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );
    }

    /** @test */
    public function it_can_store_image_without_processing()
    {
        Config::set('media.processing.image.enabled', false);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('Test Image', $result->display_name);
        $this->assertFalse($result->meta['processed']);
    }

    /** @test */
    public function it_can_store_image_with_processing_enabled()
    {
        Config::set('media.processing.image', [
            'enabled' => true,
            'quality' => 90,
            'convert_format' => null,
            'resize' => ['enabled' => false],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
            'optimize' => true,
        ]);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertTrue($result->meta['processed']);
    }

    /** @test */
    public function it_can_convert_image_format()
    {
        Config::set('media.processing.image', [
            'enabled' => true,
            'convert_format' => 'webp',
            'quality' => 80,
            'resize' => ['enabled' => false],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ]);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertEquals('webp', $result->meta['final_extension']);
    }

    /** @test */
    public function it_can_resize_image()
    {
        Config::set('media.processing.image', [
            'enabled' => true,
            'convert_format' => null,
            'quality' => 85,
            'resize' => [
                'enabled' => true,
                'width' => 200,
                'height' => 150,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ]);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertTrue($result->meta['processed']);
        // The image should be resized but we can't easily test exact dimensions without reading the file
    }

    /** @test */
    public function it_can_crop_image()
    {
        Config::set('media.processing.image', [
            'enabled' => true,
            'convert_format' => null,
            'quality' => 85,
            'resize' => ['enabled' => false],
            'crop' => [
                'enabled' => true,
                'width' => 100,
                'height' => 100,
                'position' => 'center',
            ],
            'watermark' => ['enabled' => false],
        ]);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertTrue($result->meta['processed']);
    }

    /** @test */
    public function it_can_generate_thumbnail()
    {
        Config::set('media.processing.image.enabled', true);
        Config::set('media.processing.thumbnail', [
            'convert_format' => 'jpg',
            'quality' => 80,
            'resize' => [
                'width' => 150,
                'height' => 150,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ],
        ]);

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->withThumbnail(true)
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        $this->assertNotNull($result->meta['thumbnail_path']);
        $this->assertStringContainsString('_thumb', $result->meta['thumbnail_path']);
    }

    /** @test */
    public function it_skips_thumbnail_for_non_image_types()
    {
        $file = $this->createFakeVideoFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::VIDEO)
            ->withName('Test Video')
            ->withThumbnail(true) // This should be ignored for video
            ->build();

        // We'll test this with MediaStrategy since video doesn't support thumbnails
        $strategy = new class extends \Carone\Media\Strategies\MediaStrategy {
            public function getType(): MediaType {
                return MediaType::VIDEO;
            }
        };

        $result = $strategy->storeLocalFile($data);
        $this->assertNull($result->meta['thumbnail_path'] ?? null);
    }

    /** @test */
    public function thumbnail_generation_fails_gracefully()
    {
        Config::set('media.processing.thumbnail', []); // Empty config should make it fail gracefully

        $file = $this->createRealUploadedFile();
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->withThumbnail(true)
            ->build();

        $result = $this->strategy->storeLocalFile($data);

        // Should still create the main image even if thumbnail fails
        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertNull($result->meta['thumbnail_path']);
    }

    /** @test */
    public function builder_supports_thumbnail_option()
    {
        $file = $this->createRealUploadedFile();

        $dataWithThumbnail = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withThumbnail(true)
            ->build();

        $dataWithoutThumbnail = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withThumbnail(false)
            ->build();

        $this->assertTrue($dataWithThumbnail->generateThumbnail);
        $this->assertFalse($dataWithoutThumbnail->generateThumbnail);
    }

    /** @test */
    public function builder_defaults_to_no_thumbnail()
    {
        $file = $this->createRealUploadedFile();

        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->build();

        $this->assertFalse($data->generateThumbnail);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $testImagePath = storage_path('app/test-image.jpg');
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }

        parent::tearDown();
    }
}
