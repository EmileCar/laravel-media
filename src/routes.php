<?php

use Illuminate\Support\Facades\Route;
use Carone\Media\Http\Controllers\MediaController;

Route::prefix('api/media')->group(function () {
    Route::get('types', [MediaController::class, 'getMediaTypes']);
    Route::get('type/{type}', [MediaController::class, 'getMediaByType']);
    Route::get('search', [MediaController::class, 'searchMedia']);

    // Upload media
    Route::post('upload', [MediaController::class, 'uploadMedia']);

    // Get media by ID
    Route::get('{id}', [MediaController::class, 'getMediaById'])
        ->where('id', '[0-9]+');

    // Delete media
    Route::delete('{id}', [MediaController::class, 'deleteMedia'])
        ->where('id', '[0-9]+');

    // Bulk delete media
    Route::delete('bulk', [MediaController::class, 'bulkDeleteMedia']);
});

// File serving routes
Route::prefix('media')->group(function () {

    // Serve media files
    Route::get('{id}', [MediaController::class, 'getMedia'])
        ->where('id', '[0-9]+');

    // Serve thumbnails
    if (config('media.thumbnails.enabled', false)) {
        Route::get('thumbnails/{id}', [MediaController::class, 'getThumbnail'])
            ->where('id', '[0-9]+');
    }
});
