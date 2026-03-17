<?php

use App\Http\Controllers\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello from Laravel API! The stack is working.',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Review Queue
Route::get('/items', [ItemController::class, 'index']);
Route::post('/items', [ItemController::class, 'store']);
Route::get('/items/{item}', [ItemController::class, 'show']);
Route::patch('/items/{item}/review', [ItemController::class, 'review']);
Route::patch('/items/{item}/note',          [ItemController::class, 'saveNote']);
Route::delete('/items/{item}/notes/{note}', [ItemController::class, 'destroyNote']);
Route::delete('/items/{item}', [ItemController::class, 'destroy']);

// Protected routes (requires Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
