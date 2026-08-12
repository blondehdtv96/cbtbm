<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stamps users.last_seen_at for the "online now" stat on the monitoring
 * page. Writes directly via the query builder (no model events/timestamps)
 * and is throttled to once a minute per user so it stays cheap even on
 * high-frequency polling routes like the exam autosave endpoint.
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && (!$user->last_seen_at || $user->last_seen_at->lt(now()->subMinute()))) {
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
