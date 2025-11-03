<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    | Here you may specify the default filesystem disk that should be used
    | by the media library.
    |---------------------------------------------------------------------------
    */
    'disk' => env('MEDIA_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Directory Structure
    |--------------------------------------------------------------------------
    | Configure where uploaded media should be placed on the disk.
    | Replace {path} with the appropriate path segment.
    | When storing a file with path 'images/2024/06' for example, the final storage path will be 'media/images/2024/06'.
    |--------------------------------------------------------------------------
    */
    'storage_path' => 'media/{path}',

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
    | Thumbnail Configuration
    |--------------------------------------------------------------------------
    | Configure how thumbnails are generated and stored.
    | You can enable or disable them, define a separate disk,
    |--------------------------------------------------------------------------
    */
    'thumbnails' => [

        // Enable or disable thumbnail generation
        'enabled' => env('MEDIA_THUMBNAILS_ENABLED', true),

        // If enabled, specify if a thumbnail needs to always be generated when media is uploaded
        'generate_always' => false,

        // The filesystem disk where thumbnails will be stored
        // If not set, it will use the disk that their media itself uses
        'force_disk' => env('MEDIA_STORAGE_DISK', null),

        // Path structure on the disk
        // {path} will be replaced with the media’s subpath
        'storage_path' => 'media/thumbnails/{path}',

        // Number of minutes to cache thumbnails when served via a controller, if null, do not cache
        'cache_minutes' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Banned File Types
    |--------------------------------------------------------------------------
    | Here you may specify the file types that are not allowed for uploads.
    |---------------------------------------------------------------------------
    */
    'banned_file_types' => ['exe', 'bat', 'cmd'],

    /*
    |--------------------------------------------------------------------------
    | Upload Validation Rules
    |--------------------------------------------------------------------------
    | Define per-type uploadvalidation logic.
    |---------------------------------------------------------------------------
    */
    'validation' => [
        'image' => ['mimes:jpg,jpeg,png,gif', 'max:5120'],
        'video' => ['mimes:mp4,mov,avi', 'max:20480'],
        'audio' => ['mimes:mp3,wav', 'max:10240'],
        'document' => ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
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
    | Image Processing Configuration
    |--------------------------------------------------------------------------
    | Configure how images should be processed after upload
    | Set 'enabled' to false to disable processing
    |---------------------------------------------------------------------------
    */
    'processing' => [
        'image' => [
            'enabled' => true,
            'convert_format' => 'jpg', // Convert all images to this format (jpg, png, webp, etc.) or null to keep original
            'quality' => 85, // Quality for JPEG/WebP compression (0-100)
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
                'opacity' => 80, // Opacity percentage
                'margin' => 10, // Margin from edge in pixels
            ],
            'optimize' => true, // Apply optimization
        ],
        'thumbnail' => [ // Configuration for generating thumbnails
            'convert_format' => 'jpg', // Convert all thumbnails to this format (jpg, png, webp, etc.) or null to keep original
            'quality' => 80, // Quality for thumbnail compression
            'resize' => [ // Resize settings for thumbnails (should always be enabled)
                'width' => 300,
                'height' => 300,
                'maintain_aspect_ratio' => true,
                'upsize' => false, // Don't upsize smaller images
            ],
        ],
    ],
];
