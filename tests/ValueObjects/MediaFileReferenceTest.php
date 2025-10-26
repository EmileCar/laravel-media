<?php

namespace Carone\Media\Tests\ValueObjects;

use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaFileReference;

class MediaFileReferenceTest extends TestCase
{
    /** @test */
    public function it_can_be_constructed_with_all_required_parameters(): void
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

    /** @test */
    public function it_generates_correct_filename_with_extension(): void
    {
        $reference = new MediaFileReference('test-file', 'jpg', 'public', 'media');

        $this->assertSame('test-file.jpg', $reference->getFileNameWithExtension());
    }

    /** @test */
    public function it_handles_filename_with_extension_edge_cases(): void
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

    /** @test */
    public function it_generates_correct_full_path(): void
    {
        $reference = new MediaFileReference('test-file', 'jpg', 'public', 'media/images');

        $this->assertSame('media/images/test-file.jpg', $reference->getFullPath());
    }

    /** @test */
    public function it_handles_full_path_edge_cases(): void
    {
        // Empty directory
        $reference1 = new MediaFileReference('test', 'jpg', 'public', '');
        $this->assertSame('test.jpg', $reference1->getFullPath());

        // Directory with leading slash
        $reference2 = new MediaFileReference('test', 'jpg', 'public', '/media/images');
        $this->assertSame('media/images/test.jpg', $reference2->getFullPath());

        // Directory with trailing slash
        $reference3 = new MediaFileReference('test', 'jpg', 'public', 'media/images/');
        $this->assertSame('media/images/test.jpg', $reference3->getFullPath());

        // Directory with both leading and trailing slashes
        $reference4 = new MediaFileReference('test', 'jpg', 'public', '/media/images/');
        $this->assertSame('media/images/test.jpg', $reference4->getFullPath());

        // Multiple slashes
        $reference5 = new MediaFileReference('test', 'jpg', 'public', '//media//images//');
        $this->assertSame('media//images/test.jpg', $reference5->getFullPath());

        // Root directory (just slash)
        $reference6 = new MediaFileReference('test', 'jpg', 'public', '/');
        $this->assertSame('test.jpg', $reference6->getFullPath());

        // Deep nested directory
        $reference7 = new MediaFileReference('test', 'jpg', 'public', 'media/images/2024/01/15');
        $this->assertSame('media/images/2024/01/15/test.jpg', $reference7->getFullPath());
    }

    /** @test */
    public function it_handles_special_characters_in_paths(): void
    {
        // Special characters in filename
        $reference1 = new MediaFileReference('test-file_name', 'jpg', 'public', 'media');
        $this->assertSame('media/test-file_name.jpg', $reference1->getFullPath());

        // Unicode characters
        $reference2 = new MediaFileReference('测试文件', 'jpg', 'public', 'media');
        $this->assertSame('media/测试文件.jpg', $reference2->getFullPath());

        // Spaces in paths (though not recommended)
        $reference3 = new MediaFileReference('test file', 'jpg', 'public', 'media images');
        $this->assertSame('media images/test file.jpg', $reference3->getFullPath());

        // Numbers and special chars
        $reference4 = new MediaFileReference('file-123_v2', 'png', 'local', 'uploads/2024');
        $this->assertSame('uploads/2024/file-123_v2.png', $reference4->getFullPath());
    }

    /** @test */
    public function it_works_with_different_disk_names(): void
    {
        $reference1 = new MediaFileReference('test', 'jpg', 'local', 'media');
        $this->assertSame('local', $reference1->disk);

        $reference2 = new MediaFileReference('test', 'jpg', 's3', 'media');
        $this->assertSame('s3', $reference2->disk);

        $reference3 = new MediaFileReference('test', 'jpg', 'custom-disk-name', 'media');
        $this->assertSame('custom-disk-name', $reference3->disk);
    }

    /** @test */
    public function it_works_with_different_file_extensions(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'mp3', 'wav', 'pdf', 'doc', 'docx'];
        
        foreach ($extensions as $extension) {
            $reference = new MediaFileReference('test', $extension, 'public', 'media');
            $this->assertSame("test.{$extension}", $reference->getFileNameWithExtension());
            $this->assertSame("media/test.{$extension}", $reference->getFullPath());
        }
    }

    /** @test */
    public function it_preserves_case_sensitivity(): void
    {
        // Filenames
        $reference1 = new MediaFileReference('TestFile', 'JPG', 'public', 'Media');
        $this->assertSame('TestFile', $reference1->filename);
        $this->assertSame('JPG', $reference1->extension);
        $this->assertSame('Media', $reference1->directory);
        $this->assertSame('TestFile.JPG', $reference1->getFileNameWithExtension());
        $this->assertSame('Media/TestFile.JPG', $reference1->getFullPath());
    }

    /** @test */
    public function it_can_represent_the_same_file_in_different_locations(): void
    {
        $ref1 = new MediaFileReference('image', 'jpg', 'local', 'temp');
        $ref2 = new MediaFileReference('image', 'jpg', 'public', 'media');
        $ref3 = new MediaFileReference('image', 'jpg', 's3', 'uploads/images');

        // Same filename and extension, different storage locations
        $this->assertSame('image.jpg', $ref1->getFileNameWithExtension());
        $this->assertSame('image.jpg', $ref2->getFileNameWithExtension());
        $this->assertSame('image.jpg', $ref3->getFileNameWithExtension());

        // Different full paths
        $this->assertSame('temp/image.jpg', $ref1->getFullPath());
        $this->assertSame('media/image.jpg', $ref2->getFullPath());
        $this->assertSame('uploads/images/image.jpg', $ref3->getFullPath());
    }

    /** @test */
    public function it_handles_extremely_long_paths(): void
    {
        $longFilename = str_repeat('a', 100);
        $longDirectory = str_repeat('dir/', 50) . 'final';

        $reference = new MediaFileReference($longFilename, 'jpg', 'public', $longDirectory);

        $this->assertSame($longFilename, $reference->filename);
        $this->assertSame($longDirectory, $reference->directory);
        $this->assertStringContainsString($longFilename . '.jpg', $reference->getFileNameWithExtension());
        $this->assertStringContainsString($longDirectory, $reference->getFullPath());
    }

    /** @test */
    public function it_can_be_compared_for_equality(): void
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
}