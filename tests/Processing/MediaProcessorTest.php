<?php

namespace Carone\Media\Tests\Processing;

use Carone\Media\Processing\LocalMediaRequest;
use Carone\Media\Processing\MediaProcessor;
use Carone\Media\Processing\MediaRequestBuilder;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;

class MediaProcessorTest extends TestCase
{
    protected MediaProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new MediaProcessor();
    }

    /** @test */
    public function it_can_process_basic_media_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $request = MediaRequestBuilder::forLocalFile($file)
            ->type(MediaType::IMAGE)
            ->name('Test Image')
            ->build();

        $result = $this->processor->processLocalFile($request);

        $this->assertInstanceOf(\Carone\Media\Models\MediaResource::class, $result);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('Test Image', $result->display_name);
        $this->assertEquals('local', $result->source);
    }

    /** @test */
    public function it_can_process_external_media(): void
    {
        $request = MediaRequestBuilder::forExternalUrl('https://example.com/image.jpg')
            ->type(MediaType::IMAGE)
            ->name('External Image')
            ->build();

        $result = $this->processor->processExternalMedia($request);

        $this->assertInstanceOf(\Carone\Media\Models\MediaResource::class, $result);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('External Image', $result->display_name);
        $this->assertEquals('external', $result->source);
        $this->assertEquals('https://example.com/image.jpg', $result->url);
    }
}
