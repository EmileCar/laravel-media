<?php

use Illuminate\Support\Facades\Route;
use Carone\Media\Http\Controllers\MediaController;

// API routes for media management
Route::prefix('api/media')->group(function () {
    
    // Get media types
    Route::get('types', [MediaController::class, 'getMediaTypes']);
    
    // Get media by type with pagination
    Route::get('type/{type}', [MediaController::class, 'getMediaByType']);
    
    // Search media
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
    Route::get('{type}/{identifier}', [MediaController::class, 'getMedia'])
        ->where('type', 'image|video|audio|document')
        ->where('identifier', '[^/]+');
    
    // Serve thumbnails
    Route::get('{type}/thumbnails/{identifier}', [MediaController::class, 'getThumbnail'])
        ->where('type', 'image|video|audio|document')
        ->where('identifier', '[^/]+');
});