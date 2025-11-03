<?php

namespace Carone\Media\Tests\Facades;

use Carone\Media\Facades\Media;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class MediaTest extends TestCase
{
    /** @test */
    public function it_can_store_media_via_facade(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $result = Media::store([
            'file' => $file,
            'type' => 'image',
            'name' => 'Test Image',
            'description' => 'A test image'
        ]);

        $this->assertInstanceOf(\Carone\Media\Models\MediaResource::class, $result);
        $this->assertEquals('image', $result->type);
        $this->assertEquals('Test Image', $result->display_name);
    }

    /** @test */
    public function it_can_get_media_by_id_via_facade(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $stored = Media::store([
            'file' => $file,
            'type' => 'image',
            'name' => 'Test Image'
        ]);

        $retrieved = Media::getById($stored->id);

        $this->assertEquals($stored->id, $retrieved->id);
        $this->assertEquals($stored->display_name, $retrieved->display_name);
    }

    /** @test */
    public function it_can_delete_media_via_facade(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $stored = Media::store([
            'file' => $file,
            'type' => 'image',
            'name' => 'Test Image'
        ]);

        $deleted = Media::delete($stored->id);
        $this->assertTrue($deleted);
    }

    /** @test */
    public function it_can_get_enabled_types(): void
    {
        $types = Media::getEnabledTypes();

        $this->assertIsArray($types);
        $this->assertContains('image', $types);
    }
}
