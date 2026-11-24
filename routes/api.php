<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ReadingGoalController;
use App\Http\Controllers\Api\ReadingSessionController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserBookController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/books', [BookController::class, 'index']);
Route::get('/books/isbn/{isbn}', [BookController::class, 'lookupIsbn']);
Route::get('/books/{id}', [BookController::class, 'show']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Dashboard & Stats
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/stats', [StatsController::class, 'index']);

    // Master Books (Manual Creation & Update/Cover Upload)
    Route::post('/books', [BookController::class, 'store']);
    Route::post('/books/{id}', [BookController::class, 'update']);
    Route::put('/books/{id}', [BookController::class, 'update']);

    // User Books (Rak Buku)
    Route::get('/user-books', [UserBookController::class, 'index']);
    Route::post('/user-books', [UserBookController::class, 'store']);
    Route::get('/user-books/{id}', [UserBookController::class, 'show']);
    Route::put('/user-books/{id}', [UserBookController::class, 'update']);
    Route::delete('/user-books/{id}', [UserBookController::class, 'destroy']);

    // Collections (Rak Kustom)
    Route::get('/collections', [CollectionController::class, 'index']);
    Route::post('/collections', [CollectionController::class, 'store']);
    Route::get('/collections/{id}', [CollectionController::class, 'show']);
    Route::put('/collections/{id}', [CollectionController::class, 'update']);
    Route::delete('/collections/{id}', [CollectionController::class, 'destroy']);
    Route::post('/collections/{id}/items', [CollectionController::class, 'addItem']);
    Route::delete('/collections/{id}/items/{userBookId}', [CollectionController::class, 'removeItem']);

    // Reading Sessions (Timer & Log)
    Route::get('/sessions', [ReadingSessionController::class, 'index']);
    Route::post('/sessions', [ReadingSessionController::class, 'store']);

    // Reading Goals
    Route::get('/goals', [ReadingGoalController::class, 'index']);
    Route::post('/goals', [ReadingGoalController::class, 'storeOrUpdate']);

    // Notes & Highlights
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::delete('/notes/{id}', [NoteController::class, 'destroy']);

    // Badges & Gamification
    Route::get('/badges', [BadgeController::class, 'index']);
});
