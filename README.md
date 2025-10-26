# # Carone Laravel Media Package

A comprehensive media management package for Laravel applications that provides a clean, facade-based API for handling various media types including images, videos, audio files, and documents.

## Installation

Install the package via Composer:

```bash
composer require carone/laravel-media
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Carone\Media\CaroneMediaServiceProvider" --tag="config"
```

Run the migrations:

```bash
php artisan migrate
```

## ⚠️ IMPORTANT: Public API

**This package provides ONLY ONE public interface: the `Media` facade.**

External projects should **NEVER** directly access internal classes like:
- ❌ `Carone\Media\Services\*`
- ❌ `Carone\Media\Strategies\*` 
- ❌ `Carone\Media\Models\*`
- ❌ `Carone\Media\Contracts\*`

**Always use the facade:**
- ✅ `Carone\Media\Facades\Media`

## Basic Usage

All media operations are performed through the `Media` facade:

```php
use Carone\Media\Facades\Media;

// Store a new media file
$media = Media::store([
    'file' => $uploadedFile,
    'name' => 'My Image',
    'description' => 'A beautiful landscape photo',
    'type' => 'image'
]);

// Get media by ID
$media = Media::getById(1);

// Get media by type with pagination
$result = Media::getByType('image', limit: 10, offset: 0);

// Search media
$result = Media::search('landscape', type: 'image', limit: 5);

// Serve media file
return Media::serve('image', 'filename.jpg');

// Serve thumbnail
return Media::thumbnail('image', 'filename.jpg');

// Delete media
Media::delete(1);

// Delete multiple media files
$result = Media::deleteMultiple([1, 2, 3]);

// Delete all media of a specific type
$result = Media::deleteByType('image');

// Clean up orphaned files
$result = Media::cleanupOrphanedFiles('image');

// Get enabled media types
$types = Media::getEnabledTypes();
```

## Configuration

Configure media types and settings in `config/media.php`:

```php
return [
    'enabled_types' => [
        'image' => [
            'enabled' => true,
            'max_file_size' => 10240, // KB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'thumbnails' => [
                'enabled' => true,
                'width' => 300,
                'height' => 300,
            ],
        ],
        'video' => [
            'enabled' => true,
            'max_file_size' => 102400, // KB
            'allowed_extensions' => ['mp4', 'avi', 'mov', 'wmv'],
        ],
        // ... other types
    ],
    
    'storage' => [
        'disk' => 'public',
        'path' => 'media',
    ],
];
```

## Media Types

The package supports four media types:

- **Image**: JPEG, PNG, GIF, WebP with automatic thumbnail generation
- **Video**: MP4, AVI, MOV, WMV
- **Audio**: MP3, WAV, OGG, AAC
- **Document**: PDF, DOC, DOCX, TXT

Each type can be individually enabled/disabled in the configuration.

## API Response Format

### Single Media Resource

```php
{
    "id": 1,
    "name": "My Image",
    "description": "A beautiful landscape photo",
    "type": "image",
    "file_name": "image_20231025_123456.jpg",
    "file_size": 2048576,
    "mime_type": "image/jpeg",
    "source": "local",
    "path": "/storage/media/images/image_20231025_123456.jpg",
    "thumbnail_path": "/storage/media/images/thumbnails/image_20231025_123456.jpg",
    "meta_data": {
        "width": 1920,
        "height": 1080,
        "exif": {...}
    },
    "created_at": "2023-10-25T12:34:56.000000Z",
    "updated_at": "2023-10-25T12:34:56.000000Z"
}
```

### Paginated Results

```php
{
    "data": [...], // Array of media resources
    "total": 50,
    "offset": 0,
    "limit": 20
}
```

### Search Results

```php
{
    "data": [...], // Array of media resources
    "total": 10,
    "offset": 0,
    "limit": 20,
    "query": "landscape",
    "type": "image"
}
```

## Security

- All file uploads are validated for type and size
- File extensions are strictly checked against allowed lists
- MIME type validation prevents malicious uploads
- Automatic cleanup of orphaned files

## Error Handling

The facade will throw appropriate exceptions:

```php
try {
    $media = Media::store($data);
} catch (\InvalidArgumentException $e) {
    // Invalid media type or validation failed
} catch (\Exception $e) {
    // Other storage errors
}
```

## Testing

Run the package tests:

```bash
composer test
```

## Package Architecture (Internal)

> **⚠️ WARNING: The following information is for package development only.**
> 
> **External projects should NEVER access these internal components directly.**
> **Always use the Media facade.**

### Internal Structure

```
src/
├── Facades/
│   └── Media.php                    # ✅ PUBLIC API - Use this
├── MediaManager.php                 # ❌ INTERNAL - Do not use
├── CaroneMediaServiceProvider.php   # ❌ INTERNAL - Do not use
├── Contracts/                       # ❌ INTERNAL - Do not use
├── Services/                        # ❌ INTERNAL - Do not use
├── Strategies/                      # ❌ INTERNAL - Do not use
├── Models/                          # ❌ INTERNAL - Do not use
├── Enums/                          # ❌ INTERNAL - Do not use
└── Utilities/                      # ❌ INTERNAL - Do not use
```

### Key Internal Components

- **MediaManager**: Central orchestrator (accessed via facade)
- **Services**: Business logic layer
- **Strategies**: Media type-specific processing
- **Models**: Database entities
- **Contracts**: Service interfaces

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

A **configurable media management package** for Laravel applications.
Easily handle media uploads, storage, and metadata (images, videos, audio, and documents) in your Laravel backend projects using clean, reusable Actions and the Strategy Pattern.

> 💡 Perfect for projects where you want consistent, reusable media logic across multiple Laravel apps.

