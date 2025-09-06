<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class HandleExpiredSession
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            if ($e instanceof TokenMismatchException) {
                return redirect()->route('session.expired');
            }
            throw $e; // let other errors pass through
        }
    }
}
