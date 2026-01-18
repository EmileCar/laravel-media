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

// Store a new media file with tags
$media = Media::store([
    'file' => $uploadedFile,
    'name' => 'My Image',
    'description' => 'A beautiful landscape photo',
    'type' => 'image',
    'tags' => ['Nature', 'Photography', 'Landscape'], // Optional tags
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
    
    'tags' => [
        'enabled' => true, // Enable/disable tagging system
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

## Tags

The package includes a flexible tagging system that allows you to organize and categorize your media files.

### Uploading Media with Tags

```php
use Carone\Media\Facades\Media;

// Upload image with tags
$media = Media::store([
    'type' => 'image',
    'file' => $request->file('image'),
    'name' => 'Sunset Photo',
    'tags' => ['Nature', 'Photography', 'Sunset'], // Tags are automatically created if they don't exist
]);

// Upload external media with tags
$media = Media::store([
    'type' => 'video',
    'source' => 'external',
    'url' => 'https://youtube.com/watch?v=example',
    'name' => 'Tutorial Video',
    'tags' => ['Tutorial', 'Education'],
]);
```

### Managing Tags

Tags are automatically created if they don't exist. The package handles:
- **Automatic slug generation** - "Nature Photography" becomes "nature-photography"
- **Case-insensitive matching** - "Nature", "nature", and "NATURE" are treated as the same tag
- **Duplicate prevention** - Reuses existing tags instead of creating duplicates

### Accessing Tags

```php
// Get all tags for a media resource
$tags = $media->tags; // Collection of Tag models

// Get tag names
$tagNames = $media->tags->pluck('name')->toArray();
// ['Nature', 'Photography', 'Sunset']

// Get tag slugs
$tagSlugs = $media->tags->pluck('slug')->toArray();
// ['nature', 'photography', 'sunset']
```

### Updating Tags

```php
// Sync tags (replaces existing tags)
$media->syncTags(['New Tag', 'Another Tag']);

// The old tags are removed, only the new ones remain
```

### Configuration

Enable or disable tags in `config/media.php`:

```php
'tags' => [
    'enabled' => true, // Set to false to disable tagging functionality
],
```

When tags are disabled:
- Tags provided during upload are ignored
- Existing tag relationships remain in the database
- No validation errors are thrown

## API Response Format

### Public Search Endpoints (GET /api/media/search, GET /api/media/type/{type})

These public endpoints return a minimal, SEO-friendly format:

```json
{
    "data": [
        {
            "id": 1,
            "name": "My Image",
            "description": "A beautiful landscape photo",
            "date": "2025-01-15",
            "type": "image",
            "thumbnail_url": "https://example.com/media/thumbnails/1",
            "media_url": "https://example.com/media/images/photo.jpg",
            "tags": [
                {"id": 1, "name": "Nature", "slug": "nature"},
                {"id": 2, "name": "Photography", "slug": "photography"}
            ]
        }
    ],
    "total": 50,
    "limit": 20,
    "offset": 0
}
```

**Public Response Fields:**
- `id` - Media resource ID (for detail pages, routing)
- `name` - Display name
- `description` - Optional description (for alt text, tooltips)
- `date` - ISO date string
- `type` - Media type (image, video, audio, document)
- `thumbnail_url` - Thumbnail URL (null if no thumbnail)
- `media_url` - Direct URL to media file
- `tags` - Array of tag objects with id, name, and slug

### Single Media Resource (GET /api/media/{id})

The detail endpoint returns complete information:

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
    "tags": [
        {"id": 1, "name": "Nature", "slug": "nature"},
        {"id": 2, "name": "Photography", "slug": "photography"}
    ],
    "created_at": "2023-10-25T12:34:56.000000Z",
    "updated_at": "2023-10-25T12:34:56.000000Z"
}
```

### Paginated Results

Public browsing and search endpoints return simplified data:

```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "name": "example.jpg",
            "description": "Example image",
            "date": "2024-01-01",
            "type": "image",
            "thumbnail_url": "http://localhost/media/thumbnails/1",
            "media_url": "http://localhost/media/uploads/2024/01/example.jpg",
            "tags": [
                {"id": 1, "name": "Nature", "slug": "nature"}
            ]
        }
    ],
    "per_page": 15,
    "total": 100
}
```

Management endpoints return complete resource data:

```json
{
    "id": 1,
    "name": "example.jpg",
    "description": "Example image",
    "date": "2024-01-01",
    "type": "image",
    "file_extension": "jpg",
    "file_size": 1024000,
    "thumbnail_id": 1234,
    "metadata": {
        "dimensions": {"width": 1920, "height": 1080},
        "exif": {...}
    },
    "tags": [
        {"id": 1, "name": "Nature", "slug": "nature"}
    ],
    "created_at": "2024-01-01T12:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z"
}
```

### Search Results

```json
{
    "data": [...], // Array of media resources (format depends on endpoint)
    "total": 10,
    "offset": 0,
    "limit": 20,
    "query": "landscape",
    "type": "image"
}
```

## Media Serving

### Path-Based URLs

Media files are served using clean, SEO-friendly path-based URLs:

```
GET /media/{path}

