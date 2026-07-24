<?php

use Illuminate\Support\Facades\Route;

// Named so Laravel's default "auth" middleware can resolve route('login') when
// redirecting an unauthenticated web-session request (e.g. to /analytics).
// Serves the same SPA shell; Vue Router then shows the actual login page.
Route::get('/login', function () {
    return view('app');
})->name('login');

$spa = fn () => view('app');

// request-analytics only supports a blocklist, and this is an SPA where any
// path can hit the catch-all below (assets, bots, unmatched routes, ...), so
// tracking is opt-in instead: only event pages and the backend/admin area
// get the capture middleware attached.
Route::middleware('request-analytics.web')->group(function () use ($spa) {
    Route::get('/events/{slug}/{any?}', $spa)->where('any', '.*');
    // Legacy prefix, kept so previously shared/emailed /e/... links (old
    // adjective-noun slugs) stay reachable.
    Route::get('/e/{slug}/{any?}', $spa)->where('any', '.*');
    Route::get('/backend/{any?}', $spa)->where('any', '.*');
});

// Catch-all: serve the Vue SPA for every other non-API route (untracked)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
