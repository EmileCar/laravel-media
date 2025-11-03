<?php

namespace Carone\Media\Tests\ValueObjects;

use Carone\Media\UploadStrategies\UploadMediaStrategy;
use Carbon\Carbon;
use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\ValueObjects\StoreExternalMediaData;
use Carone\Media\ValueObjects\StoreLocalMediaData;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;

class StoreMediaDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function store_local_media_data_can_be_constructed_with_all_parameters(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $date = Carbon::now();

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'custom-name',
            name: 'Test Image',
            description: 'A test image',
            date: $date,
            directory: 'custom/directory'
        );

        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame($file, $data->file);
        $this->assertSame('custom-name', $data->fileName);
        $this->assertSame('Test Image', $data->name);
        $this->assertSame('A test image', $data->description);
        $this->assertSame($date, $data->date);
        $this->assertSame('custom/directory', $data->directory);
    }

    /** @test */
    public function store_local_media_data_can_be_constructed_with_minimal_parameters(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $date = Carbon::now();

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: $date,
            directory: null
        );

        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame($file, $data->file);
        $this->assertNull($data->fileName);
        $this->assertSame('Test Image', $data->name);
        $this->assertNull($data->description);
        $this->assertSame($date, $data->date);
        $this->assertNull($data->directory);
    }

    /** @test */
    public function store_local_media_data_to_array_includes_all_data(): void
    {
        $file = UploadedFile::fake()->image('original.jpg');
        $date = Carbon::parse('2024-01-15');

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'custom-name',
            name: 'Test Image',
            description: 'A test image',
            date: $date,
            directory: 'media/images'
        );

        $array = $data->toArray();

        $this->assertSame('image', $array['type']);
        $this->assertSame('Test Image', $array['name']);
        $this->assertSame('A test image', $array['description']);
        $this->assertSame('2024-01-15', $array['date']);
        $this->assertSame($file, $array['file']);
        $this->assertSame('custom-name', $array['file_name']);
        $this->assertSame('media/images', $array['directory']);
    }

    /** @test */
    public function store_local_media_data_to_array_handles_null_filename(): void
    {
        $file = UploadedFile::fake()->image('original.jpg');
        $date = Carbon::now();

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test Image',
            description: null,
            date: $date,
            directory: null
        );

        $array = $data->toArray();

        // Should fall back to original filename
        $this->assertSame('original.jpg', $array['file_name']);
    }

    /** @test */
    public function store_local_media_data_returns_correct_validation_rules(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: null,
            name: 'Test',
            description: null,
            date: Carbon::now(),
            directory: null
        );

        $rules = $data->rules();

        // Should include base rules and file-specific rules
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('date', $rules);
        $this->assertArrayHasKey('file', $rules);
        $this->assertArrayHasKey('file_name', $rules);
        $this->assertArrayHasKey('directory', $rules);
        $this->assertArrayHasKey('disk', $rules);

        $this->assertSame('required|file', $rules['file']);
        $this->assertSame('nullable|string|max:255', $rules['file_name']);
    }

    /** @test */
    public function store_local_media_data_can_store_with_strategy(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'test',
            name: 'Test Image',
            description: 'Test',
            date: Carbon::now(),
            directory: 'test'
        );

        $strategy = $this->createMock(UploadMediaStrategy::class);
        $expectedResource = new MediaResource();

        $strategy->expects($this->once())
            ->method('storeLocalFile')
            ->with($data)
            ->willReturn($expectedResource);

        $result = $data->storeWith($strategy);

        $this->assertSame($expectedResource, $result);
    }

    /** @test */
    public function store_external_media_data_can_be_constructed(): void
    {
        $date = Carbon::now();

        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'External Image',
            description: 'An external image',
            date: $date
        );

        $this->assertSame(MediaType::IMAGE, $data->type);
        $this->assertSame('https://example.com/image.jpg', $data->url);
        $this->assertSame('External Image', $data->name);
        $this->assertSame('An external image', $data->description);
        $this->assertSame($date, $data->date);
    }

    /** @test */
    public function store_external_media_data_can_be_constructed_with_minimal_parameters(): void
    {
        $date = Carbon::now();

        $data = new StoreExternalMediaData(
            type: MediaType::VIDEO,
            url: 'https://example.com/video.mp4',
            name: 'External Video',
            description: null,
            date: $date
        );

        $this->assertSame(MediaType::VIDEO, $data->type);
        $this->assertSame('https://example.com/video.mp4', $data->url);
        $this->assertSame('External Video', $data->name);
        $this->assertNull($data->description);
        $this->assertSame($date, $data->date);
    }

    /** @test */
    public function store_external_media_data_to_array_includes_all_data(): void
    {
        $date = Carbon::parse('2024-01-15');

        $data = new StoreExternalMediaData(
            type: MediaType::VIDEO,
            url: 'https://example.com/video.mp4',
            name: 'External Video',
            description: 'A test video',
            date: $date
        );

        $array = $data->toArray();

        $this->assertSame('video', $array['type']);
        $this->assertSame('External Video', $array['name']);
        $this->assertSame('A test video', $array['description']);
        $this->assertSame('2024-01-15', $array['date']);
        $this->assertSame('https://example.com/video.mp4', $array['url']);
    }

    /** @test */
    public function store_external_media_data_returns_correct_validation_rules(): void
    {
        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'Test',
            description: null,
            date: Carbon::now()
        );

        $rules = $data->rules();

        // Should include base rules and URL-specific rules
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('date', $rules);
        $this->assertArrayHasKey('url', $rules);

        $this->assertSame('required|url|max:1000', $rules['url']);
    }

    /** @test */
    public function store_external_media_data_can_store_with_strategy(): void
    {
        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'Test Image',
            description: 'Test',
            date: Carbon::now()
        );

        $strategy = $this->createMock(UploadMediaStrategy::class);
        $expectedResource = new MediaResource();

        $strategy->expects($this->once())
            ->method('storeExternalFile')
            ->with($data)
            ->willReturn($expectedResource);

        $result = $data->storeWith($strategy);

        $this->assertSame($expectedResource, $result);
    }

    /** @test */
    public function store_media_data_works_with_different_media_types(): void
    {
        $date = Carbon::now();

        // Test all media types
        $types = [
            [MediaType::IMAGE, 'https://example.com/image.jpg'],
            [MediaType::VIDEO, 'https://example.com/video.mp4'],
            [MediaType::AUDIO, 'https://example.com/audio.mp3'],
            [MediaType::DOCUMENT, 'https://example.com/document.pdf'],
        ];

        foreach ($types as [$type, $url]) {
            $data = new StoreExternalMediaData(
                type: $type,
                url: $url,
                name: "Test {$type->value}",
                description: null,
                date: $date
            );

            $this->assertSame($type, $data->type);
            $this->assertSame($url, $data->url);
            $this->assertSame("Test {$type->value}", $data->name);
        }
    }

    /** @test */
    public function store_local_media_data_works_with_different_file_types(): void
    {
        $date = Carbon::now();

        $files = [
            'image' => UploadedFile::fake()->image('test.jpg'),
            'document' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        ];

        foreach ($files as $type => $file) {
            $mediaType = $type === 'image' ? MediaType::IMAGE : MediaType::DOCUMENT;

            $data = new StoreLocalMediaData(
                type: $mediaType,
                file: $file,
                fileName: null,
                name: "Test {$type}",
                description: null,
                date: $date,
                directory: null
            );

            $this->assertSame($mediaType, $data->type);
            $this->assertSame($file, $data->file);
            $this->assertSame("Test {$type}", $data->name);
        }
    }

    /** @test */
    public function store_media_data_handles_edge_case_inputs(): void
    {
        $date = Carbon::now();

        // Very long name
        $longName = str_repeat('A', 300);
        $data1 = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: $longName,
            description: null,
            date: $date
        );
        $this->assertSame($longName, $data1->name);

        // Very long description
        $longDescription = str_repeat('Description ', 200);
        $data2 = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'Test',
            description: $longDescription,
            date: $date
        );
        $this->assertSame($longDescription, $data2->description);

        // Very long URL
        $longUrl = 'https://example.com/' . str_repeat('path/', 100) . 'file.jpg';
        $data3 = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: $longUrl,
            name: 'Test',
            description: null,
            date: $date
        );
        $this->assertSame($longUrl, $data3->url);
    }

    /** @test */
    public function store_media_data_preserves_date_precision(): void
    {
        $preciseDate = Carbon::parse('2024-01-15 14:30:45.123456');

        $data = new StoreExternalMediaData(
            type: MediaType::IMAGE,
            url: 'https://example.com/image.jpg',
            name: 'Test',
            description: null,
            date: $preciseDate
        );

        $this->assertSame($preciseDate, $data->date);

        // to_array should format as date string
        $array = $data->toArray();
        $this->assertSame('2024-01-15', $array['date']);
    }

    /** @test */
    public function store_local_media_data_handles_special_characters_in_filename(): void
    {
        $file = UploadedFile::fake()->image('test-file_name.jpg');
        $date = Carbon::now();

        $data = new StoreLocalMediaData(
            type: MediaType::IMAGE,
            file: $file,
            fileName: 'special-chars_123',
            name: 'Test with émojis 🎉',
            description: 'Description with "quotes" and symbols: @#$%',
            date: $date,
            directory: 'path/with-special_chars'
        );

        $this->assertSame('special-chars_123', $data->fileName);
        $this->assertSame('Test with émojis 🎉', $data->name);
        $this->assertSame('Description with "quotes" and symbols: @#$%', $data->description);
        $this->assertSame('path/with-special_chars', $data->directory);
    }

    /** @test */
    public function store_external_media_data_handles_various_url_formats(): void
    {
        $date = Carbon::now();

        $urls = [
            'https://example.com/image.jpg',
            'http://subdomain.example.org/path/to/file.png',
            'https://cdn.example.com/uploads/2024/01/15/file-name_123.jpg',
            'https://example.com/file.jpg?v=123&format=webp',
            'https://example.com/file.jpg#anchor',
        ];

        foreach ($urls as $url) {
            $data = new StoreExternalMediaData(
                type: MediaType::IMAGE,
                url: $url,
                name: 'Test',
                description: null,
                date: $date
            );

            $this->assertSame($url, $data->url);

            $array = $data->toArray();
            $this->assertSame($url, $array['url']);
        }
    }
}
