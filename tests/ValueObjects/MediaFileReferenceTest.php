<?php

namespace Carone\Media\Tests\ValueObjects;

use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaFileReference;

class MediaFileReferenceTest extends TestCase
{
    public function test_it_can_be_constructed_with_all_required_parameters(): void
    {
        $reference = new MediaFileReference(
            filename: 'test-file',
            extension: 'jpg',
            disk: 'public',
            directory: 'media/images'
        );

        $this->assertSame('test-file', $reference->filename);
        $this->assertSame('jpg', $reference->extension);
        $this->assertSame('public', $reference->disk);
        $this->assertSame('media/images', $reference->directory);
    }

    public function test_it_can_be_created_from_path(): void
    {
        $reference = MediaFileReference::fromPath('media/images/test-file.jpg', 'public');

        $this->assertSame('test-file', $reference->filename);
        $this->assertSame('jpg', $reference->extension);
        $this->assertSame('public', $reference->disk);
        $this->assertSame('media/images', $reference->directory);
    }

    public function test_it_handles_from_path_edge_cases(): void
    {
        // Path without directory
        $reference1 = MediaFileReference::fromPath('test.jpg', 'public');
        $this->assertSame('test', $reference1->filename);
        $this->assertSame('jpg', $reference1->extension);
        $this->assertSame('', $reference1->directory);

        // Path without extension
        $reference2 = MediaFileReference::fromPath('media/test', 'public');
        $this->assertSame('test', $reference2->filename);
        $this->assertSame('', $reference2->extension);
        $this->assertSame('media', $reference2->directory);

        // Deep nested path
        $reference3 = MediaFileReference::fromPath('media/images/2024/01/file.png', 'local');
        $this->assertSame('file', $reference3->filename);
        $this->assertSame('png', $reference3->extension);
        $this->assertSame('media/images/2024/01', $reference3->directory);

        // Empty path
        $reference4 = MediaFileReference::fromPath('', 'public');
        $this->assertSame('', $reference4->filename);
        $this->assertSame('', $reference4->extension);
        $this->assertSame('', $reference4->directory);
    }

    public function test_it_creates_equivalent_objects_from_path_and_constructor(): void
    {
        $fromPath = MediaFileReference::fromPath('media/images/test.jpg', 'public');
        $fromConstructor = new MediaFileReference('test', 'jpg', 'public', 'media/images');

        $this->assertSame($fromConstructor->filename, $fromPath->filename);
        $this->assertSame($fromConstructor->extension, $fromPath->extension);
        $this->assertSame($fromConstructor->disk, $fromPath->disk);
        $this->assertSame($fromConstructor->directory, $fromPath->directory);
        $this->assertSame($fromConstructor->getFileNameWithExtension(), $fromPath->getFileNameWithExtension());
        $this->assertSame($fromConstructor->getPath(), $fromPath->getPath());
    }

    public function test_it_generates_correct_filename_with_extension(): void
    {
        $reference = new MediaFileReference('test-file', 'jpg', 'public', 'media');

        $this->assertSame('test-file.jpg', $reference->getFileNameWithExtension());
    }

    public function test_it_handles_filename_with_extension_edge_cases(): void
    {
        // Empty filename
        $reference1 = new MediaFileReference('', 'jpg', 'public', 'media');
        $this->assertSame('.jpg', $reference1->getFileNameWithExtension());

        // Empty extension
        $reference2 = new MediaFileReference('test', '', 'public', 'media');
        $this->assertSame('test.', $reference2->getFileNameWithExtension());

        // Both empty
        $reference3 = new MediaFileReference('', '', 'public', 'media');
        $this->assertSame('.', $reference3->getFileNameWithExtension());

        // Complex filename
        $reference4 = new MediaFileReference('my-test.file-name', 'jpeg', 'public', 'media');
        $this->assertSame('my-test.file-name.jpeg', $reference4->getFileNameWithExtension());

        // Numeric filename
        $reference5 = new MediaFileReference('123', 'png', 'public', 'media');
        $this->assertSame('123.png', $reference5->getFileNameWithExtension());
    }

    public function test_it_generates_correct_full_path(): void
    {
        $reference = new MediaFileReference('test-file', 'jpg', 'public', 'media/images');

        $this->assertSame('media/images/test-file.jpg', $reference->getPath());
    }

