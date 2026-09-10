<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\UserBookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/user', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
	Route::get('/user', [AuthController::class, 'me']);
	Route::post('/logout', [AuthController::class, 'logout']);
	Route::get('/books/details/{Key}', [BookController::class, 'details']);
	Route::apiResource('books', BookController::class);
	Route::post('/user-books', [UserBookController::class, 'store']);
});
