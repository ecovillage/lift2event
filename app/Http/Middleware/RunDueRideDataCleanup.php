<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\RideDataRetentionCleaner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RunDueRideDataCleanup
{
    /**
     * There is no cron job available on shared hosting, so the retention
     * cleanup instead piggy-backs on incoming requests. Setting::instance()
     * caches, in rides_cleanup_next_due_at, the exact moment the
     * soonest-expiring event becomes eligible for deletion (kept in sync by
     * Event's saved/deleted hooks and RideDataRetentionCleaner itself), so
     * most requests can skip the cleanup with a single cheap comparison
     * instead of scanning the events table.
     *
     * The claim below is a single atomic UPDATE, so concurrent requests
     * can't both "win" and run it twice. It optimistically pushes the due
     * date a day out before running the cleaner; a successful run replaces
     * that with the real next-due date, while a failed one leaves it as a
     * one-day fallback so a broken cleanup doesn't retry on every request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A broken or unreachable database must never turn every request
        // into a 500 just because of this side effect, so the whole check
        // (including the claim itself) is guarded.
        try {
            $claimed = Setting::where('id', Setting::instance()->id)
                ->whereNotNull('rides_cleanup_next_due_at')
                ->where('rides_cleanup_next_due_at', '<=', now())
                ->update(['rides_cleanup_next_due_at' => now()->addDay()]);

            if ($claimed) {
                app(RideDataRetentionCleaner::class)->run();
            }
        } catch (\Throwable $e) {
            Log::error('Ride data retention cleanup failed', ['exception' => $e]);
        }

        return $next($request);
    }
}
