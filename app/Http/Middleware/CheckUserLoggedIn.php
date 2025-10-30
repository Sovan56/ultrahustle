<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserLoggedIn
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('user_id')) {
            // If AJAX/JSON, don’t redirect—return 401 JSON.
            if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => 'Login required'], 401);
            }

            // Normal web request: redirect to home with a hint to open login
            return redirect()
                ->to(route('home', ['login' => 1]))   // adds ?login=1
                ->with('openModal', 'login');         // flash for blade checks
        }

        return $next($request);
    }
}
