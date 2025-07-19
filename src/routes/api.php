<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvatarController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Enhanced gender detection endpoints (NEW!)
Route::get('/detect-gender/{name}/detailed', [AvatarController::class, 'detectGenderDetailed'])->name('gender.detect.detailed');
Route::get('/detect-gender/{name}/compare', [AvatarController::class, 'compareCountries'])->name('gender.compare');

// Simple gender detection endpoint (for testing/debugging)
Route::get('/detect-gender/{name}', [AvatarController::class, 'detectGender'])->name('gender.detect');

// New gender-specific routes (manual gender override)
Route::get('/avatar/{name}/{gender}.webp', [AvatarController::class, 'generate'])->name('avatar.generate.gender');
Route::get('/avatar/{name}/{gender}', [AvatarController::class, 'generate'])->name('avatar.generate.gender.no-ext');

// Auto-detection routes (automatic gender detection from name)
Route::get('/avatar/{name}.webp', [AvatarController::class, 'generate'])->name('avatar.generate');
Route::get('/avatar/{name}', [AvatarController::class, 'generate'])->name('avatar.generate.no-ext');

// Random avatar routes
Route::get('/avatar.webp', [AvatarController::class, 'generate']);
Route::get('/avatar', [AvatarController::class, 'generate']);