Examples:
- /media/images/2024/photo.jpg
- /media/documents/reports/annual-report.pdf
- /media/videos/demo.mp4
```

The path corresponds directly to the file's location in storage, making URLs predictable and clean.

### Thumbnails

Thumbnails are served by ID (since they can be on different disks):

```
GET /media/thumbnails/{id}

Example:
- /media/thumbnails/1
```

### URL Generation

The public search endpoints automatically generate these URLs:

```json
{
    "media_url": "https://example.com/media/images/photo.jpg",
    "thumbnail_url": "https://example.com/media/thumbnails/1"
}
```

## Security

- All file uploads are validated for type and size
- File extensions are strictly checked against allowed lists
- MIME type validation prevents malicious uploads
- Automatic cleanup of orphaned files
- Tag input is sanitized and validated

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
│   ├── MediaResource.php           # Media model
│   └── Tag.php                     # Tag model (for tagging system)
├── Enums/                          # ❌ INTERNAL - Do not use
└── Utilities/                      # ❌ INTERNAL - Do not use
```

### Key Internal Components

- **MediaManager**: Central orchestrator (accessed via facade)
- **Services**: Business logic layer
- **Strategies**: Media type-specific processing
- **Models**: Database entities (MediaResource, Tag)
- **Contracts**: Service interfaces

## Database Schema

The package creates the following tables:

### media_resources
Stores all media files (images, videos, audio, documents)

### media_tags
Stores tags that can be attached to media resources
- `id` - Primary key
- `name` - Display name (e.g., "Nature Photography")
- `slug` - URL-friendly slug (e.g., "nature-photography")
- `created_at`, `updated_at` - Timestamps

### media_resource_tag
Pivot table for many-to-many relationship between media and tags
- `media_resource_id` - Foreign key to media_resources
- `tag_id` - Foreign key to media_tags

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
- 🏷️ **Tagging System** - Organize media with tags (auto-created, case-insensitive)
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

// Upload a local image with tags
$media = StoreMediaAction::run([
    'type' => 'image',
    'file' => $request->file('image'),
    'name' => 'My Beautiful Image',
    'description' => 'A description of the image',
    'tags' => ['Nature', 'Landscape', 'Photography'],
]);

// Upload external media with tags
$media = StoreMediaAction::run([
    'type' => 'video',
    'source' => 'external',
    'url' => 'https://www.youtube.com/watch?v=example',
    'name' => 'External Video',
    'tags' => ['Tutorial', 'Video'],
]);
```

### Get Media

```php
use Carone\Media\Actions\GetMediaAction;

// Get media by type with pagination
$images = GetMediaAction::byType('image', $limit = 20, $offset = 0);

// Get single media item (includes tags)
$media = GetMediaAction::byId(1);
$tags = $media->tags; // Collection of Tag models

// Search media
$results = GetMediaAction::make()->search('vacation photos', 'image');
```

### Delete Media

```php
use Carone\Media\Actions\DeleteMediaAction;

// Delete single media (also removes tag associations)
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

