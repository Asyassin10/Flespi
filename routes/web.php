<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Devices
Route::resource('devices', DeviceController::class);
Route::post('devices/{device}/sync', [DeviceController::class, 'sync'])->name('devices.sync');
Route::get('devices/{device}/location', [DeviceController::class, 'location'])->name('devices.location');
Route::post('devices/{device}/assign-driver', [DeviceController::class, 'assignDriver'])->name('devices.assign-driver');
Route::post('devices/{device}/unassign-driver', [DeviceController::class, 'unassignDriver'])->name('devices.unassign-driver');

// Drivers
Route::resource('drivers', DriverController::class);
Route::post('drivers/{driver}/assign', [DriverController::class, 'assignToDevice'])->name('drivers.assign');
Route::post('driver-assignments/{assignment}/end', [DriverController::class, 'endAssignment'])->name('driver-assignments.end');

// Trips
Route::get('trips', [TripController::class, 'index'])->name('trips.index');
Route::get('trips/{trip}', [TripController::class, 'show'])->name('trips.show');
Route::get('trips/{trip}/route', [TripController::class, 'route'])->name('trips.route');
Route::post('trips/sync', [TripController::class, 'sync'])->name('trips.sync');

// Geofences
Route::resource('geofences', GeofenceController::class);
Route::post('geofences-sync', [GeofenceController::class, 'sync'])->name('geofences.sync');
Route::post('geofences-hit-test', [GeofenceController::class, 'hitTest'])->name('geofences.hit-test');
Route::post('geofences/{geofence}/assign-calculator', [GeofenceController::class, 'assignCalculator'])->name('geofences.assign-calculator');

// Setup
Route::get('setup', [DashboardController::class, 'setup'])->name('setup');
Route::post('setup/calculator', [DashboardController::class, 'createCalculator'])->name('setup.calculator');
Route::post('setup/sync', [DashboardController::class, 'syncDevices'])->name('setup.sync');
