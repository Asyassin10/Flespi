<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Flespi Webhook Endpoint
Route::post('flespi/webhook', [WebhookController::class, 'handle'])->name('api.flespi.webhook');

// Device API endpoints
Route::prefix('devices')->group(function () {
    Route::get('{device}/telemetry', [DeviceController::class, 'telemetry'])->name('api.devices.telemetry');
    Route::get('{device}/messages', [DeviceController::class, 'messages'])->name('api.devices.messages');
});

// Live data endpoints for real-time updates
Route::get('devices-positions', [DeviceController::class, 'allPositions'])->name('api.devices.positions');
