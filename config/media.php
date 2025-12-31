<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    | Here you may specify the default filesystem disk that should be used
    | by the media library. Can be overridden per upload via the builder.
    |---------------------------------------------------------------------------
    */
    'disk' => env('DEFAULT_MEDIA_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default Directory Structure
    |--------------------------------------------------------------------------
    | Configure where uploaded media should be placed on the disk.
    | Replace {path} with the appropriate path segment.
    | When storing a file with path 'images/2024/06' for example, the final
    | storage path will be 'media/images/2024/06'.
    | Can be overridden per upload via the builder.
    |--------------------------------------------------------------------------
    */
    'storage_path' => env('DEFAULT_MEDIA_STORAGE_PATH', 'media/{path}'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    | Number of minutes to cache media files when served via a controller.
    | If null, do not cache.
    |--------------------------------------------------------------------------
    */
    'cache_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Image Processing Driver
    |--------------------------------------------------------------------------
    | The image driver to use for processing. Options: 'imagick' or 'gd'
    |
    | Imagick (ImageMagick extension):
    |   - Streams image data, using 3-5x less memory
    |   - Better for large images and high-throughput applications
    |   - Requires php-imagick extension to be installed
    |
    | GD (Graphics Draw):
    |   - Built into most PHP installations by default
    |   - Loads entire images into memory before processing
    |   - Suitable for smaller images or low-volume applications
    |
    | See README for detailed comparison and installation instructions.
    |--------------------------------------------------------------------------
    */
    'image_driver' => env('IMAGE_DRIVER', 'imagick'),

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Configuration
    |--------------------------------------------------------------------------
    | Configure how thumbnails are generated and stored for media files.
    | Thumbnails are optional for ALL media types (image, video, audio, document).
    |
    | For images: Thumbnails can be automatically generated from the source image.
    | For other types or external media: Thumbnails can be provided as:
    |   - An external URL (thumbnail_url) via builder
    |   - A file upload (thumbnail_file) via builder
    |   - A local file path (thumbnail_path) via builder
    |
    | Thumbnails DO NOT have their own MediaResource entity - they are stored
    | as properties on the parent media resource.
    |--------------------------------------------------------------------------
    */
    'thumbnails' => [

        // Enable or disable thumbnail functionality globally
        'enabled' => env('MEDIA_THUMBNAILS_ENABLED', true),

        // Auto-generate thumbnails for images when uploaded (only applies to local images)
        // This can be overridden per upload via the builder's withThumbnail() method
        'auto_generate_for_images' => env('MEDIA_AUTO_GENERATE_THUMBNAILS', false),

        // The filesystem disk where thumbnails always will be stored
        // If null, will use the same disk as the media file itself
        'disk' => env('MEDIA_THUMBNAILS_DISK', null),

        // Path structure on the disk where thumbnails are stored
        // {path} will be replaced with the media's directory path
        'storage_path' => env('MEDIA_THUMBNAILS_STORAGE_PATH', 'media/thumbnails/{path}'),

        // Number of minutes to cache thumbnails when served via a controller
        // Set to null to disable caching
        'cache_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Banned File Types
    |--------------------------------------------------------------------------
    | Here you may specify the file types that are not allowed for uploads.
    |---------------------------------------------------------------------------
    */
    'banned_file_types' => ['exe', 'bat', 'cmd', 'sh'],

    /*
    |--------------------------------------------------------------------------
    | Upload Validation Rules
    |--------------------------------------------------------------------------
    | Define per-type upload validation logic. These can be overridden
    | per upload via the builder's withValidationRules() method.
    |---------------------------------------------------------------------------
    */
    'validation' => [
        'image' => ['mimes:jpg,jpeg,png,gif,webp', 'max:5120'], // 5MB
        'video' => ['mimes:mp4,mov,avi', 'max:20480'], // 20MB
        'audio' => ['mimes:mp3,wav', 'max:10240'], // 10MB
        'document' => ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240'], // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled Media Types
    |--------------------------------------------------------------------------
    | Here you may specify the types of media that the application will handle.
    | Others will be ignored.
    |---------------------------------------------------------------------------
    */
    'enabled_types' => ['image', 'video', 'audio', 'document'],

    /*
    |--------------------------------------------------------------------------
    | Model Customization
    |--------------------------------------------------------------------------
    | Optionally point to a custom Eloquent model class to extend base behavior.
    |---------------------------------------------------------------------------
    */
    'model' => \Carone\Media\Models\MediaResource::class,

    /*
    |--------------------------------------------------------------------------
    | Management Endpoints Middleware
    |--------------------------------------------------------------------------
    | Define which middleware should protect media management endpoints
    | (upload, delete, bulk delete). Set to an empty array for no protection.
    | Public endpoints (search, serve media) are always accessible.
    |
    | Examples:
    |   ['auth'] - Require authentication
    |   ['auth', 'admin'] - Require authentication and admin role
    |   ['auth:sanctum'] - Use Sanctum authentication
    |   [] - No protection (not recommended for production)
    |---------------------------------------------------------------------------
    */
    'management_middleware' => ['auth'],

    /*
    |--------------------------------------------------------------------------
    | Tags Configuration
    |--------------------------------------------------------------------------
    | Enable or disable tagging functionality for media resources.
    | When enabled, media can be tagged and filtered by tags.
    |
    | Features when enabled:
    |   - Assign multiple tags to media during upload
    |   - Search/filter media by tags
    |   - Retrieve all available tags
    |   - Auto-create tags that don't exist
    |---------------------------------------------------------------------------
    */
    'tags' => [
        'enabled' => env('MEDIA_TAGS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing Configuration
    |--------------------------------------------------------------------------
    | Configure how images should be processed after upload.
    | Set 'enabled' to false to disable processing.
    | All settings can be overridden per upload via the builder.
    |---------------------------------------------------------------------------
    */
    'processing' => [
        'image' => [
            'enabled' => true,
            'convert_format' => null, // Convert all images to this format (jpg, png, webp, etc.) or null to keep original
            'quality' => 85, // Quality for JPEG/WebP compression (0-100)

            // Automatic scaling for oversized images
            // When enabled, images exceeding max_dimension_before_encode will be scaled down
            // This helps prevent memory issues and reduces storage/bandwidth usage
            'scale_oversized_images' => true, // Enable/disable automatic scaling
            'max_dimension_before_encode' => 3000, // Images larger than this will be scaled down
            'scaled_max_dimension' => 2560, // Target dimension for oversized images (still HD quality)

            'resize' => [
                'enabled' => false,
                'width' => 1920,
                'height' => 1080,
                'maintain_aspect_ratio' => true,
                'upsize' => false, // Don't upsize smaller images
            ],
            'crop' => [
                'enabled' => false,
                'width' => 800,
                'height' => 600,
                'position' => 'center', // center, top-left, top, top-right, left, right, bottom-left, bottom, bottom-right
            ],
            'watermark' => [
                'enabled' => false,
                'path' => null, // Path to watermark image
                'position' => 'bottom-right', // Position of watermark
                'opacity' => 80, // Opacity percentage (0-100)
                'margin' => 10, // Margin from edge in pixels
            ],
            'optimize' => true, // Apply optimization
        ],

        // Configuration for generating thumbnails from images
        'thumbnail' => [
            'convert_format' => 'jpg', // Convert all thumbnails to this format (jpg, png, webp, etc.) or null to keep original
            'quality' => 80, // Quality for thumbnail compression (0-100)
            'resize' => [
                'width' => 300,
                'height' => 300,
                'maintain_aspect_ratio' => true,
                'upsize' => false, // Don't upsize smaller images
            ],
        ],
    ],
];
