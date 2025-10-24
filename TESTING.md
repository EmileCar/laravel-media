# Testing Guide

This document explains how to run and understand the test suite for the Carone Laravel Media package.

## Running Tests

### Prerequisites

First, install the testing dependencies:

```bash
composer install --dev
```

### Running All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Running Specific Test Groups

Run only Action tests:
```bash
vendor/bin/phpunit tests/Actions/
```

Run only Strategy tests:
```bash
vendor/bin/phpunit tests/Strategies/
```

Run a specific test class:
```bash
vendor/bin/phpunit tests/Actions/StoreMediaActionTest.php
```

### Running with Coverage

```bash
composer test-coverage
```

This will generate an HTML coverage report in the `coverage/` directory.

## Test Structure

### TestCase Base Class

All tests extend from `Carone\Media\Tests\TestCase` which provides:

- **Database setup** - In-memory SQLite database
- **Storage mocking** - Fake storage disks for testing
- **File creation helpers** - Methods to create fake media files
- **Assertion helpers** - Custom assertions for file existence

### Action Tests

#### StoreMediaActionTest

Tests the media upload functionality:

- ✅ Upload images, videos, audio, documents
- ✅ Handle external media (URLs)
- ✅ File validation and error handling
- ✅ Unique filename generation
- ✅ Metadata storage
- ✅ Thumbnail creation for images
- ✅ Configuration validation

#### GetMediaActionTest

Tests media retrieval functionality:

- ✅ Get media by ID
- ✅ Get media by type with pagination
- ✅ Search functionality
- ✅ File serving
- ✅ Thumbnail serving
- ✅ API data formatting per media type

#### DeleteMediaActionTest

Tests media deletion functionality:

- ✅ Delete single media items
- ✅ Bulk deletion
- ✅ Delete by type with filters
- ✅ File cleanup (both main files and thumbnails)
- ✅ Orphaned file cleanup
- ✅ Error handling

### Strategy Tests

#### ImageStrategyTest

Tests image-specific functionality:

- ✅ File type support validation
- ✅ JPEG conversion
- ✅ Thumbnail generation
- ✅ External image handling
- ✅ API data formatting

Similar tests exist for VideoStrategy, AudioStrategy, and DocumentStrategy.

## Test Helpers

### Creating Fake Files

The TestCase provides helpers to create fake files for testing:

```php
// Create a fake image file
$imageFile = $this->createFakeImageFile('test-image.jpg');

// Create a fake video file
$videoFile = $this->createFakeVideoFile('test-video.mp4');

// Create a fake audio file
$audioFile = $this->createFakeAudioFile('test-audio.mp3');

// Create a fake document file
$documentFile = $this->createFakeDocumentFile('test-document.pdf');

// Create an unsupported file type
$unsupportedFile = $this->createFakeUnsupportedFile('malware.exe');
```

### Storage Assertions

Custom assertions for testing file operations:

```php
// Assert file exists in storage
$this->assertFileExistsInStorage('local', 'path/to/file.jpg');

// Assert file does not exist in storage
$this->assertFileNotExistsInStorage('local', 'path/to/file.jpg');
```

## Configuration for Testing

Tests use these configurations:

```php
'media.disk' => 'local'
'media.storage_path' => 'media/{type}'
'media.generate_thumbnails' => true
'media.enabled_types' => ['image', 'video', 'audio', 'document']
```

## Coverage Expectations

The test suite aims for high coverage:

- **Actions**: 95%+ coverage
- **Strategies**: 90%+ coverage  
- **Utilities**: 85%+ coverage
- **Models**: 80%+ coverage

## Writing New Tests

When adding new functionality:

1. **Follow naming conventions**: `MethodNameTest.php`
2. **Use descriptive test names**: `it_can_upload_image_with_custom_metadata`
3. **Test happy path and edge cases**
4. **Mock external dependencies**
5. **Use helper methods** from TestCase
6. **Clean up resources** (files, database records)

### Example Test Structure

```php
/** @test */
public function it_can_do_something_specific()
{
    // Arrange
    $data = ['key' => 'value'];
    
    // Act
    $result = $this->action->handle($data);
    
    // Assert
    $this->assertInstanceOf(ExpectedClass::class, $result);
    $this->assertEquals('expected', $result->property);
}
```

## Continuous Integration

The test suite is designed to run in CI environments:

- **Database**: Uses in-memory SQLite
- **Storage**: Uses Laravel's fake storage
- **Dependencies**: Minimal external dependencies
- **Performance**: Fast execution (< 30 seconds)

## Debugging Tests

### Enable Debug Output

```bash
vendor/bin/phpunit --debug
```

### Run Single Test

```bash
vendor/bin/phpunit --filter "test_method_name"
```

### View Test Coverage

```bash
vendor/bin/phpunit --coverage-text
```

## Common Issues

### Image Processing Issues

If image tests fail, ensure the GD extension is installed:

```bash
php -m | grep -i gd
```

### Permission Issues

Ensure the `storage/` directory is writable:

```bash
chmod -R 755 storage/
```

### Memory Issues

For large test suites, increase PHP memory:

```bash
php -d memory_limit=512M vendor/bin/phpunit
```