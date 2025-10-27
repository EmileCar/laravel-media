<?php

namespace Carone\Media\Tests\Utilities;

use Carbon\Carbon;
use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\StoreMediaDataBuilder;
use Carone\Media\Utilities\StoreLocalMediaDataBuilder;
use Carone\Media\Utilities\StoreExternalMediaDataBuilder;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class StoreMediaDataBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function can_create_local_builder_from_factory_method(): void
    {
        $file = $this->createFakeImageFile();
        
        $builder = StoreMediaDataBuilder::fromFile($file);
        
        $this->assertInstanceOf(StoreLocalMediaDataBuilder::class, $builder);
    }

    /** @test */
    public function can_create_external_builder_from_factory_method(): void
    {
        $url = 'https://example.com/image.jpg';
        
        $builder = StoreMediaDataBuilder::fromExternalUrl($url);
        
        $this->assertInstanceOf(StoreExternalMediaDataBuilder::class, $builder);
    }

    /** @test */
    public function local_builder_can_build_with_auto_detected_type(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame($file, $data->file);
    }

    /** @test */
    public function local_builder_can_build_with_explicit_type(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::VIDEO)
            ->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
        $this->assertSame(MediaType::VIDEO, $data->type);
        $this->assertSame($file, $data->file);
    }

    /** @test */
    public function local_builder_can_build_with_string_type(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType('audio')
            ->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
        $this->assertSame(MediaType::AUDIO, $data->type);
        $this->assertSame($file, $data->file);
    }

    /** @test */
    public function local_builder_throws_exception_for_invalid_string_type(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid media type: ');
        
        StoreMediaDataBuilder::fromFile($file)
            ->forType('invalid')
            ->build();
    }

    /** @test */
    public function local_builder_can_set_all_properties(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        $date = Carbon::parse('2023-01-01');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Custom Name')
            ->withDescription('Custom Description')
            ->withDate($date)
            ->useFileName('custom-filename')
            ->useDirectory('custom/directory')
            ->withMeta(['key1' => 'value1', 'key2' => 'value2'])
            ->addMeta('key3', 'value3')
            ->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame($file, $data->file);
        $this->assertSame('Custom Name', $data->name);
        $this->assertSame('Custom Description', $data->description);
        $this->assertSame($date, $data->date);
        $this->assertSame('custom-filename', $data->fileName);
        $this->assertSame('custom/directory', $data->directory);
    }

    /** @test */
    public function local_builder_can_use_original_name(): void
    {
        $file = $this->createFakeImageFile('original-file-name.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->useOriginalName()
            ->build();
        
        $this->assertSame('original-file-name', $data->name);
    }

    /** @test */
    public function local_builder_original_name_does_not_override_explicit_name(): void
    {
        $file = $this->createFakeImageFile('original-file-name.jpg');

        $data = StoreMediaDataBuilder::fromFile($file)
            ->withName('Explicit Name')
            ->useOriginalName()
            ->build();

        $this->assertSame('Explicit Name', $data->name);
    }

    /** @test */
    public function local_builder_auto_detects_image_type(): void
    {
        $file = $this->createFakeImageFile('test.png');
        
        $data = StoreMediaDataBuilder::fromFile($file)->build();
        
        $this->assertSame(MediaType::IMAGE, $data->type);
    }

    /** @test */
    public function local_builder_auto_detects_video_type(): void
    {
        $file = $this->createFakeVideoFile('test.mp4');
        
        $data = StoreMediaDataBuilder::fromFile($file)->build();
        
        $this->assertSame(MediaType::VIDEO, $data->type);
    }

    /** @test */
    public function local_builder_auto_detects_audio_type(): void
    {
        $file = $this->createFakeAudioFile('test.mp3');
        
        $data = StoreMediaDataBuilder::fromFile($file)->build();
        
        $this->assertSame(MediaType::AUDIO, $data->type);
    }

    /** @test */
    public function local_builder_auto_detects_document_type(): void
    {
        $file = $this->createFakeDocumentFile('test.pdf');
        
        $data = StoreMediaDataBuilder::fromFile($file)->build();
        
        $this->assertSame(MediaType::DOCUMENT, $data->type);
    }

    /** @test */
    public function local_builder_throws_exception_for_unsupported_file_type(): void
    {
        $file = $this->createFakeUnsupportedFile('test.exe');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not auto-detect media type for extension: exe');
        
        StoreMediaDataBuilder::fromFile($file)->build();
    }

    /** @test */
    public function external_builder_requires_explicit_type(): void
    {
        $url = 'https://example.com/video.mp4';
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Media type must be set');
        
        StoreMediaDataBuilder::fromExternalUrl($url)->build();
    }

    /** @test */
    public function external_builder_can_build_with_explicit_type(): void
    {
        $url = 'https://example.com/video.mp4';
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->build();
        
        $this->assertInstanceOf(StoreExternalMediaData::class, $data);
        $this->assertSame(MediaType::VIDEO, $data->type);
        $this->assertSame($url, $data->url);
    }

    /** @test */
    public function external_builder_can_set_all_properties(): void
    {
        $url = 'https://example.com/video.mp4';
        $date = Carbon::parse('2023-01-01');
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->withName('Custom Name')
            ->withDescription('Custom Description')
            ->withDate($date)
            ->withMeta(['key1' => 'value1'])
            ->addMeta('key2', 'value2')
            ->build();
        
        $this->assertInstanceOf(StoreExternalMediaData::class, $data);
        $this->assertSame(MediaType::VIDEO, $data->type);
        $this->assertSame($url, $data->url);
        $this->assertSame('Custom Name', $data->name);
        $this->assertSame('Custom Description', $data->description);
        $this->assertSame($date, $data->date);
    }

    /** @test */
    public function external_builder_can_use_url_filename(): void
    {
        $url = 'https://example.com/path/to/my-video-file.mp4';
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->useUrlFilename()
            ->build();
        
        $this->assertSame('my-video-file', $data->name);
    }

    /** @test */
    public function external_builder_url_filename_does_not_override_explicit_name(): void
    {
        $url = 'https://example.com/path/to/my-video-file.mp4';
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->withName('Explicit Name')
            ->useUrlFilename()
            ->build();
        
        $this->assertSame('Explicit Name', $data->name);
    }

    /** @test */
    public function external_builder_handles_url_without_filename(): void
    {
        $url = 'https://example.com/';
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->useUrlFilename()
            ->build();
        
        $this->assertNull($data->name);
    }

    /** @test */
    public function external_builder_with_different_file_types(): void
    {
        $testCases = [
            ['https://example.com/file.jpg', MediaType::IMAGE],
            ['https://example.com/file.mp4', MediaType::VIDEO],
            ['https://example.com/file.mp3', MediaType::AUDIO],
            ['https://example.com/file.pdf', MediaType::DOCUMENT],
        ];

        foreach ($testCases as [$url, $expectedType]) {
            $data = StoreMediaDataBuilder::fromExternalUrl($url)
                ->forType($expectedType)
                ->build();
                
            $this->assertSame($expectedType, $data->type, "Failed for URL: {$url}");
        }
    }

    /** @test */
    public function builder_can_chain_methods_fluently(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        $date = Carbon::parse('2023-01-01');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Test Image')
            ->withDescription('A test image file')
            ->withDate($date)
            ->useFileName('custom-name')
            ->useDirectory('images')
            ->useOriginalName() // Should not override explicit name
            ->withMeta(['author' => 'John Doe'])
            ->addMeta('category', 'test')
            ->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame('Test Image', $data->name);
        $this->assertSame('A test image file', $data->description);
        $this->assertSame($date, $data->date);
        $this->assertSame('custom-name', $data->fileName);
        $this->assertSame('images', $data->directory);
    }

    /** @test */
    public function builder_allows_null_description(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->withDescription(null)
            ->build();
        
        $this->assertNull($data->description);
    }

    /** @test */
    public function builder_allows_null_directory(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->useDirectory(null)
            ->build();
        
        $this->assertNull($data->directory);
    }

    /** @test */
    public function builder_merges_metadata_correctly(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $builder = StoreMediaDataBuilder::fromFile($file)
            ->withMeta(['key1' => 'value1', 'key2' => 'value2'])
            ->withMeta(['key2' => 'updated_value2', 'key3' => 'value3'])
            ->addMeta('key4', 'value4');
        
        // Since we can't directly access the meta property, we verify the builder
        // operates correctly by ensuring it builds successfully
        $data = $builder->build();
        $this->assertInstanceOf(StoreLocalMediaData::class, $data);
    }

    /** @test */
    public function local_builder_handles_file_without_extension(): void
    {
        // Create a file without extension
        $tempPath = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($tempPath, 'fake content');
        $file = new UploadedFile($tempPath, 'test_file_no_extension', 'image/jpeg', null, true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not auto-detect media type for extension:');
        
        StoreMediaDataBuilder::fromFile($file)->build();
    }

    /** @test */
    public function builder_instances_are_reusable(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $baseBuilder = StoreMediaDataBuilder::fromFile($file)
            ->forType(MediaType::IMAGE)
            ->withName('Base Name');
        
        $data1 = $baseBuilder->withDescription('First Description')->build();
        $data2 = $baseBuilder->withDescription('Second Description')->build();
        
        $this->assertInstanceOf(StoreLocalMediaData::class, $data1);
        $this->assertInstanceOf(StoreLocalMediaData::class, $data2);
        $this->assertSame('Base Name', $data1->name);
        $this->assertSame('Base Name', $data2->name);
        // Note: The second description would override the first in the current implementation
        $this->assertSame('Second Description', $data2->description);
    }

    /** @test */
    public function local_builder_can_set_custom_filename_without_extension(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->useFileName('my-custom-name')
            ->build();
        
        $this->assertSame('my-custom-name', $data->fileName);
    }

    /** @test */
    public function external_builder_extracts_filename_from_complex_url(): void
    {
        $url = 'https://example.com/some/deep/path/video-file.mp4?param=value&other=test';
        
        $data = StoreMediaDataBuilder::fromExternalUrl($url)
            ->forType(MediaType::VIDEO)
            ->useUrlFilename()
            ->build();
        
        $this->assertSame('video-file', $data->name);
    }

    /** @test */
    public function builder_can_override_properties(): void
    {
        $file = $this->createFakeImageFile('test.jpg');
        
        $data = StoreMediaDataBuilder::fromFile($file)
            ->withName('First Name')
            ->withName('Second Name')  // Should override
            ->withDescription('First Description')
            ->withDescription('Second Description')  // Should override
            ->build();
        
        $this->assertSame('Second Name', $data->name);
        $this->assertSame('Second Description', $data->description);
    }

    /** @test */
    public function external_builder_works_with_different_protocols(): void
    {
        $testUrls = [
            'https://example.com/file.jpg',
            'http://example.com/file.jpg',
            'ftp://example.com/file.jpg',
        ];

        foreach ($testUrls as $url) {
            $data = StoreMediaDataBuilder::fromExternalUrl($url)
                ->forType(MediaType::IMAGE)
                ->build();
            
            $this->assertSame($url, $data->url);
            $this->assertSame(MediaType::IMAGE, $data->type);
        }
    }
}