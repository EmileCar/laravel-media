<?php

namespace Carone\Media\Utilities;

use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\ImageInterface;

class ImageProcessor
{
    /**
     * Maximum dimensions before image is scaled down to prevent memory issues (GD only)
     */
    private const MAX_DIMENSION_BEFORE_ENCODE = 3000;

    /**
     * Target dimension when scaling down oversized images (GD only)
     */
    private const SCALED_MAX_DIMENSION = 2560; // 2.5K resolution

    /**
     * Generate a thumbnail from an image
     */
    public static function generateThumbnail(string $imagePath, MediaFileReference $thumbnailReference, array $config): void
    {
        $image = Image::read($imagePath);

        $image = static::applyResize($image, $config['resize']);
        $tempPath = tempnam(sys_get_temp_dir(), 'thumbnail_');
        static::encodeAndSave($image, $tempPath, $config['convert_format'] ?? 'jpg', $config['quality']);

        // Free image from memory immediately after encoding
        unset($image);
        gc_collect_cycles();

        // Store using thumbnail-specific storage path
        $thumbnailStoragePath = $thumbnailReference->getThumbnailStoragePath();
        Storage::disk($thumbnailReference->disk)->put($thumbnailStoragePath, file_get_contents($tempPath));

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    /**
     * Apply resize transformation
     */
    public static function applyResize(ImageInterface $image, array $config): ImageInterface
    {
        $width = $config['width'] ?? null;
        $height = $config['height'] ?? null;
        $maintainAspectRatio = $config['maintain_aspect_ratio'] ?? true;
        $upsize = $config['upsize'] ?? false;

        if (!$upsize) {
            // Don't upsize smaller images
            if ($width && $image->width() < $width) {
                $width = $image->width();
            }
            if ($height && $image->height() < $height) {
                $height = $image->height();
            }
        }

        if ($maintainAspectRatio) {
            return $image->scale($width, $height);
        } else {
            return $image->resize($width, $height);
        }
    }

    /**
     * Apply crop transformation
     */
    public static function applyCrop(ImageInterface $image, array $config): ImageInterface
    {
        $width = $config['width'];
        $height = $config['height'];
        $position = $config['position'] ?? 'center';

        return $image->crop($width, $height, position: $position);
    }

    /**
     * Apply watermark
     */
    public static function applyWatermark(ImageInterface $image, array $config): ImageInterface
    {
        $watermarkPath = $config['path'];
        $position = $config['position'] ?? 'bottom-right';
        $opacity = $config['opacity'] ?? 80;
        $margin = $config['margin'] ?? 10;

        if (!file_exists($watermarkPath)) {
            return $image;
        }

        $watermark = Image::read($watermarkPath);

        // Calculate position
        [$x, $y] = static::calculateWatermarkPosition($image, $watermark, $position, $margin);

        return $image->place($watermark, 'top-left', $x, $y, $opacity);
    }

    /**
     * Save image with specific format
     * Automatically scales down oversized images if enabled in config
     */
    public static function encodeAndSave(ImageInterface $image, string $path, string $format, int $quality): void
    {
        // Scale down oversized images if enabled in config
        if (config('media.processing.image.scale_oversized_images', true)) {
            $image = static::scaleDownOversizedImage($image);
        }

        $encoder = match (strtolower($format)) {
            'jpg', 'jpeg' => new \Intervention\Image\Encoders\JpegEncoder($quality),
            'png' => new \Intervention\Image\Encoders\PngEncoder(),
            'webp' => new \Intervention\Image\Encoders\WebpEncoder($quality),
            default => new \Intervention\Image\Encoders\JpegEncoder($quality),
        };

        $encoded = $image->encode($encoder);
        $encoded->save($path);

        // Free encoded image from memory
        unset($encoded);
    }

    /**
     * Scale down images that exceed maximum dimension threshold
     * Helps prevent memory exhaustion and reduces storage/bandwidth usage
     */
    private static function scaleDownOversizedImage(ImageInterface $image): ImageInterface
    {
        $width = $image->width();
        $height = $image->height();
        $maxDimension = max($width, $height);

        // Get thresholds from config or use defaults
        $maxDimensionThreshold = config('media.processing.image.max_dimension_before_encode', static::MAX_DIMENSION_BEFORE_ENCODE);
        $scaledMaxDimension = config('media.processing.image.scaled_max_dimension', static::SCALED_MAX_DIMENSION);

        // Only scale if image exceeds threshold
        if ($maxDimension <= $maxDimensionThreshold) {
            return $image;
        }

        // Calculate scale factor to bring largest dimension down to target
        $scaleFactor = $scaledMaxDimension / $maxDimension;
        $newWidth = (int) round($width * $scaleFactor);
        $newHeight = (int) round($height * $scaleFactor);

        return $image->scale($newWidth, $newHeight);
    }

    /**
     * Calculate watermark position
     */
    private static function calculateWatermarkPosition(ImageInterface $image, ImageInterface $watermark, string $position, int $margin): array
    {
        $imageWidth = $image->width();
        $imageHeight = $image->height();
        $watermarkWidth = $watermark->width();
        $watermarkHeight = $watermark->height();

        return match ($position) {
            'top-left' => [$margin, $margin],
            'top' => [($imageWidth - $watermarkWidth) / 2, $margin],
            'top-right' => [$imageWidth - $watermarkWidth - $margin, $margin],
            'left' => [$margin, ($imageHeight - $watermarkHeight) / 2],
            'center' => [($imageWidth - $watermarkWidth) / 2, ($imageHeight - $watermarkHeight) / 2],
            'right' => [$imageWidth - $watermarkWidth - $margin, ($imageHeight - $watermarkHeight) / 2],
            'bottom-left' => [$margin, $imageHeight - $watermarkHeight - $margin],
            'bottom' => [($imageWidth - $watermarkWidth) / 2, $imageHeight - $watermarkHeight - $margin],
            'bottom-right' => [$imageWidth - $watermarkWidth - $margin, $imageHeight - $watermarkHeight - $margin],
            default => [$imageWidth - $watermarkWidth - $margin, $imageHeight - $watermarkHeight - $margin],
        };
    }
}