    public function test_it_handles_full_path_edge_cases(): void
    {
        // Empty directory
        $reference1 = new MediaFileReference('test', 'jpg', 'public', '');
        $this->assertSame('test.jpg', $reference1->getPath());

        // Directory with leading slash
        $reference2 = new MediaFileReference('test', 'jpg', 'public', '/media/images');
        $this->assertSame('media/images/test.jpg', $reference2->getPath());

        // Directory with trailing slash
        $reference3 = new MediaFileReference('test', 'jpg', 'public', 'media/images/');
        $this->assertSame('media/images/test.jpg', $reference3->getPath());

        // Directory with both leading and trailing slashes
        $reference4 = new MediaFileReference('test', 'jpg', 'public', '/media/images/');
        $this->assertSame('media/images/test.jpg', $reference4->getPath());

        // Multiple slashes
        $reference5 = new MediaFileReference('test', 'jpg', 'public', '//media//images//');
        $this->assertSame('media//images/test.jpg', $reference5->getPath());

        // Root directory (just slash)
        $reference6 = new MediaFileReference('test', 'jpg', 'public', '/');
        $this->assertSame('test.jpg', $reference6->getPath());

        // Deep nested directory
        $reference7 = new MediaFileReference('test', 'jpg', 'public', 'media/images/2024/01/15');
        $this->assertSame('media/images/2024/01/15/test.jpg', $reference7->getPath());
    }

    public function test_it_handles_special_characters_in_paths(): void
    {
        // Special characters in filename
        $reference1 = new MediaFileReference('test-file_name', 'jpg', 'public', 'media');
        $this->assertSame('media/test-file_name.jpg', $reference1->getPath());

        // Unicode characters
        $reference2 = new MediaFileReference('测试文件', 'jpg', 'public', 'media');
        $this->assertSame('media/测试文件.jpg', $reference2->getPath());

        // Spaces in paths (though not recommended)
        $reference3 = new MediaFileReference('test file', 'jpg', 'public', 'media images');
        $this->assertSame('media images/test file.jpg', $reference3->getPath());

        // Numbers and special chars
        $reference4 = new MediaFileReference('file-123_v2', 'png', 'local', 'uploads/2024');
        $this->assertSame('uploads/2024/file-123_v2.png', $reference4->getPath());
    }

    public function test_it_works_with_different_disk_names(): void
    {
        $reference1 = new MediaFileReference('test', 'jpg', 'local', 'media');
        $this->assertSame('local', $reference1->disk);

        $reference2 = new MediaFileReference('test', 'jpg', 's3', 'media');
        $this->assertSame('s3', $reference2->disk);

        $reference3 = new MediaFileReference('test', 'jpg', 'custom-disk-name', 'media');
        $this->assertSame('custom-disk-name', $reference3->disk);
    }

    public function test_it_works_with_different_file_extensions(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'mp3', 'wav', 'pdf', 'doc', 'docx'];

