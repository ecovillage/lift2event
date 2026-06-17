<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicRideController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('settings', [SettingController::class, 'show']);
Route::get('geocode/search', [GeocodingController::class, 'search']);

// Public event / ride pages (no auth)
Route::prefix('e')->group(function () {
    Route::get('{slug}', [PublicEventController::class, 'show']);
    Route::post('{slug}/rides', [PublicRideController::class, 'store']);
    Route::put('{slug}/rides/{ride}', [PublicRideController::class, 'update']);
    Route::delete('{slug}/rides/{ride}', [PublicRideController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user/profile', [UserController::class, 'updateProfile']);

    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::put('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);

    // Admin-only
    Route::get('users', [UserController::class, 'index']);
    Route::put('users/{user}/approve', [UserController::class, 'toggleApprove']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::put('settings', [SettingController::class, 'update']);
});
