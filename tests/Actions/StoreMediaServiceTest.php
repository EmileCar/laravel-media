<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Contracts\StoreMediaServiceInterface;
use Carone\Media\Services\StoreMediaService;
use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;

class StoreMediaServiceTest extends TestCase
{
    private StoreMediaServiceInterface $action;

    protected function setUp(): void
    {
        parent::setUp();
    $this->action = app(StoreMediaServiceInterface::class);
    }

    /** @test */
    public function it_can_upload_an_image_file()
    {
        $file = $this->createFakeImageFile('test-image.jpg');
        
        $data = [
            'type' => 'image',
            'file' => $file,
            'name' => 'Test Image',
            'description' => 'A test image for testing',
        ];

        $media = $this->action->handle($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('image', $media->type);
        $this->assertEquals('local', $media->source);
        $this->assertEquals('Test Image', $media->name);
        $this->assertEquals('A test image for testing', $media->description);
        $this->assertNotNull($media->file_name);
        $this->assertStringEndsWith('.jpg', $media->file_name);

        // Assert file was stored
        $this->assertFileExistsInStorage('local', 'media/image/' . $media->file_name);
        
        // Assert thumbnail was created
        $thumbnailPath = 'media/image/thumbnails/' . pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
        $this->assertFileExistsInStorage('local', $thumbnailPath);
    }

    /** @test */
    public function it_can_upload_a_video_file()
    {
        $file = $this->createFakeVideoFile('test-video.mp4');
        
        $data = [
            'type' => 'video',
            'file' => $file,
            'name' => 'Test Video',
            'description' => 'A test video for testing',
        ];

        $media = $this->action->handle($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('video', $media->type);
        $this->assertEquals('local', $media->source);
        $this->assertEquals('Test Video', $media->name);
        $this->assertStringEndsWith('.mp4', $media->file_name);

        // Assert file was stored
        $this->assertFileExistsInStorage('local', 'media/video/' . $media->file_name);
    }

    /** @test */
    public function it_can_upload_an_audio_file()
    {
        $file = $this->createFakeAudioFile('test-audio.mp3');
        
        $data = [
            'type' => 'audio',
            'file' => $file,
            'name' => 'Test Audio',
            'description' => 'A test audio for testing',
        ];

        $media = $this->action->handle($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('audio', $media->type);
        $this->assertEquals('local', $media->source);
        $this->assertEquals('Test Audio', $media->name);
        $this->assertStringEndsWith('.mp3', $media->file_name);

        // Assert file was stored
        $this->assertFileExistsInStorage('local', 'media/audio/' . $media->file_name);
    }

    /** @test */
    public function it_can_upload_a_document_file()
    {
        $file = $this->createFakeDocumentFile('test-document.pdf');
        
        $data = [
            'type' => 'document',
            'file' => $file,
            'name' => 'Test Document',
            'description' => 'A test document for testing',
        ];

        $media = $this->action->handle($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('document', $media->type);
        $this->assertEquals('local', $media->source);
        $this->assertEquals('Test Document', $media->name);
        $this->assertStringEndsWith('.pdf', $media->file_name);

        // Assert file was stored
        $this->assertFileExistsInStorage('local', 'media/document/' . $media->file_name);
    }

    /** @test */
    public function it_can_upload_external_media()
    {
        $data = [
            'type' => 'video',
            'source' => 'external',
            'url' => 'https://www.youtube.com/watch?v=example',
            'name' => 'External Video',
            'description' => 'A video from YouTube',
        ];

        $media = $this->action->handle($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('video', $media->type);
        $this->assertEquals('external', $media->source);
        $this->assertEquals('https://www.youtube.com/watch?v=example', $media->url);
        $this->assertEquals('External Video', $media->name);
        $this->assertNull($media->file_name);
    }

    /** @test */
    public function it_stores_meta_data_correctly()
    {
        $file = $this->createFakeImageFile('test-image.jpg');
        
        $data = [
            'type' => 'image',
            'file' => $file,
            'name' => 'Test Image',
            'meta' => ['custom_field' => 'custom_value'],
        ];

        $media = $this->action->handle($data);

        $this->assertArrayHasKey('original_name', $media->meta);
        $this->assertArrayHasKey('size', $media->meta);
        $this->assertArrayHasKey('mime_type', $media->meta);
        $this->assertArrayHasKey('custom_field', $media->meta);
        $this->assertEquals('custom_value', $media->meta['custom_field']);
        $this->assertEquals('test-image.jpg', $media->meta['original_name']);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $file1 = $this->createFakeImageFile('duplicate.jpg');
        $file2 = $this->createFakeImageFile('duplicate.jpg');
        
        $data1 = [
            'type' => 'image',
            'file' => $file1,
            'name' => 'duplicate',
        ];

        $data2 = [
            'type' => 'image',
            'file' => $file2,
            'name' => 'duplicate',
        ];

        $media1 = $this->action->handle($data1);
        $media2 = $this->action->handle($data2);

        $this->assertNotEquals($media1->file_name, $media2->file_name);
        $this->assertFileExistsInStorage('local', 'media/image/' . $media1->file_name);
        $this->assertFileExistsInStorage('local', 'media/image/' . $media2->file_name);
    }

    /** @test */
    public function it_uses_filename_when_no_name_provided()
    {
        $file = $this->createFakeImageFile('beautiful-sunset.jpg');
        
        $data = [
            'type' => 'image',
            'file' => $file,
        ];

        $media = $this->action->handle($data);

        $this->assertEquals('beautiful-sunset', $media->name);
    }

    /** @test */
    public function it_throws_exception_for_invalid_media_type()
    {
        $file = $this->createFakeImageFile();
        
        $data = [
            'type' => 'invalid_type',
            'file' => $file,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->action->handle($data);
    }

    /** @test */
    public function it_throws_exception_for_disabled_media_type()
    {
        // Temporarily disable image type
        config(['media.enabled_types' => ['video', 'audio', 'document']]);
        
        $file = $this->createFakeImageFile();
        
        $data = [
            'type' => 'image',
            'file' => $file,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Media type 'image' is not enabled");

        $this->action->handle($data);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_file_type()
    {
        $file = $this->createFakeUnsupportedFile('malware.exe');
        
        $data = [
            'type' => 'document',
            'file' => $file,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type not supported');

        $this->action->handle($data);
    }

    /** @test */
    public function it_throws_exception_for_banned_file_types()
    {
        config(['media.banned_file_types' => ['exe', 'bat', 'cmd']]);
        
        $file = $this->createFakeUnsupportedFile('test.exe');
        
        $data = [
            'type' => 'document',
            'file' => $file,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File type '.exe' is not allowed");

        $this->action->handle($data);
    }

    /** @test */
    public function it_throws_exception_when_file_missing_for_local_upload()
    {
        $data = [
            'type' => 'image',
            'source' => 'local',
            'name' => 'Test Image',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File is required for local uploads');

        $this->action->handle($data);
    }

    /** @test */
    public function it_throws_exception_when_url_missing_for_external_upload()
    {
        $data = [
            'type' => 'video',
            'source' => 'external',
            'name' => 'External Video',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL is required for external media');

        $this->action->handle($data);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $data = [
            // Missing type
            'name' => 'Test Media',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->action->handle($data);
    }

    /** @test */
    public function it_can_be_called_statically()
    {
        $file = $this->createFakeImageFile();
        
        $data = [
            'type' => 'image',
            'file' => $file,
            'name' => 'Static Test',
        ];

    $media = StoreMediaService::run($data);

        $this->assertInstanceOf(MediaResource::class, $media);
        $this->assertEquals('Static Test', $media->name);
    }

    /** @test */
    public function it_sets_default_date_when_not_provided()
    {
        $file = $this->createFakeImageFile();
        
        $data = [
            'type' => 'image',
            'file' => $file,
            'name' => 'Date Test',
        ];

        $media = $this->action->handle($data);

        $this->assertEquals(now()->toDateString(), $media->date->toDateString());
    }

    /** @test */
    public function it_uses_provided_date()
    {
        $file = $this->createFakeImageFile();
        
        $data = [
            'type' => 'image',
            'file' => $file,
            'name' => 'Date Test',
            'date' => '2024-01-15',
        ];

        $media = $this->action->handle($data);

        $this->assertEquals('2024-01-15', $media->date->toDateString());
    }
}