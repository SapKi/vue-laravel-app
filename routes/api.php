<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me',      [AuthController::class, 'me'])->middleware('auth:sanctum');

// Review Queue (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::patch('/items/{item}/review', [ItemController::class, 'review']);
    Route::patch('/items/{item}/reopen', [ItemController::class, 'reopen']);
    Route::patch('/items/{item}/note',          [ItemController::class, 'saveNote']);
    Route::delete('/items/{item}/notes/{note}', [ItemController::class, 'destroyNote']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
});
