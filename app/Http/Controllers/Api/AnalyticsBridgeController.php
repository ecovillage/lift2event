<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AnalyticsBridgeController extends Controller
{
    /**
     * The request-analytics package's own dashboard is a plain Blade page,
     * authenticated via the session-based "web" guard. The SPA only ever
     * carries a Sanctum bearer token, which a real browser navigation to
     * that page can't send. This endpoint is called via an authenticated
     * XHR request (bearer token attached) and, in exchange, starts a
     * session for the "web" guard so the subsequent full-page navigation
     * to /analytics is authenticated via the session cookie instead.
     */
    public function login(Request $request): Response
    {
        Auth::guard('web')->login($request->user());
        $request->session()->regenerate();

        return response()->noContent();
    }
}
