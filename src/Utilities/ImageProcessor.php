<?php

namespace Carone\Media\Utilities;

use Carone\Media\ValueObjects\MediaFileReference;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\ImageInterface;

class ImageProcessor
{
    /**
     * Generate a thumbnail from an image
     */
    public static function generateThumbnail(string $imagePath, MediaFileReference $refernce, array $config): void
    {
        $image = Image::read($imagePath);

        $image = static::applyResize($image, $config['resize']);
        $tempPath = tempnam(sys_get_temp_dir(), 'thumbnail_');
        static::encodeAndSave($image, $tempPath, $config['convert_format'] ?? 'jpg', $config['quality']);

        MediaStorageHelper::storeFile($refernce, file_get_contents($tempPath));

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
     */
    public static function encodeAndSave(ImageInterface $image, string $path, string $format, int $quality): void
    {
        $encoder = match (strtolower($format)) {
            'jpg', 'jpeg' => new \Intervention\Image\Encoders\JpegEncoder($quality),
            'png' => new \Intervention\Image\Encoders\PngEncoder(),
            'webp' => new \Intervention\Image\Encoders\WebpEncoder($quality),
            default => new \Intervention\Image\Encoders\JpegEncoder($quality),
        };

        $image->encode($encoder)->save($path);
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
