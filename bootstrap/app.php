<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'checkUser'  => \App\Http\Middleware\CheckUserLoggedIn::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'touch.lastseen' => \App\Http\Middleware\TouchLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 419 - CSRF/session expired
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh and try again.'
                ], 419);
            }

            return redirect()
                ->route('session.expired')
                ->with('error', 'Your session expired. Please try again.');
        });

        // Handle 404 - Not Found
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The requested resource could not be found.'
                ], 404);
            }

            return redirect()->route('custom.404');
        });
    })
    ->create();
