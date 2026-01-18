<?php

use Illuminate\Support\Facades\Route;
use Carone\Media\Http\Controllers\MediaController;
use Carone\Media\Http\Controllers\MediaAdminController;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
| Admin interface for managing media (requires management_middleware).
*/
if (config('media.admin.enabled', true)) {
    $adminPrefix = config('media.admin.route_prefix', 'admin/media');

    Route::prefix($adminPrefix)
        ->middleware(array_merge(['web'], config('media.management_middleware', ['auth'])))
        ->group(function () {
            Route::get('/', [MediaAdminController::class, 'index'])->name('media.admin');
        });

    // Admin API Routes
    Route::prefix('api/' . $adminPrefix)
        ->middleware(array_merge(['api'], config('media.management_middleware', ['auth'])))
        ->group(function () {
            Route::get('stats', [MediaAdminController::class, 'getStats']);
            Route::get('media', [MediaAdminController::class, 'getAllMedia']);
            Route::get('tags', [MediaAdminController::class, 'getAllTags']);
            Route::put('media/{id}', [MediaAdminController::class, 'updateMedia']);
            Route::put('media/{id}/tags', [MediaAdminController::class, 'updateMediaTags']);
        });
}

/*
|--------------------------------------------------------------------------
| Public Media Routes
|--------------------------------------------------------------------------
| These routes are publicly accessible without authentication.
| Includes: media browsing, search, and file serving.
*/
Route::prefix('api/media')->middleware('api')->group(function () {
    Route::get('types', [MediaController::class, 'getMediaTypes']);
    Route::get('tags', [MediaController::class, 'getTags']);
    Route::get('type/{type}', [MediaController::class, 'getMediaByType']);
    Route::get('search', [MediaController::class, 'searchMedia']);
    Route::get('{id}', [MediaController::class, 'getMediaById'])
        ->where('id', '[0-9]+');
});

/*
|--------------------------------------------------------------------------
| Protected Management Routes
|--------------------------------------------------------------------------
| These routes require authentication (configurable via media.management_middleware).
| Includes: upload, delete operations.
*/
Route::prefix('api/media')
    ->middleware(array_merge(['api'], config('media.management_middleware', ['auth'])))
    ->group(function () {
        Route::post('upload', [MediaController::class, 'uploadMedia']);
        Route::delete('{id}', [MediaController::class, 'deleteMedia'])
            ->where('id', '[0-9]+');
        Route::delete('bulk', [MediaController::class, 'bulkDeleteMedia']);
    });

/*
|--------------------------------------------------------------------------
| Public File Serving Routes
|--------------------------------------------------------------------------
| Serve media files and thumbnails - publicly accessible.
*/
Route::prefix('media')->middleware('api')->group(function () {
    if (config('media.thumbnails.enabled', false)) {
        Route::get('thumbnails/{id}', [MediaController::class, 'getThumbnail'])
            ->where('id', '[0-9]+');
    }
    Route::get('{path}', [MediaController::class, 'getMedia'])
        ->where('path', '.*');
});
