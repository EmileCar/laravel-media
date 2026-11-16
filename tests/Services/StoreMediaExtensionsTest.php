<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Facades\Media;
use Carone\Media\Services\DeleteMediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\StoreMediaDataBuilder;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 'other' type, extended extension lists and supplied thumbnails (video poster frames)
 */
class StoreMediaExtensionsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('media.enabled_types', ['image', 'video', 'audio', 'document', 'other']);
        $app['config']->set('media.validation.other', ['max:10240']);
    }

    private function fakeFile(string $name, string $content, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'media_test');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function zipFile(string $name = 'archive.zip'): UploadedFile
    {
        // Minimal empty zip archive (end of central directory record)
        return $this->fakeFile($name, "PK\x05\x06" . str_repeat("\0", 18), 'application/zip');
    }

    private function videoFile(): UploadedFile
    {
        // Minimal MP4 'ftyp' box so the MIME type is detected as video/mp4
        $ftyp = pack('N', 24) . 'ftyp' . 'isom' . pack('N', 512) . 'isomiso2';

        return $this->fakeFile('clip.mp4', $ftyp . str_repeat("\0", 64), 'video/mp4');
    }

    public function test_other_files_can_be_stored(): void
    {
        $data = StoreMediaDataBuilder::fromFile($this->zipFile())->withName('Trip photos')->build();

        $this->assertSame(MediaType::OTHER, $data->type);

        $media = Media::store($data);

        $this->assertSame('other', $media->type);
        Storage::disk('local')->assertExists($media->loadFileReference()->getStoragePath());
    }

    public function test_other_type_must_be_enabled(): void
    {
        config(['media.enabled_types' => ['image']]);

        $data = StoreMediaDataBuilder::fromFile($this->zipFile())->withName('Archive')->build();

        $this->expectException(\InvalidArgumentException::class);
        Media::store($data);
    }

    public function test_extensions_outside_the_type_list_are_rejected(): void
    {
        $data = StoreMediaDataBuilder::fromFile($this->zipFile('archive.zip'))
            ->forType(MediaType::DOCUMENT)
            ->withName('Archive')
            ->build();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File extension '.zip' is not supported for document");
        Media::store($data);
    }

    public function test_banned_extensions_are_rejected_even_when_listed(): void
    {
        config(['media.types.other.extensions' => ['sh']]);

        $data = StoreMediaDataBuilder::fromFile($this->fakeFile('run.sh', 'echo hi', 'text/x-shellscript'))
            ->forType(MediaType::OTHER)
            ->withName('Script')
            ->build();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File type '.sh' is not allowed");
        Media::store($data);
    }

    public function test_mime_type_must_match_the_type(): void
    {
        $data = StoreMediaDataBuilder::fromFile($this->fakeFile('fake.mp4', 'just text', 'video/mp4'))
            ->withName('Fake video')
            ->build();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type');
        Media::store($data);
    }

    public function test_image_formats_that_cannot_be_re_encoded_are_stored_unprocessed(): void
    {
        $png = UploadedFile::fake()->image('scan.png', 50, 50);
        $tiff = $this->fakeFile('scan.tiff', file_get_contents($png->getRealPath()), 'image/png');

        $media = Media::store(StoreMediaDataBuilder::fromFile($tiff)->withName('Scan')->build());

        $this->assertSame('image', $media->type);
        $stored = Storage::disk('local')->get($media->loadFileReference()->getStoragePath());
        $this->assertSame(file_get_contents($png->getRealPath()), $stored);
    }

    public function test_a_supplied_thumbnail_is_stored_for_a_video(): void
    {
        $poster = UploadedFile::fake()->image('poster.jpg', 1280, 720);

        $media = Media::store(
            StoreMediaDataBuilder::fromFile($this->videoFile())
                ->withName('Clip')
                ->withThumbnailFile($poster)
                ->build()
        );

        $this->assertSame('video', $media->type);
        $this->assertNotNull($media->thumbnail_path);
        Storage::disk('local')->assertExists($media->loadThumbnailFileReference()->getThumbnailStoragePath());
    }

    public function test_a_supplied_thumbnail_is_resized_when_processing_is_enabled(): void
    {
        config(['media.thumbnails.process_supplied' => true]);
        $poster = UploadedFile::fake()->image('poster.png', 1280, 720);

        $media = Media::store(
            StoreMediaDataBuilder::fromFile($this->videoFile())
                ->withName('Clip')
                ->withThumbnailFile($poster)
                ->build()
        );

        $this->assertStringEndsWith('_thumb.jpg', $media->thumbnail_path);
        $stored = Storage::disk('local')->get($media->loadThumbnailFileReference()->getThumbnailStoragePath());
        [$width, $height] = getimagesizefromstring($stored);
        $this->assertLessThanOrEqual(300, $width);
        $this->assertLessThanOrEqual(300, $height);
    }

    public function test_a_supplied_thumbnail_must_be_an_image(): void
    {
        $data = StoreMediaDataBuilder::fromFile($this->videoFile())
            ->withName('Clip')
            ->withThumbnailFile($this->zipFile())
            ->build();

        $this->expectException(\InvalidArgumentException::class);
        Media::store($data);
    }

    public function test_deleting_media_removes_its_thumbnail(): void
    {
        $media = Media::store(
            StoreMediaDataBuilder::fromFile($this->videoFile())
                ->withName('Clip')
                ->withThumbnailFile(UploadedFile::fake()->image('poster.jpg', 100, 100))
                ->build()
        );
        $filePath = $media->loadFileReference()->getStoragePath();
        $thumbnailPath = $media->loadThumbnailFileReference()->getThumbnailStoragePath();

        (new DeleteMediaService())->delete($media->id);

        Storage::disk('local')->assertMissing($filePath);
        Storage::disk('local')->assertMissing($thumbnailPath);
    }
}
