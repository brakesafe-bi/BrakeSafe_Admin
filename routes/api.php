<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrakeEventController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API endpoints for ESP32 (no authentication required)
Route::post('/brake-events', [BrakeEventController::class, 'store']);

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/brake-events', [BrakeEventController::class, 'index']);
});
