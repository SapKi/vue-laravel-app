<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello from Laravel API! The stack is working.',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes (requires Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
