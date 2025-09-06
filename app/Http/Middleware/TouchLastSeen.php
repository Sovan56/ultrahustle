<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TouchLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only for logged-in users
        if (Auth::check()) {
            $user = Auth::user();

            // write at most once per 60s to avoid thrashing
            $now = now();
            $last = $user->last_seen_at;
            if (!$last || $last->lt($now->subSeconds(60))) {
                // Avoid double updates if the controller already touched it
                $user->forceFill(['last_seen_at' => $now])->saveQuietly();
            }
        }

        return $response;
    }
}