## Features

- 🎯 **Action-Based Architecture** - Clean, reusable Actions for all media operations
- 🏗️ **Strategy Pattern** - Extensible design for different media types
- 🖼️ **Automatic Thumbnails** - Generate thumbnails for images
- 📁 **Multiple Storage** - Support for local and external media
- 🔍 **Search & Filter** - Built-in search and pagination
- 🛡️ **File Validation** - Configurable validation rules per media type
- 🚀 **Ready-to-use API** - Complete REST API endpoints
- 📝 **Comprehensive Logging** - Detailed error logging and debugging

---

## Installation

Require the package via Composer:

```bash
composer require carone/laravel-media
```
Laravel will auto-discover the service provider.

## Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="Carone\Media\CaroneMediaServiceProvider" --tag=config
```

This creates a config/media.php file in your app.

## Database Migration

Run the included migration to create the media_resources table

```bash
php artisan migrate
```

Table structure:
(<i>Required fields are marked with *</i>)

| Column      | Type                                   | Description               |
|-------------|----------------------------------------|---------------------------|
| id*          | bigint                                 | Primary key               |
| type*        | enum(image, video, audio, document)   | Type of media             |
| source*      | enum(local, external)                  | File source               |
| file_name*   | string                                 | Original file name (with extension)       |
| path        | string                                 | Local storage path        |
| url         | string                                 | External URL (if any)    |
| name*        | string                                 | Readable name       |
| description | text                                   | Description      |
| date       | date                                   | Date             |
| meta       | json                                   | Extra metadata            |
| timestamps*  | —                                      | Created/updated time      |

## Quick Start

### Upload Media

```php
use Carone\Media\Actions\StoreMediaAction;

// Upload a local image
$media = StoreMediaAction::run([
    'type' => 'image',
    'file' => $request->file('image'),
    'name' => 'My Beautiful Image',
    'description' => 'A description of the image',
]);

// Upload external media
$media = StoreMediaAction::run([
    'type' => 'video',
    'source' => 'external',
    'url' => 'https://www.youtube.com/watch?v=example',
    'name' => 'External Video',
]);
```

### Get Media

```php
use Carone\Media\Actions\GetMediaAction;

// Get media by type with pagination
$images = GetMediaAction::byType('image', $limit = 20, $offset = 0);

// Get single media item
$media = GetMediaAction::byId(1);

// Search media
$results = GetMediaAction::make()->search('vacation photos', 'image');
```

### Delete Media

```php
use Carone\Media\Actions\DeleteMediaAction;

// Delete single media
$success = DeleteMediaAction::run($mediaId);

// Bulk delete
$result = DeleteMediaAction::make()->deleteMultiple([1, 2, 3]);
```

### Serve Files

The package automatically provides routes for serving files:

```html
<!-- Original image -->
<img src="/media/image/my-image.jpg" alt="My Image">

<!-- Thumbnail (images only) -->
<img src="/media/image/thumbnails/my-image.jpg" alt="Thumbnail">
```

## API Endpoints

The package provides ready-to-use REST API endpoints:

```
GET    /api/media/types              # Get available media types
GET    /api/media/type/{type}        # Get media by type (paginated)
GET    /api/media/search             # Search media
POST   /api/media/upload             # Upload media
GET    /api/media/{id}               # Get media by ID
DELETE /api/media/{id}               # Delete media
DELETE /api/media/bulk               # Bulk delete media

GET    /media/{type}/{filename}      # Serve media files
GET    /media/{type}/thumbnails/{filename} # Serve thumbnails
```

## Migration from Existing Controllers

Replace your existing controller logic with Actions:

```php
// Before (your existing controller)
public function uploadMedia(MediaUploadRequest $request)
{
    // ... complex upload logic ...
}

// After (using this package)
public function uploadMedia(Request $request)
{
    $media = StoreMediaAction::run([
        'type' => $request->input('type'),
        'file' => $request->file('file'),
        'name' => $request->input('name'),
    ]);
    
    return response()->json(['success' => true, 'media' => $media]);
}
```

## Advanced Features

### Custom Validation

Configure validation rules per media type:

```php
// config/media.php
'validation' => [
    'image' => ['mimes:jpg,jpeg,png,gif', 'max:5120'], // 5MB max
    'video' => ['mimes:mp4,mov', 'max:51200'], // 50MB max
    'audio' => ['mimes:mp3,wav', 'max:10240'], // 10MB max
    'document' => ['mimes:pdf,doc,docx', 'max:10240'],
],
```

### Storage Configuration

```php
'disk' => env('MEDIA_STORAGE_DISK', 'public'),
'storage_path' => 'media/{type}',
'generate_thumbnails' => true,
'enabled_types' => ['image', 'video', 'audio', 'document'],
```

### Error Handling

All Actions throw appropriate exceptions:

```php
try {
    $media = StoreMediaAction::run($data);
} catch (\InvalidArgumentException $e) {
    // Handle validation errors
} catch (\Exception $e) {
    // Handle general errors
}
```

## Documentation

For detailed usage examples and advanced features, see [USAGE.md](USAGE.md).

## Architecture

This package uses:
- **Actions** (via `lorisleiva/laravel-actions`) for clean, reusable operations
- **Strategy Pattern** for handling different media types
- **Dependency Injection** for extensibility
- **Laravel's Storage** system for file management

## License

This package is open-sourced software licensed under the MIT license.

💬 **About**

carone/laravel-media is built for developers who need consistent, configurable media handling across multiple Laravel projects — without duplicating models, migrations, or upload logic.

**Designed for backend efficiency.**  
**Presentation is up to you.** 🎨