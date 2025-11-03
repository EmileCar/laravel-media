<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Processing\MediaRequestBuilder;
use Carone\Media\Services\MediaService;
use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Illuminate\Http\UploadedFile;

class MediaServiceTest extends TestCase
{
    protected MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MediaService::class);
    }

    /** @test */
    public function it_can_store_local_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $request = MediaRequestBuilder::forLocalFile($file)
            ->type(MediaType::IMAGE)
            ->name('Test Image')
            ->build();

        $result = $this->service->storeLocalFile($request);

        $this->assertInstanceOf(\Carone\Media\Models\MediaResource::class, $result);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('Test Image', $result->display_name);
    }

    /** @test */
    public function it_can_get_media_by_id(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $request = MediaRequestBuilder::forLocalFile($file)
            ->type(MediaType::IMAGE)
            ->name('Test Image')
            ->build();

        $stored = $this->service->storeLocalFile($request);
        $retrieved = $this->service->getById($stored->id);

        $this->assertEquals($stored->id, $retrieved->id);
        $this->assertEquals($stored->display_name, $retrieved->display_name);
    }

    /** @test */
    public function it_can_delete_media(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $request = MediaRequestBuilder::forLocalFile($file)
            ->type(MediaType::IMAGE)
            ->name('Test Image')
            ->build();

        $stored = $this->service->storeLocalFile($request);
        $deleted = $this->service->delete($stored->id);

        $this->assertTrue($deleted);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->getById($stored->id);
    }
}
