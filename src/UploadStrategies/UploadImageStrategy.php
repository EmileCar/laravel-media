<?php

namespace Carone\Media\UploadStrategies;

use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carone\Media\Utilities\ImageProcessor;
use Carone\Media\Utilities\MediaStorageHelper;
use Carone\Media\ValueObjects\MediaFileReference;
use Carone\Media\ValueObjects\StoreMediaData;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;

class UploadImageStrategy extends UploadMediaStrategy
{
    public function __construct(StoreMediaData $data)
    {
        $this->data = $data;
    }

    protected function processFile(UploadedFile $file): ?string
    {
        // Get config - use custom config if provided, otherwise use default
        $config = $this->data->processingConfig ?? config('media.processing.image', []);

        if (empty($config) || !($config['enabled'] ?? true)) {
            return null;
        }

        $image = Image::read($file);

        if (!empty($config['resize']['enabled'])) {
            $image = ImageProcessor::applyResize($image, $config['resize']);
        }
        if (!empty($config['crop']['enabled'])) {
            $image = ImageProcessor::applyCrop($image, $config['crop']);
        }
        if (!empty($config['watermark']['enabled']) && !empty($config['watermark']['path'])) {
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

        // Create proper thumbnail file reference with correct disk and unique filename
        $thumbnailExtension = $thumbnailConfig['convert_format'] ?? 'jpg';
        $thumbnailFileReference = MediaFileReference::createForThumbnail($fileReference, $thumbnailExtension);

        try {
            $physicalPath = MediaStorageHelper::getPhysicalPath($fileReference);
            ImageProcessor::generateThumbnail($physicalPath, $thumbnailFileReference, $thumbnailConfig);
            return $thumbnailFileReference;
        } catch (\Exception $e) {
            // Log error but don't fail the main upload
            \Log::warning('Failed to generate thumbnail: ' . $e->getMessage());
            return null;
        }
    }
}
