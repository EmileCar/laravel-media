<?php

namespace Carone\Media\Tests\Services;

use Carone\Media\Facades\Media;
use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\MediaStreamer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamMediaTest extends TestCase
{
    private const CONTENT = '0123456789abcdefghij'; // 20 bytes

    protected function defineRoutes($router): void
    {
        // The application's own (authenticated in real life) endpoints
        Route::get('/app/files/{id}', fn (string $id) => Media::stream((int) $id));
        Route::get('/app/files/{id}/download', fn (string $id) => Media::download((int) $id));
        Route::get('/app/files/{id}/thumbnail', fn (string $id) => Media::streamThumbnail((int) $id));
    }

    private function createLocalMedia(array $attributes = []): MediaResource
    {
        Storage::disk('local')->put('media/videos/holiday.mp4', self::CONTENT);

        return MediaResource::factory()->create(array_merge([
            'type' => 'video',
            'source' => 'local',
            'path' => 'videos/holiday.mp4',
            'url' => null,
            'meta' => ['original_name' => 'Holiday été.mp4', 'mime_type' => 'video/mp4'],
            'thumbnail_path' => null,
            'thumbnail_url' => null,
        ], $attributes));
    }

    private function body(TestResponse $response): string
    {
        $base = $response->baseResponse;
        if ($base instanceof StreamedResponse) {
            return $response->streamedContent();
        }

        ob_start();
        $base->sendContent();
        return (string) ob_get_clean();
    }

    public function test_stream_serves_a_local_file_inline_with_private_headers(): void
    {
        $media = $this->createLocalMedia();

        $response = $this->get("/app/files/{$media->id}");

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame('video/mp4', $response->headers->get('Content-Type'));
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("filename*=utf-8''Holiday%20%C3%A9t%C3%A9.mp4", $response->headers->get('Content-Disposition'));
        $this->assertSame(self::CONTENT, $this->body($response));
    }

    public function test_stream_supports_range_requests_on_local_disks(): void
    {
        $media = $this->createLocalMedia();

        $response = $this->get("/app/files/{$media->id}", ['Range' => 'bytes=5-9']);

        $response->assertStatus(206);
        $this->assertSame('bytes 5-9/20', $response->headers->get('Content-Range'));
        $this->assertSame('5', $response->headers->get('Content-Length'));
        $this->assertSame('56789', $this->body($response));
    }

    public function test_download_sends_the_file_as_attachment(): void
    {
        $media = $this->createLocalMedia();

        $response = $this->get("/app/files/{$media->id}/download");

        $response->assertOk();
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('filename="Holiday ete.mp4"', $response->headers->get('Content-Disposition'));
    }

    public function test_download_accepts_a_custom_filename(): void
    {
        $media = $this->createLocalMedia();

        $response = Media::download($media->id, 'renamed.mp4');

        $this->assertStringContainsString('filename=renamed.mp4', $response->headers->get('Content-Disposition'));
    }

    public function test_stream_thumbnail_serves_the_stored_thumbnail(): void
    {
        $media = $this->createLocalMedia(['thumbnail_path' => 'videos/holiday_thumb.jpg']);
        Storage::disk('local')->put('media/thumbnails/videos/holiday_thumb.jpg', 'poster');

        $response = $this->get("/app/files/{$media->id}/thumbnail");

        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertSame('poster', $this->body($response));
    }

    public function test_stream_thumbnail_returns_404_without_local_thumbnail(): void
    {
        $media = $this->createLocalMedia();

        $this->get("/app/files/{$media->id}/thumbnail")->assertNotFound();
    }

    public function test_stream_returns_404_when_the_file_is_missing(): void
    {
        $media = $this->createLocalMedia();
        Storage::disk('local')->delete('media/videos/holiday.mp4');

        $this->get("/app/files/{$media->id}")->assertNotFound();
    }

    public function test_stream_returns_404_for_external_media(): void
    {
        $media = MediaResource::factory()->create([
            'source' => 'external',
            'path' => null,
            'url' => 'https://example.com/video.mp4',
        ]);

        $this->get("/app/files/{$media->id}")->assertNotFound();
    }

    public function test_stream_throws_for_unknown_media(): void
    {
        $this->expectException(ModelNotFoundException::class);
        Media::stream(999);
    }

    public function test_streamed_response_serves_the_full_file(): void
    {
        Storage::disk('local')->put('media/file.bin', self::CONTENT);

        $response = MediaStreamer::streamedResponse('local', 'media/file.bin', 'application/octet-stream', Request::create('/'));

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('20', $response->headers->get('Content-Length'));
        $this->assertSame(self::CONTENT, $this->sendStreamed($response));
    }

    public function test_streamed_response_serves_a_range(): void
    {
        Storage::disk('local')->put('media/file.bin', self::CONTENT);
        $request = Request::create('/', 'GET', server: ['HTTP_RANGE' => 'bytes=10-']);

        $response = MediaStreamer::streamedResponse('local', 'media/file.bin', 'application/octet-stream', $request);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes 10-19/20', $response->headers->get('Content-Range'));
        $this->assertSame('10', $response->headers->get('Content-Length'));
        $this->assertSame('abcdefghij', $this->sendStreamed($response));
    }

    public function test_streamed_response_serves_a_suffix_range(): void
    {
        Storage::disk('local')->put('media/file.bin', self::CONTENT);
        $request = Request::create('/', 'GET', server: ['HTTP_RANGE' => 'bytes=-3']);

        $response = MediaStreamer::streamedResponse('local', 'media/file.bin', 'application/octet-stream', $request);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('hij', $this->sendStreamed($response));
    }

    public function test_streamed_response_rejects_unsatisfiable_ranges(): void
    {
        Storage::disk('local')->put('media/file.bin', self::CONTENT);
        $request = Request::create('/', 'GET', server: ['HTTP_RANGE' => 'bytes=50-60']);

        $response = MediaStreamer::streamedResponse('local', 'media/file.bin', 'application/octet-stream', $request);

        $this->assertSame(416, $response->getStatusCode());
        $this->assertSame('bytes */20', $response->headers->get('Content-Range'));
    }

    public function test_parse_range(): void
    {
        $this->assertNull(MediaStreamer::parseRange(null, 100));
        $this->assertNull(MediaStreamer::parseRange('bytes=0-1,5-6', 100));
        $this->assertNull(MediaStreamer::parseRange('items=0-1', 100));
        $this->assertSame([0, 99], MediaStreamer::parseRange('bytes=0-', 100));
        $this->assertSame([10, 20], MediaStreamer::parseRange('bytes=10-20', 100));
        $this->assertSame([10, 99], MediaStreamer::parseRange('bytes=10-500', 100));
        $this->assertSame([90, 99], MediaStreamer::parseRange('bytes=-10', 100));
        $this->assertSame([0, 99], MediaStreamer::parseRange('bytes=-500', 100));
        $this->assertFalse(MediaStreamer::parseRange('bytes=100-', 100));
        $this->assertFalse(MediaStreamer::parseRange('bytes=20-10', 100));
        $this->assertFalse(MediaStreamer::parseRange('bytes=-0', 100));
    }

    private function sendStreamed(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();
        return (string) ob_get_clean();
    }
}
