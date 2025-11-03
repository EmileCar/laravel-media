<?php

namespace Carone\Media\Processing\Plugins;

use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaFileReference;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Image-specific processing plugin
 */
class ImageProcessingPlugin
{
    /**
     * Process an image file
     */
    public function process(UploadedFile $file): ?string
    {
        $config = config('media.processing.image', []);

        if (empty($config) || !$config['enabled']) {
            return null;
        }

        $image = Image::read($file);

        if ($config['resize']['enabled'] ?? false) {
            $image = ImageProcessor::applyResize($image, $config['resize']);
        }

        if ($config['crop']['enabled'] ?? false) {
            $image = ImageProcessor::applyCrop($image, $config['crop']);
        }

        if (($config['watermark']['enabled'] ?? false) && ($config['watermark']['path'] ?? null)) {
            $image = ImageProcessor::applyWatermark($image, $config['watermark']);
        }

        // Encode and save to temp file
        $ext = $config['convert_format'] ?? $file->getClientOriginalExtension();
        $quality = $config['quality'] ?? 85;
        $tempPath = tempnam(sys_get_temp_dir(), 'processed_img_') . '.' . $ext;

        ImageProcessor::encodeAndSave($image, $tempPath, $ext, $quality);

        return $tempPath;
    }

    /**
     * Generate thumbnail for the image
     */
    public function generateThumbnail(MediaFileReference $fileReference): ?MediaFileReference
    {
        $thumbnailConfig = config('media.processing.thumbnail', []);

        if (empty($thumbnailConfig)) {
            return null;
        }

        $thumbnailFileReference = new MediaFileReference(
            $fileReference->filename . '_thumb',
            $thumbnailConfig['convert_format'] ?? 'jpg',
            $fileReference->disk,
            $fileReference->directory
        );

        try {
            ImageProcessor::generateThumbnail(
                MediaStorageHelper::getPhysicalPath($fileReference),
                $thumbnailFileReference,
                $thumbnailConfig
            );
            return $thumbnailFileReference;
        } catch (\Exception $e) {
            // Log error but don't fail the main upload
            \Log::warning('Failed to generate thumbnail: ' . $e->getMessage());
            return null;
        }
    }
}
