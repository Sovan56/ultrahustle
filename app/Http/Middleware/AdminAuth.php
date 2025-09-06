<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('admin_id')) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('admin.login');
        }
        return $next($request);
    }
}
