<?php

use Illuminate\Support\Facades\Route;
use Carone\Media\Http\Controllers\MediaController;

Route::prefix('api/media')->group(function () {
    Route::get('types', [MediaController::class, 'getMediaTypes']);
    Route::get('type/{type}', [MediaController::class, 'getMediaByType']);
    Route::get('search', [MediaController::class, 'searchMedia']);

    // Delete media
    Route::delete('{id}', [MediaController::class, 'deleteMedia'])
        ->where('id', '[0-9]+');

    // Bulk delete media
    Route::delete('bulk', [MediaController::class, 'bulkDeleteMedia']);
});

// File serving routes
Route::prefix('media')->group(function () {

    // Serve media files
    Route::get('{type}/{identifier}', [MediaController::class, 'getMedia'])
        ->where('type', 'image|video|audio|document')
        ->where('identifier', '[^/]+');

    // Serve thumbnails
    Route::get('{type}/thumbnails/{identifier}', [MediaController::class, 'getThumbnail'])
        ->where('type', 'image|video|audio|document')
        ->where('identifier', '[^/]+');
});
