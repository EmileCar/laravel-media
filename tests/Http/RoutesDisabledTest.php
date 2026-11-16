<?php

namespace Carone\Media\Tests\Http;

use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class RoutesDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('media.routes.enabled', false);
        $app['config']->set('media.thumbnails.enabled', true);
    }

    public function test_no_package_routes_are_registered(): void
    {
        Storage::disk('local')->put('media/photos/private.jpg', 'secret');
        $media = MediaResource::factory()->create([
            'source' => 'local',
            'path' => 'photos/private.jpg',
            'thumbnail_path' => 'photos/private_thumb.jpg',
        ]);

        $this->get('/media/photos/private.jpg')->assertNotFound();
        $this->get("/media/thumbnails/{$media->id}")->assertNotFound();
        $this->getJson("/api/media/{$media->id}")->assertNotFound();
        $this->getJson('/api/media/search?q=x')->assertNotFound();
        $this->postJson('/api/media/upload')->assertNotFound();
        $this->get('/admin/media')->assertNotFound();
    }
}
