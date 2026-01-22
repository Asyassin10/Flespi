<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Flespi Webhook Endpoint
Route::post('flespi/webhook', [WebhookController::class, 'handle'])->name('api.flespi.webhook');

// Device API endpoints
Route::prefix('devices')->group(function () {
    Route::get('{device}/telemetry', [App\Http\Controllers\DeviceController::class, 'telemetry']);
    Route::get('{device}/messages', [App\Http\Controllers\DeviceController::class, 'messages']);
});
