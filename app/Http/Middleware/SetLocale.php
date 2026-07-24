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

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
