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
    | Where uploaded media should be placed on the disk.
    | Tokens like {type} will be replaced dynamically.
    |---------------------------------------------------------------------------
    */
    'storage_path' => 'media/{type}',

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
    | Define per-type validation logic.
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
    | Thumbnail Generation
    |--------------------------------------------------------------------------
    | Enable or disable automatic thumbnail generation for image uploads.
    |---------------------------------------------------------------------------
    */
    'generate_thumbnails' => true,

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
];
