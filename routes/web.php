<?php

use Illuminate\Support\Facades\Route;

// Catch-all: serve the Vue SPA for every non-API route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