<!-- With tags -->
<div>
    <img src="/media/image/my-image.jpg" alt="My Image">
    <div class="tags">
        @foreach($media->tags as $tag)
            <span class="tag">{{ $tag->name }}</span>
        @endforeach
    </div>
</div>
```

## API Endpoints

The package provides ready-to-use REST API endpoints:

```
GET    /api/media/types              # Get available media types
GET    /api/media/type/{type}        # Get media by type (paginated, includes tags)
GET    /api/media/search             # Search media (includes tags)
POST   /api/media/upload             # Upload media (accepts tags array)
GET    /api/media/{id}               # Get media by ID (includes tags)
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
        'tags' => $request->input('tags', []), // Optional tags
    ]);

    return response()->json([
        'success' => true, 
        'media' => $media,
        'tags' => $media->tags->pluck('name'),
    ]);
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
'disk' => env('DEFAULT_MEDIA_STORAGE_DISK', 'public'), // Main media files disk (not customizable per upload)
'storage_path' => 'media/{path}',
'enabled_types' => ['image', 'video', 'audio', 'document'],

// Thumbnail configuration
'thumbnails' => [
    'enabled' => true,
    'disk' => null, // If null, uses same disk as media. Can be customized per upload.
    'storage_path' => 'media/thumbnails/{path}',
],

// Tags configuration
'tags' => [
    'enabled' => true, // Enable/disable tagging system
],
```

**Important:** The main media disk is configured globally and cannot be overridden per upload. This ensures consistent, path-based media URLs. Thumbnails can still use custom disks.

### Route Protection

The package provides separate public and protected routes:

**Public Routes** (no authentication required):
- `GET /api/media/types` - Get enabled media types
- `GET /api/media/type/{type}` - Browse media by type (includes tags)
- `GET /api/media/search` - Search media (includes tags)
- `GET /api/media/{id}` - Get media details (includes tags)
- `GET /media/{path}` - Serve media files
- `GET /media/thumbnails/{id}` - Serve thumbnails

**Protected Routes** (authentication required by default):
- `POST /api/media/upload` - Upload media (accepts tags)
- `DELETE /api/media/{id}` - Delete media (removes tag associations)
- `DELETE /api/media/bulk` - Bulk delete media

Configure protection in `config/media.php`:

```php
'management_middleware' => ['auth'], // Default: require authentication

// Examples:
'management_middleware' => ['auth:sanctum'], // Use Sanctum
'management_middleware' => ['auth', 'admin'], // Require admin role
'management_middleware' => [], // No protection (not recommended for production)
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

## Image Processing Driver Setup

The package supports two image processing drivers: **Imagick** and **GD**. Choose based on your application's needs.

### Driver Comparison

| Feature | Imagick | GD |
|---------|---------|-----|
| **Installation** | Requires php-imagick extension | Usually built-in with PHP |
| **Memory Usage** | Streams data (~20-30 MB for 4000×4000) | Loads full image (~180-200 MB for 4000×4000) |
| **Large Images** | Handles efficiently | Requires high memory_limit |
| **Performance** | Generally faster | Adequate for small images |
| **Availability** | Needs separate installation | Built into most PHP installs |

### Understanding Memory Behavior

**Imagick** processes images by streaming data in chunks:
```
4000 × 4000 image = ~25-30 MB memory usage
```

**GD** loads the entire decompressed image into memory:
```
4000 × 4000 × 4 bytes (RGBA) = 64 MB base
+ Source buffer + processing buffers
= 180-200 MB total memory usage
```

### Which Driver to Choose?

**Choose Imagick if:**
- You handle large images (>2000px dimensions)
- You have high-volume image uploads
- You need optimal memory efficiency
- You can install the php-imagick extension

**Choose GD if:**
- Your images are typically small (<2000px)
- You have low upload volumes
- Imagick is not available in your environment
- You want to avoid additional dependencies

Both drivers are fully supported. The package defaults to Imagick but will automatically fall back to GD if Imagick is not installed.

### Installation

#### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install php-imagick
sudo systemctl restart php-fpm  # or apache2/nginx
```

#### Windows

1. Download the appropriate DLL from [PECL](https://pecl.php.net/package/imagick) or [windows.php.net](https://windows.php.net/downloads/pecl/releases/imagick/)
2. Place it in your PHP `ext` directory (e.g., `C:\php\ext\php_imagick.dll`)
3. Edit `php.ini` and add:
   ```ini
   extension=imagick
   ```
4. Restart your web server

#### macOS
```bash
brew install imagemagick
pecl install imagick
```

Then add to your `php.ini`:
```ini
extension=imagick.so
```

### Verification

After installation, verify your setup:

```bash
php artisan media:info
```

This will display:
- Active image driver and available extensions
- PHP memory limit and recommendations
- General media package configuration

Or check directly:
```bash
php -m | grep imagick
```

## Configuration

The driver is configured in `config/media.php`:

```php
'image_driver' => env('IMAGE_DRIVER', 'imagick'),
```

To use GD:
```env
IMAGE_DRIVER=gd
```

### Oversized Image Handling

The package can automatically scale down very large images to prevent memory issues and reduce storage:

```php
// config/media.php
'processing' => [
    'image' => [
        'scale_oversized_images' => true,     // Enable/disable automatic scaling
        'max_dimension_before_encode' => 3000, // Threshold for scaling
        'scaled_max_dimension' => 2560,        // Target size (2.5K, still HD)
    ],
],
```

When enabled:
- 4000×4000 image → scaled to 2560×2560
- 2000×1500 image → kept at original size
- Works with both Imagick and GD drivers

## Automatic Fallback

If Imagick is configured but not installed, the package will:
1. Log a warning
2. Automatically fall back to GD

## Memory Recommendations

### Recommended Settings

For applications handling large images, configure adequate memory:

```ini
# php.ini
memory_limit = 256M  # Minimum recommended for large images (4000×4000)
```

### Memory Requirements by Image Size

| Image Size | Minimum memory_limit | Recommended |
|------------|---------------------|-------------|
| Up to 2000×2000 | 128M | 256M |
| Up to 4000×4000 | 256M | 512M |
| Larger than 4000×4000 | 512M+ | 1G |

**Note:** Imagick uses less memory than GD for the same images. With `scale_oversized_images` enabled, memory requirements are further reduced.

### Check Your Settings

Run the info command to see your current configuration:

```bash
php artisan media:info
```

This will warn you if your memory limit is below the recommended 256M.

## Troubleshooting

### Error: "Imagick class not found"
- Extension is not installed or not enabled in php.ini
- Run: `php -m | grep imagick` to check

### Error: "Call to undefined function imagick_..."
- Wrong version of PHP or Imagick
- Reinstall: `pecl uninstall imagick && pecl install imagick`

### Still getting memory errors with Imagick
- Check PHP memory limit: `php -i | grep memory_limit`
- Increase if needed: `memory_limit = 256M` (minimum)
- Verify Imagick is actually being used: `php artisan media:info`
- Enable `scale_oversized_images` in config

### Images look worse quality
- Check quality settings in `config/media.php`:
  ```php
  'quality' => 85,  // 0-100, higher = better quality
  ```
- Verify `scale_oversized_images` settings if images are being scaled unexpectedly

### Want to disable automatic scaling
- Set `scale_oversized_images` to `false` in config
- Ensure adequate memory_limit for your image sizes

## Performance Comparison

Testing with a 4000×4000 JPEG (5 MB on disk):

| Operation | GD | Imagick |
|-----------|-----|---------|
| Load image | ~180 MB RAM | ~25 MB RAM |
| Resize to 2560×2560 | ~220 MB RAM | ~30 MB RAM |
| Generate thumbnail | ~250 MB RAM | ~35 MB RAM |
| Total time | 3.2s | 1.8s |

*Results may vary based on server configuration and image complexity.*

## Summary

Both drivers work well for their intended use cases:
- **Imagick**: Best for applications with large images or high upload volumes
- **GD**: Suitable for applications with small images or where Imagick is unavailable

The package is configured to use Imagick by default but will work seamlessly with either driver.


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
