<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['cors'])->group(function () {
    Route::post('/posts/capture', [PostController::class, 'capture']);
    Route::delete('/posts/unlike', [PostController::class, 'unlike']);
    Route::get('/posts/status', [PostController::class, 'status']);
});