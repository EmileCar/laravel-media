# Media Management Usage Examples

This document provides examples of how to use the Carone Laravel Media package in your Laravel applications.

## Basic Usage

### Uploading Media

#### Upload a Local Image
```php
use Carone\Media\Actions\StoreMediaAction;

// In your controller
public function uploadImage(Request $request)
{
    $media = StoreMediaAction::run([
        'type' => 'image',
        'file' => $request->file('image'),
        'name' => 'My Beautiful Image',
        'description' => 'A description of the image',
        'source' => 'local', // optional, defaults to 'local'
    ]);
    
    return response()->json($media);
}
```

#### Upload External Media (URL)
```php
$media = StoreMediaAction::run([
    'type' => 'video',
    'source' => 'external',
    'url' => 'https://www.youtube.com/watch?v=example',
    'name' => 'External Video',
    'description' => 'A video from YouTube',
]);
```

### Retrieving Media

#### Get Media by Type
```php
use Carone\Media\Actions\GetMediaAction;

// Get images with pagination
$images = GetMediaAction::byType('image', $limit = 20, $offset = 0);

// Response structure:
// {
//     "data": [...],
//     "total": 150,
//     "offset": 0,
//     "limit": 20
// }
```

#### Get Single Media Item
```php
$media = GetMediaAction::byId(1);
```

#### Search Media
```php
$action = GetMediaAction::make();
$results = $action->search('vacation photos', $type = 'image', $limit = 10);
```

### Deleting Media

#### Delete Single Media
```php
use Carone\Media\Actions\DeleteMediaAction;

$success = DeleteMediaAction::run($mediaId);
```

#### Bulk Delete
```php
$action = DeleteMediaAction::make();
$result = $action->deleteMultiple([1, 2, 3, 4]);

// Returns:
// [
//     'deleted' => 3,
//     'failed' => [
//         ['id' => 4, 'error' => 'Media not found']
//     ]
// ]
```

## Advanced Usage

### Custom Validation

You can extend validation by modifying the config:

```php
// config/media.php
'validation' => [
    'image' => ['mimes:jpg,jpeg,png,gif', 'max:5120'], // 5MB max
    'video' => ['mimes:mp4,mov', 'max:51200'], // 50MB max
    'audio' => ['mimes:mp3,wav', 'max:10240'], // 10MB max
    'document' => ['mimes:pdf,doc,docx', 'max:10240'],
],
```

### Using in Your Controllers

Replace your existing controller logic with the Actions:

```php
<?php

namespace App\Http\Controllers;

use Carone\Media\Actions\StoreMediaAction;
use Carone\Media\Actions\GetMediaAction;
use Carone\Media\Actions\DeleteMediaAction;
use Illuminate\Http\Request;

class YourMediaController extends Controller
{
    public function index($type)
    {
        $limit = request('limit', 20);
        $offset = request('offset', 0);
        
        return GetMediaAction::byType($type, $limit, $offset);
    }
    
    public function store(Request $request)
    {
        try {
            $media = StoreMediaAction::run([
                'type' => $request->input('type'),
                'file' => $request->file('file'),
                'source' => $request->input('source', 'local'),
                'url' => $request->input('url'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'meta' => $request->input('meta', []),
            ]);
            
            return response()->json([
                'success' => true,
                'media' => $media
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    public function destroy($id)
    {
        try {
            DeleteMediaAction::run($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

### File Serving

The package automatically provides routes for serving files:

```html
<!-- Serve original image -->
<img src="/media/image/my-image.jpg" alt="My Image">

<!-- Serve thumbnail (for images only) -->
<img src="/media/image/thumbnails/my-image.jpg" alt="Thumbnail">

<!-- Serve video -->
<video controls>
    <source src="/media/video/my-video.mp4" type="video/mp4">
</video>
```

### API Integration

The package provides ready-to-use API endpoints:

```javascript
// Get media types
fetch('/api/media/types')

// Get media by type
fetch('/api/media/type/image?limit=20&offset=0')

// Search media
fetch('/api/media/search?q=vacation&type=image')

// Upload media
const formData = new FormData();
formData.append('type', 'image');
formData.append('file', fileInput.files[0]);
formData.append('name', 'My Image');

fetch('/api/media/upload', {
    method: 'POST',
    body: formData
})

// Delete media
fetch('/api/media/123', { method: 'DELETE' })
```

## Strategy Pattern Benefits

The package uses the Strategy Pattern, which means:

1. **Extensible**: Easy to add new media types
2. **Maintainable**: Each media type has its own handling logic
3. **Testable**: Each strategy can be tested independently
4. **Configurable**: Enable/disable media types via config

### Custom Media Types

You can add custom media types by creating new strategies:

```php
use Carone\Media\Contracts\MediaUploadStrategyInterface;
use Carone\Media\Contracts\MediaRetrievalStrategyInterface;

class PdfStrategy implements MediaUploadStrategyInterface, MediaRetrievalStrategyInterface
{
    public function getType(): string
    {
        return 'pdf';
    }
    
    // Implement other required methods...
}
```

Then register it in your service provider:

```php
// In your AppServiceProvider or custom provider
$strategies = app(StoreMediaAction::class)->getStrategies();
$strategies['pdf'] = new PdfStrategy();
app(StoreMediaAction::class)->setStrategies($strategies);
```

## Configuration

### Storage Configuration

```php
// config/media.php
'disk' => env('MEDIA_STORAGE_DISK', 'public'),
'storage_path' => 'media/{type}', // {type} gets replaced with actual type
```

### Thumbnail Settings

```php
'generate_thumbnails' => true,
```

### Enabled Types

```php
'enabled_types' => ['image', 'video', 'audio', 'document'],
```

## Error Handling

All Actions throw appropriate exceptions:

- `\InvalidArgumentException` for invalid input
- `\Illuminate\Database\Eloquent\ModelNotFoundException` for missing records
- `\Exception` for general errors

Always wrap Action calls in try-catch blocks for proper error handling.