        foreach ($extensions as $extension) {
            $reference = new MediaFileReference('test', $extension, 'public', 'media');
            $this->assertSame("test.{$extension}", $reference->getFileNameWithExtension());
            $this->assertSame("media/test.{$extension}", $reference->getPath());
        }
    }

    public function test_it_preserves_case_sensitivity(): void
    {
        // Filenames
        $reference1 = new MediaFileReference('TestFile', 'JPG', 'public', 'Media');
        $this->assertSame('TestFile', $reference1->filename);
        $this->assertSame('JPG', $reference1->extension);
        $this->assertSame('Media', $reference1->directory);
        $this->assertSame('TestFile.JPG', $reference1->getFileNameWithExtension());
        $this->assertSame('Media/TestFile.JPG', $reference1->getPath());
    }

    public function test_it_can_represent_the_same_file_in_different_locations(): void
    {
        $ref1 = new MediaFileReference('image', 'jpg', 'local', 'temp');
        $ref2 = new MediaFileReference('image', 'jpg', 'public', 'media');
        $ref3 = new MediaFileReference('image', 'jpg', 's3', 'uploads/images');

        // Same filename and extension, different storage locations
        $this->assertSame('image.jpg', $ref1->getFileNameWithExtension());
        $this->assertSame('image.jpg', $ref2->getFileNameWithExtension());
        $this->assertSame('image.jpg', $ref3->getFileNameWithExtension());

        // Different full paths
        $this->assertSame('temp/image.jpg', $ref1->getPath());
        $this->assertSame('media/image.jpg', $ref2->getPath());
        $this->assertSame('uploads/images/image.jpg', $ref3->getPath());
    }

    public function test_it_handles_extremely_long_paths(): void
    {
        $longFilename = str_repeat('a', 100);
        $longDirectory = str_repeat('dir/', 50) . 'final';

        $reference = new MediaFileReference($longFilename, 'jpg', 'public', $longDirectory);

        $this->assertSame($longFilename, $reference->filename);
        $this->assertSame($longDirectory, $reference->directory);
        $this->assertStringContainsString($longFilename . '.jpg', $reference->getFileNameWithExtension());
        $this->assertStringContainsString($longDirectory, $reference->getPath());
    }

    public function test_it_can_be_compared_for_equality(): void
    {
        $ref1 = new MediaFileReference('test', 'jpg', 'public', 'media');
        $ref2 = new MediaFileReference('test', 'jpg', 'public', 'media');
        $ref3 = new MediaFileReference('test2', 'jpg', 'public', 'media');

        // Since it's a value object, we test by properties (PHP doesn't have built-in value equality)
        $this->assertSame($ref1->filename, $ref2->filename);
        $this->assertSame($ref1->extension, $ref2->extension);
        $this->assertSame($ref1->disk, $ref2->disk);
        $this->assertSame($ref1->directory, $ref2->directory);

        $this->assertNotSame($ref1->filename, $ref3->filename);
    }

    public function test_it_generates_correct_storage_path(): void
    {
        // Test with default config pattern 'media/{path}'
        config(['media.storage_path' => 'media/{path}']);

        $reference = new MediaFileReference('test-file', 'jpg', 'public', 'uploads/images');
        $this->assertSame('media/uploads/images/test-file.jpg', $reference->getStoragePath());

        // Test with empty directory
        $reference2 = new MediaFileReference('test', 'jpg', 'public', '');
        $this->assertSame('media/test.jpg', $reference2->getStoragePath());

        // Test with nested directories
        $reference3 = new MediaFileReference('photo', 'png', 'local', 'users/123/gallery');
        $this->assertSame('media/users/123/gallery/photo.png', $reference3->getStoragePath());
    }

    public function test_it_handles_different_storage_path_configs(): void
    {
        // Test with different storage path pattern
        config(['media.storage_path' => 'storage/app/{path}']);

        $reference = new MediaFileReference('document', 'pdf', 'local', 'docs');
        $this->assertSame('storage/app/docs/document.pdf', $reference->getStoragePath());

        // Test with no placeholder
        config(['media.storage_path' => 'static/files']);

        $reference2 = new MediaFileReference('image', 'jpg', 'public', 'photos');
        $this->assertSame('static/files', $reference2->getStoragePath());

        // Test with multiple placeholders (edge case)
        config(['media.storage_path' => 'files/{path}/backup/{path}']);

        $reference3 = new MediaFileReference('backup', 'zip', 'backup', 'daily');
        $this->assertSame('files/daily/backup.zip/backup/daily/backup.zip', $reference3->getStoragePath());
    }

    public function test_it_handles_storage_path_with_empty_and_null_values(): void
    {
        // Test with null path (empty directory)
        config(['media.storage_path' => 'uploads/{path}']);

        $reference = new MediaFileReference('test', 'jpg', 'public', '');
        $this->assertSame('uploads/test.jpg', $reference->getStoragePath());

        // Test with path containing special characters
        $reference2 = new MediaFileReference('test-file_name', 'png', 'local', 'user-data/special_files');
        $this->assertSame('uploads/user-data/special_files/test-file_name.png', $reference2->getStoragePath());
    }

    public function test_it_handles_complex_storage_scenarios(): void
    {
        // Test realistic scenario with date-based storage
        config(['media.storage_path' => 'media/uploads/{path}']);

        $reference = new MediaFileReference('profile-photo', 'jpg', 'public', '2024/10/users/123');
        $this->assertSame('media/uploads/2024/10/users/123/profile-photo.jpg', $reference->getStoragePath());

        // Test with UUID-like filenames
        $reference2 = new MediaFileReference('550e8400-e29b-41d4-a716-446655440000', 'jpeg', 's3', 'temp/uploads');
        $this->assertSame('media/uploads/temp/uploads/550e8400-e29b-41d4-a716-446655440000.jpeg', $reference2->getStoragePath());

        // Test with multilingual filenames
        $reference3 = new MediaFileReference('файл-тест', 'pdf', 'local', 'документы');
        $this->assertSame('media/uploads/документы/файл-тест.pdf', $reference3->getStoragePath());
    }

    public function test_it_correctly_trims_directory_slashes_in_get_path(): void
    {
        // Test the specific behavior of trim($this->directory, '/') in getPath()

        // Directory with only leading slash
        $reference1 = new MediaFileReference('test', 'jpg', 'public', '/media');
        $this->assertSame('media/test.jpg', $reference1->getPath());

        // Directory with only trailing slash
        $reference2 = new MediaFileReference('test', 'jpg', 'public', 'media/');
        $this->assertSame('media/test.jpg', $reference2->getPath());

        // Directory with both leading and trailing slashes
        $reference3 = new MediaFileReference('test', 'jpg', 'public', '/media/images/');
        $this->assertSame('media/images/test.jpg', $reference3->getPath());

        // Directory with multiple leading/trailing slashes
        $reference4 = new MediaFileReference('test', 'jpg', 'public', '///media/images///');
        $this->assertSame('media/images/test.jpg', $reference4->getPath());

        // Empty string after trimming (just slashes)
        $reference5 = new MediaFileReference('test', 'jpg', 'public', '///');
        $this->assertSame('test.jpg', $reference5->getPath());

        // Single slash
        $reference6 = new MediaFileReference('test', 'jpg', 'public', '/');
        $this->assertSame('test.jpg', $reference6->getPath());

        // Normal directory without slashes (should remain unchanged)
        $reference7 = new MediaFileReference('test', 'jpg', 'public', 'media/images');
        $this->assertSame('media/images/test.jpg', $reference7->getPath());
    }
}
