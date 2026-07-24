<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['de', 'en', 'fr', 'zh'];

    /**
     * Prefer the authenticated user's saved language, then the locale the
     * frontend is currently rendering in (sent via header since public/guest
     * routes have no user to fall back to).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user('sanctum')?->preferred_language
            ?? $request->header('X-Locale');

        // Always set it explicitly, rather than only on a match, so a locale
        // from a previous request can't leak into this one on long-lived
        // processes (queue workers, Octane) or across requests within the
        // same test. Reset to app.fallback_locale, not app.locale: the latter
        // is overwritten by every app()->setLocale() call, so past requests
        // would otherwise "reset" to whatever locale they last set.
        app()->setLocale(
            is_string($locale) && in_array($locale, self::SUPPORTED, true) ? $locale : config('app.fallback_locale')
        );

        return $next($request);
    }
}
