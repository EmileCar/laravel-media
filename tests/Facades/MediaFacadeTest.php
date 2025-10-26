<?php

namespace Carone\Media\Tests\Facades;

use Carone\Media\Facades\Media;
use Carone\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_access_media_through_facade()
    {
        // Test that the facade resolves correctly
        $this->assertInstanceOf(\Carone\Media\MediaManager::class, app('carone.media'));
    }

    /** @test */
    public function it_can_get_enabled_types_through_facade()
    {
        $types = Media::getEnabledTypes();
        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    /** @test */
    public function facade_provides_clean_api()
    {
        // Verify all expected methods exist on the facade
        $reflection = new \ReflectionClass(\Carone\Media\MediaManager::class);
        
        $expectedMethods = [
            'store',
            'getById', 
            'getByType',
            'search',
            'serve',
            'thumbnail',
            'delete',
            'deleteMultiple',
            'deleteByType',
            'cleanupOrphanedFiles',
            'getEnabledTypes'
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Method {$method} should exist on MediaManager"
            );
        }
    }

    /** @test */
    public function it_provides_single_public_entry_point()
    {
        // This test documents that the facade is the ONLY public API
        $this->assertTrue(
            class_exists(\Carone\Media\Facades\Media::class),
            'Media facade should be the only public entry point'
        );
        
        // Verify the facade accessor points to our manager
        $this->assertEquals('carone.media', \Carone\Media\Facades\Media::getFacadeAccessor());
    }
}