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
     * cleanup is instead run at most once per day, piggy-backing on the
     * first incoming request of the day. The claim below is a single atomic
     * UPDATE, so concurrent requests can't both "win" and run it twice.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A broken or unreachable database must never turn every request
        // into a 500 just because of this side effect, so the whole check
        // (including the claim itself) is guarded.
        try {
            $claimed = Setting::where('id', Setting::instance()->id)
                ->where(function ($query) {
                    $query->whereNull('rides_cleanup_last_run_at')
                        ->orWhere('rides_cleanup_last_run_at', '<', now()->startOfDay());
                })
                ->update(['rides_cleanup_last_run_at' => now()]);

            if ($claimed) {
                app(RideDataRetentionCleaner::class)->run();
            }
        } catch (\Throwable $e) {
            Log::error('Ride data retention cleanup failed', ['exception' => $e]);
        }

        return $next($request);
    }
}
