<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Posts API Routes
Route::prefix('posts')->group(function () {
    // Public routes (no authentication required)
    Route::get('/', [PostController::class, 'index']); // List posts
    Route::get('/{id}', [PostController::class, 'show']); // Get post by ID
    Route::get('/slug/{slug}', [PostController::class, 'showBySlug']); // Get post by slug
    
    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PostController::class, 'store']); // Create post
        Route::put('/{id}', [PostController::class, 'update']); // Update post
        Route::delete('/{id}', [PostController::class, 'destroy']); // Delete post
    });
});
