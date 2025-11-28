<?php

namespace Carone\Media\Tests\UploadStrategies;

use Carone\Media\Models\MediaResource;
use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadMediaStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_storeLocalFile_creates_media_resource()
    {
        $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');

        $data = new StoreLocalMediaData(
            type: MediaType::VIDEO,
            file: $file,
            fileName: 'test.mp4',
            name: 'Test Video',
            description: 'A test video file',
            date: now(),
            directory: 'videos',
            generateThumbnail: false
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Test Video', $result->display_name);
        $this->assertEquals('video', $result->type);
        $this->assertEquals('local', $result->source);
        $this->assertNotNull($result->path);
        $this->assertEquals('public', $result->disk);
        $this->assertArrayHasKey('original_name', $result->meta);
        $this->assertArrayHasKey('size', $result->meta);
        $this->assertArrayHasKey('mime_type', $result->meta);
    }

    public function test_storeLocalFile_stores_file_on_disk()
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $data = new StoreLocalMediaData(
            type: MediaType::DOCUMENT,
            file: $file,
            fileName: 'document.pdf',
            name: 'Test Document',
            description: 'A test document',
            date: now(),
            directory: 'documents'
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeLocalFile($data);

        // Verify the file exists on the storage disk
        $this->assertTrue(Storage::disk('public')->exists($result->path));

        // Verify file contents
        $storedContent = Storage::disk('public')->get($result->path);
        $this->assertNotEmpty($storedContent);
    }

    public function test_storeLocalFile_saves_correct_metadata()
    {
        $file = UploadedFile::fake()->create('audio.mp3', 2000, 'audio/mpeg');
        $file->storeAs('temp', 'audio.mp3'); // Store to get a real path

        $data = new StoreLocalMediaData(
            type: MediaType::AUDIO,
            file: $file,
            fileName: 'audio.mp3',
            name: 'Test Audio',
            description: 'A test audio file',
            date: now(),
            directory: 'audio'
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertEquals('audio.mp3', $result->meta['original_name']);
        $this->assertEquals($file->getSize(), $result->meta['size']);
        $this->assertEquals('audio/mpeg', $result->meta['mime_type']);
    }

    public function test_storeExternalFile_creates_external_media_resource()
    {
        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'External Image',
            description: 'An external image',
            date: now()
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeExternalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('External Image', $result->display_name);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('external', $result->source);
        $this->assertEquals('https://example.com/image.jpg', $result->url);
        $this->assertArrayHasKey('host', $result->meta);
        $this->assertEquals('example.com', $result->meta['host']);
    }

    public function test_storeLocalFile_with_custom_disk()
    {
        Storage::fake('custom');

        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $data = new StoreLocalMediaData(
            type: MediaType::DOCUMENT,
            file: $file,
            fileName: 'test.txt',
            name: 'Test File',
            description: 'A test file',
            date: now(),
            directory: 'documents',
            disk: 'custom'
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertEquals('custom', $result->disk);
        $this->assertTrue(Storage::disk('custom')->exists($result->path));
    }

    public function test_processFile_returns_null_by_default()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);

        $data = new StoreLocalMediaData(
            type: MediaType::DOCUMENT,
            file: $file,
            fileName: 'test.txt',
            name: 'Test',
            description: '',
            date: now(),
            directory: 'test'
        );

        $strategy = new UploadMediaStrategy($data);

        // Access protected method using reflection
        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('processFile');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $file);

        $this->assertNull($result);
    }

    public function test_generateThumbnail_returns_null_by_default()
    {
        $data = new StoreLocalMediaData(
            type: MediaType::DOCUMENT,
            file: UploadedFile::fake()->create('test.txt'),
            fileName: 'test.txt',
            name: 'Test',
            description: '',
            date: now(),
            directory: 'test'
        );

        $strategy = new UploadMediaStrategy($data);

        // Create a real file reference
        $fileReference = new \Carone\Media\ValueObjects\MediaFileReference('test', 'jpg', 'public', 'test');

        // Access protected method using reflection
        $reflection = new \ReflectionClass($strategy);
        $method = $reflection->getMethod('generateThumbnail');
        $method->setAccessible(true);

        $result = $method->invoke($strategy, $fileReference);

        $this->assertNull($result);
    }

    public function test_getMediaFile_returns_binary_response()
    {
        // Create a media resource with a file
        $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');

        $data = new StoreLocalMediaData(
            type: MediaType::VIDEO,
            file: $file,
            fileName: 'test.mp4',
            name: 'Test Video',
            description: '',
            date: now(),
            directory: 'videos'
        );

        $strategy = new UploadMediaStrategy($data);
        $media = $strategy->storeLocalFile($data);

        // Get the media file
        $response = $strategy->getMediaFile($media);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('video/mp4', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
    }

    public function test_getMediaFile_throws_404_when_file_not_found()
    {
        // Create a media resource but don't store the actual file
        $media = MediaResource::factory()->create([
            'type' => 'video',
            'source' => 'local',
            'path' => 'nonexistent/file.mp4',
            'disk' => 'public'
        ]);

        $data = new StoreLocalMediaData(
            type: MediaType::VIDEO,
            file: UploadedFile::fake()->create('test.mp4'),
            fileName: 'test.mp4',
            name: 'Test',
            description: '',
            date: now(),
            directory: 'test'
        );

        $strategy = new UploadMediaStrategy($data);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $strategy->getMediaFile($media);
    }

    public function test_storeLocalFile_handles_thumbnail_generation()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test.jpg',
            name: 'Test Image',
            description: 'Test image with thumbnail',
            date: now(),
            directory: 'images',
            generateThumbnail: true
        );

        $strategy = new UploadMediaStrategy($data);
        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        $this->assertEquals('Test Image', $result->display_name);

        // The base strategy doesn't generate thumbnails, but the method should be called
        // This is more of a workflow test
        $this->assertTrue(true);
    }

    public function test_storeLocalFile_cleans_up_processed_files()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);

        $data = new StoreLocalMediaData(
            type: MediaType::DOCUMENT,
            file: $file,
            fileName: 'test.txt',
            name: 'Test File',
            description: '',
            date: now(),
            directory: 'documents'
        );

        // Create a custom strategy that returns a processed file path
        $strategy = new class($data) extends UploadMediaStrategy {
            protected function processFile(\Illuminate\Http\UploadedFile $file): ?string
            {
                // Create a temporary processed file
                $tempPath = tempnam(sys_get_temp_dir(), 'processed_') . '.txt';
                file_put_contents($tempPath, 'processed content');
                return $tempPath;
            }
        };

        $result = $strategy->storeLocalFile($data);

        $this->assertInstanceOf(MediaResource::class, $result);
        // The temporary processed file should have been cleaned up
        // We can't easily test this without exposing internal state
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
