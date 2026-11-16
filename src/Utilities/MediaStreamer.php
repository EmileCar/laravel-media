<?php

namespace Carone\Media\Utilities;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds HTTP responses that stream a stored file from any filesystem disk,
 * with HTTP Range support (206 Partial Content) so browsers can seek in video/audio.
 */
class MediaStreamer
{
    public const INLINE = 'inline';
    public const ATTACHMENT = 'attachment';

    private const CHUNK_SIZE = 1024 * 1024;

    /**
     * Stream a file. Local disks get a BinaryFileResponse (Range is handled by Symfony
     * when the response is prepared); other disks get a StreamedResponse with Range support.
     */
    public static function response(
        string $disk,
        string $path,
        string $contentType,
        ?Request $request = null,
        string $disposition = self::INLINE,
        ?string $filename = null,
        ?int $cacheMinutes = null,
    ): Response {
        $filesystem = Storage::disk($disk);

        if (!$filesystem->exists($path)) {
            abort(404, 'Media file not found');
        }

        if ($filesystem instanceof FilesystemAdapter && $filesystem->getAdapter() instanceof LocalFilesystemAdapter) {
            return self::binaryFileResponse($filesystem->path($path), $contentType, $disposition, $filename ?? basename($path), $cacheMinutes);
        }

        return self::streamedResponse($disk, $path, $contentType, $request, $disposition, $filename, $cacheMinutes);
    }

    /**
     * Stream a file through the filesystem's read stream (works for any disk driver)
     */
    public static function streamedResponse(
        string $disk,
        string $path,
        string $contentType,
        ?Request $request = null,
        string $disposition = self::INLINE,
        ?string $filename = null,
        ?int $cacheMinutes = null,
    ): Response {
        $filesystem = Storage::disk($disk);
        if (!$filesystem->exists($path)) {
            abort(404, 'Media file not found');
        }

        $size = (int) $filesystem->size($path);
        $headers = self::baseHeaders($contentType, $disposition, $filename ?? basename($path), $cacheMinutes);

        $range = self::parseRange($request?->headers->get('Range'), $size);

        if ($range === false) {
            return new Response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, array_merge($headers, [
                'Content-Range' => "bytes */{$size}",
            ]));
        }

        [$start, $end] = $range ?? [0, max($size - 1, 0)];
        $length = $size === 0 ? 0 : $end - $start + 1;

        $headers['Content-Length'] = (string) $length;
        if ($range !== null) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $status = $range !== null ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_OK;

        return new StreamedResponse(function () use ($filesystem, $path, $start, $length) {
            if ($length === 0) {
                return;
            }

            $stream = $filesystem->readStream($path);
            if (!is_resource($stream)) {
                return;
            }

            try {
                self::skip($stream, $start);

                $remaining = $length;
                while ($remaining > 0 && !feof($stream)) {
                    $chunk = fread($stream, min(self::CHUNK_SIZE, $remaining));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    flush();
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($stream);
            }
        }, $status, $headers);
    }

    /**
     * Parse a single "bytes=" range.
     *
     * @return array{0:int,1:int}|null|false [start, end] (inclusive), null when the
     *         full file must be served, false when the range is not satisfiable
     */
    public static function parseRange(?string $header, int $size): array|null|false
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($header), $matches)) {
            // Multiple or malformed ranges: ignore and serve the full file (RFC 9110)
            return null;
        }

        [, $startRaw, $endRaw] = $matches;

        if ($startRaw === '' && $endRaw === '') {
            return null;
        }

        if ($size === 0) {
            return false;
        }

        if ($startRaw === '') {
            // Suffix range: the last N bytes
            $suffix = (int) $endRaw;
            if ($suffix === 0) {
                return false;
            }
            return [max($size - $suffix, 0), $size - 1];
        }

        $start = (int) $startRaw;
        $end = $endRaw === '' ? $size - 1 : min((int) $endRaw, $size - 1);

        if ($start >= $size || $start > $end) {
            return false;
        }

        return [$start, $end];
    }

    /**
     * Build a safe Content-Disposition header value
     */
    public static function contentDisposition(string $disposition, string $filename): string
    {
        $filename = str_replace(['/', '\\'], '_', $filename);
        $fallback = preg_replace('/[^\x20-\x7e]|[%"]/', '_', Str::ascii($filename)) ?: 'file';

        return HeaderUtils::makeDisposition($disposition, $filename, $fallback);
    }

    private static function binaryFileResponse(string $physicalPath, string $contentType, string $disposition, string $filename, ?int $cacheMinutes): BinaryFileResponse
    {
        $headers = self::baseHeaders($contentType, $disposition, $filename, $cacheMinutes);

        return new BinaryFileResponse($physicalPath, 200, $headers, false, null, false, true);
    }

    private static function baseHeaders(string $contentType, string $disposition, string $filename, ?int $cacheMinutes): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => self::contentDisposition($disposition, $filename),
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => $cacheMinutes ? 'private, max-age=' . ($cacheMinutes * 60) : 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    /**
     * Move the stream forward to the given offset
     *
     * @param resource $stream
     */
    private static function skip($stream, int $offset): void
    {
        if ($offset <= 0) {
            return;
        }

        $meta = stream_get_meta_data($stream);
        if ($meta['seekable'] && fseek($stream, $offset) === 0) {
            return;
        }

        $remaining = $offset;
        while ($remaining > 0 && !feof($stream)) {
            $skipped = fread($stream, min(self::CHUNK_SIZE, $remaining));
            if ($skipped === false || $skipped === '') {
                break;
            }
            $remaining -= strlen($skipped);
        }
    }
}
