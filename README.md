# Carone - Laravel Media

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