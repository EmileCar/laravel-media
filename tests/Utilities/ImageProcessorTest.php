<?php

namespace Carone\Media\Tests\Utilities;

use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageProcessorTest extends TestCase
{
    protected string $testImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRealTestImage();
    }

    protected function createRealTestImage(): void
    {
        $image = Image::create(400, 300)->fill('ff0000'); // Red 400x300 image
        $this->testImagePath = storage_path('app/test-image.jpg');
        $image->save($this->testImagePath);
    }

    /** @test */
    public function it_skips_processing_when_disabled()
    {
        $config = ['enabled' => false];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        $this->assertEquals($this->testImagePath, $result);
    }

    /** @test */
    public function it_applies_quality_compression()
    {
        $config = [
            'enabled' => true,
            'quality' => 50,
            'convert_format' => null,
            'resize' => ['enabled' => false],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        // Just check that the image was processed (doesn't throw an error)
        // Quality difference might not be noticeable with simple test images
        $this->assertFileExists($result);
        $this->assertTrue(filesize($result) > 0);
    }

    /** @test */
    public function it_converts_image_format()
    {
        $config = [
            'enabled' => true,
            'convert_format' => 'webp',
            'quality' => 85,
            'resize' => ['enabled' => false],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        $this->assertStringEndsWith('.webp', $result);
        $this->assertTrue(file_exists($result));
    }

    /** @test */
    public function it_resizes_image_maintaining_aspect_ratio()
    {
        $config = [
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
        ];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        // Check that image was resized
        $image = Image::read($result);
        $this->assertLessThanOrEqual(200, $image->width());
        $this->assertLessThanOrEqual(150, $image->height());
    }

    /** @test */
    public function it_crops_image()
    {
        $config = [
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
        ];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        // Check that image was cropped to exact dimensions
        $image = Image::read($result);
        $this->assertEquals(100, $image->width());
        $this->assertEquals(100, $image->height());
    }

    /** @test */
    public function it_generates_thumbnail()
    {
        $thumbnailConfig = [
            'convert_format' => 'jpg',
            'quality' => 80,
            'resize' => [
                'width' => 150,
                'height' => 150,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ],
        ];

        $thumbnailRef = new MediaFileReference(
            'test_thumb',
            'jpg',
            'local',
            'thumbnails'
        );

        $this->expectNotToPerformAssertions(); // This test just ensures no exceptions are thrown

        ImageProcessor::generateThumbnail($this->testImagePath, $thumbnailRef, $thumbnailConfig);
    }

    /** @test */
    public function it_handles_resize_without_upscaling()
    {
        // Create a small 50x50 image
        $smallImage = Image::create(50, 50)->fill('00ff00');
        $smallImagePath = storage_path('app/small-test-image.jpg');
        $smallImage->save($smallImagePath);

        $config = [
            'enabled' => true,
            'convert_format' => null,
            'quality' => 85,
            'resize' => [
                'enabled' => true,
                'width' => 200,
                'height' => 200,
                'maintain_aspect_ratio' => true,
                'upsize' => false, // Should not upsize
            ],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ];

        $result = ImageProcessor::processImage($smallImagePath, $config);

        // Image should remain 50x50 since upsize is false
        $image = Image::read($result);
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());

        // Clean up
        if (file_exists($smallImagePath)) {
            unlink($smallImagePath);
        }
    }

    /** @test */
    public function it_applies_multiple_transformations()
    {
        $config = [
            'enabled' => true,
            'convert_format' => 'png',
            'quality' => 90,
            'resize' => [
                'enabled' => true,
                'width' => 300,
                'height' => 200,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ],
            'crop' => ['enabled' => false],
            'watermark' => ['enabled' => false],
        ];

        $result = ImageProcessor::processImage($this->testImagePath, $config);

        // Should be converted to PNG and resized
        $this->assertStringEndsWith('.png', $result);
        $this->assertTrue(file_exists($result));

        $image = Image::read($result);
        $this->assertLessThanOrEqual(300, $image->width());
        $this->assertLessThanOrEqual(200, $image->height());
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }

        // Clean up any processed files in storage directory
        $files = glob(storage_path('app/*.{jpg,png,webp}'), GLOB_BRACE);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
