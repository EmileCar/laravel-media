<?php

namespace Carone\Media\Tests\Utilities;

use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Mockery;

class ImageProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_generateThumbnail_creates_thumbnail_file()
    {
        // Create a fake image file
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $imagePath = $originalFile->getRealPath();

        // Create file reference for thumbnail
        $thumbnailReference = new MediaFileReference(
            'test_thumb',
            'jpg',
            'public',
            'thumbnails'
        );

        $config = [
            'convert_format' => 'jpg',
            'quality' => 80,
            'resize' => [
                'width' => 300,
                'height' => 300,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ]
        ];

        // Mock file storage to avoid actual file operations
        Storage::shouldReceive('disk->put')->once();

        ImageProcessor::generateThumbnail($imagePath, $thumbnailReference, $config);

        // If we reach here without exception, the method worked
        $this->assertTrue(true);
    }

    public function test_applyResize_with_aspect_ratio_maintained()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = Image::read($originalFile);

        $config = [
            'width' => 400,
            'height' => 300,
            'maintain_aspect_ratio' => true,
            'upsize' => false,
        ];

        $resizedImage = ImageProcessor::applyResize($image, $config);

        $this->assertInstanceOf(ImageInterface::class, $resizedImage);
        // The image should maintain aspect ratio
        $this->assertLessThanOrEqual(400, $resizedImage->width());
        $this->assertLessThanOrEqual(300, $resizedImage->height());
    }

    public function test_applyResize_without_aspect_ratio()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = Image::read($originalFile);

        $config = [
            'width' => 400,
            'height' => 300,
            'maintain_aspect_ratio' => false,
            'upsize' => false,
        ];

        $resizedImage = ImageProcessor::applyResize($image, $config);

        $this->assertInstanceOf(ImageInterface::class, $resizedImage);
        $this->assertEquals(400, $resizedImage->width());
        $this->assertEquals(300, $resizedImage->height());
    }

    public function test_applyResize_prevents_upsizing_when_disabled()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 200, 150);
        $image = Image::read($originalFile);

        $config = [
            'width' => 400,
            'height' => 300,
            'maintain_aspect_ratio' => true,
            'upsize' => false,
        ];

        $resizedImage = ImageProcessor::applyResize($image, $config);

        // Should not upsize the image
        $this->assertLessThanOrEqual(200, $resizedImage->width());
        $this->assertLessThanOrEqual(150, $resizedImage->height());
    }

    public function test_applyResize_allows_upsizing_when_enabled()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 200, 150);
        $image = Image::read($originalFile);

        $config = [
            'width' => 400,
            'height' => 300,
            'maintain_aspect_ratio' => true,
            'upsize' => true,
        ];

        $resizedImage = ImageProcessor::applyResize($image, $config);

        // Should allow upsizing the image
        $this->assertGreaterThan(200, $resizedImage->width());
    }

    public function test_applyCrop_crops_image_to_specified_dimensions()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = Image::read($originalFile);

        $config = [
            'width' => 400,
            'height' => 300,
            'position' => 'center',
        ];

        $croppedImage = ImageProcessor::applyCrop($image, $config);

        $this->assertInstanceOf(ImageInterface::class, $croppedImage);
        $this->assertEquals(400, $croppedImage->width());
        $this->assertEquals(300, $croppedImage->height());
    }

    public function test_applyCrop_with_different_positions()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = Image::read($originalFile);

        $positions = ['top-left', 'top', 'top-right', 'left', 'center', 'right', 'bottom-left', 'bottom', 'bottom-right'];

        foreach ($positions as $position) {
            $config = [
                'width' => 400,
                'height' => 300,
                'position' => $position,
            ];

            $croppedImage = ImageProcessor::applyCrop($image, $config);

            $this->assertInstanceOf(ImageInterface::class, $croppedImage);
            $this->assertEquals(400, $croppedImage->width());
            $this->assertEquals(300, $croppedImage->height());
        }
    }

    public function test_applyWatermark_returns_original_when_file_not_exists()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = Image::read($originalFile);

        $config = [
            'path' => '/nonexistent/watermark.png',
            'position' => 'bottom-right',
            'opacity' => 80,
            'margin' => 10,
        ];

        $result = ImageProcessor::applyWatermark($image, $config);

        // Should return the original image when watermark file doesn't exist
        $this->assertInstanceOf(ImageInterface::class, $result);
    }

    public function test_applyWatermark_applies_watermark_when_file_exists()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $watermarkFile = UploadedFile::fake()->image('watermark.png', 100, 50);

        $image = Image::read($originalFile);

        $config = [
            'path' => $watermarkFile->getRealPath(),
            'position' => 'bottom-right',
            'opacity' => 80,
            'margin' => 10,
        ];

        $result = ImageProcessor::applyWatermark($image, $config);

        $this->assertInstanceOf(ImageInterface::class, $result);
    }

    public function test_encodeAndSave_supports_different_formats()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 400, 300);
        $image = Image::read($originalFile);

        $formats = [
            'jpg' => 85,
            'jpeg' => 90,
            'png' => 80,
            'webp' => 75,
        ];

        foreach ($formats as $format => $quality) {
            $tempPath = tempnam(sys_get_temp_dir(), 'test_') . '.' . $format;

            ImageProcessor::encodeAndSave($image, $tempPath, $format, $quality);

            $this->assertFileExists($tempPath);
            $this->assertGreaterThan(0, filesize($tempPath));

            // Clean up
            unlink($tempPath);
        }
    }

    public function test_encodeAndSave_defaults_to_jpeg_for_unknown_format()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 400, 300);
        $image = Image::read($originalFile);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_') . '.unknown';

        ImageProcessor::encodeAndSave($image, $tempPath, 'unknown', 85);

        $this->assertFileExists($tempPath);
        $this->assertGreaterThan(0, filesize($tempPath));

        // Clean up
        unlink($tempPath);
    }

    public function test_calculateWatermarkPosition_returns_correct_coordinates()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $watermarkFile = UploadedFile::fake()->image('watermark.png', 100, 50);

        $image = Image::read($originalFile);
        $watermark = Image::read($watermarkFile);

        $positions = [
            'top-left' => [10, 10],
            'top' => [350, 10], // (800-100)/2, 10
            'top-right' => [690, 10], // 800-100-10, 10
            'left' => [10, 275], // 10, (600-50)/2
            'center' => [350, 275], // (800-100)/2, (600-50)/2
            'right' => [690, 275], // 800-100-10, (600-50)/2
            'bottom-left' => [10, 540], // 10, 600-50-10
            'bottom' => [350, 540], // (800-100)/2, 600-50-10
            'bottom-right' => [690, 540], // 800-100-10, 600-50-10
        ];

        foreach ($positions as $position => $expectedCoords) {
            $coords = $this->invokePrivateMethod(
                ImageProcessor::class,
                'calculateWatermarkPosition',
                [$image, $watermark, $position, 10]
            );

            $this->assertEquals($expectedCoords, $coords, "Position: $position");
        }
    }

    public function test_calculateWatermarkPosition_defaults_to_bottom_right()
    {
        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $watermarkFile = UploadedFile::fake()->image('watermark.png', 100, 50);

        $image = Image::read($originalFile);
        $watermark = Image::read($watermarkFile);

        $coords = $this->invokePrivateMethod(
            ImageProcessor::class,
            'calculateWatermarkPosition',
            [$image, $watermark, 'invalid-position', 10]
        );

        // Should default to bottom-right: [800-100-10, 600-50-10] = [690, 540]
        $this->assertEquals([690, 540], $coords);
    }

    public function test_generateThumbnail_handles_processing_error_gracefully()
    {
        // Test with an invalid image path
        $invalidPath = '/nonexistent/path/to/image.jpg';

        $thumbnailReference = new MediaFileReference(
            'test_thumb',
            'jpg',
            'public',
            'thumbnails'
        );

        $config = [
            'convert_format' => 'jpg',
            'quality' => 80,
            'resize' => [
                'width' => 300,
                'height' => 300,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ]
        ];

        // This should throw an exception due to invalid path
        $this->expectException(\Exception::class);

        ImageProcessor::generateThumbnail($invalidPath, $thumbnailReference, $config);
    }

    public function test_generateThumbnail_cleans_up_temp_files()
    {
        config(['media.storage_path' => 'media/{path}']);

        $originalFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $imagePath = $originalFile->getRealPath();

        $thumbnailReference = new MediaFileReference(
            'test_thumb',
            'jpg',
            'public',
            'thumbnails'
        );

        $config = [
            'convert_format' => 'jpg',
            'quality' => 80,
            'resize' => [
                'width' => 300,
                'height' => 300,
                'maintain_aspect_ratio' => true,
                'upsize' => false,
            ]
        ];

        // Get temp dir before operation
        $tempDir = sys_get_temp_dir();
        $tempFilesBefore = glob($tempDir . '/thumbnail_*');

        // Actually execute the thumbnail generation (with real storage)
        ImageProcessor::generateThumbnail($imagePath, $thumbnailReference, $config);

        // Check that temp files are cleaned up
        $tempFilesAfter = glob($tempDir . '/thumbnail_*');
        $this->assertEquals(count($tempFilesBefore), count($tempFilesAfter));
    }

    /**
     * Helper method to invoke private/protected methods for testing
     */
    private function invokePrivateMethod($class, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
