<?php

namespace Carone\Media\Strategies;

use Carone\Media\Strategies\MediaStrategy;
use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Models\MediaResource;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;

class ImageStrategy extends MediaStrategy
{
    public function getType(): MediaType
    {
        return MediaType::IMAGE;
    }

    protected function processFile(UploadedFile $file): ?string
    {
        $config = config('media.processing.image', []);

        if (empty($config) || !$config['enabled']) {
            return null;
        }

        $image = Image::read($file);

        if ($config['resize']['enabled']) {
            $image = ImageProcessor::applyResize($image, $config['resize']);
        }
        if ($config['crop']['enabled']) {
            $image = ImageProcessor::applyCrop($image, $config['crop']);
        }
        if ($config['watermark']['enabled'] && $config['watermark']['path']) {
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
    protected function generateThumbnail(MediaFileReference $fileReference): ?MediaFileReference
    {
        $thumbnailConfig = config('media.processing.thumbnail', []);

        if (empty($thumbnailConfig)) {
            return null;
        }

        $thumbnailFileReference = new MediaFileReference(
            $fileReference->filename,
            $thumbnailConfig['convert_format'],
            $fileReference->disk,
            $fileReference->directory
        );

        try {
            ImageProcessor::generateThumbnail($fileReference->getStoragePath(), $thumbnailFileReference, $thumbnailConfig);
            return $thumbnailFileReference;
        } catch (\Exception $e) {
            // Log error but don't fail the main upload
            \Log::warning('Failed to generate thumbnail: ' . $e->getMessage());
            return null;
        }
    }
}
