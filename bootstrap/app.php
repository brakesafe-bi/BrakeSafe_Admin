<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureAdminRole::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['brakesafe_code', 'verification_code']);
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() === 419 && $request->isMethod('POST') && $request->is('logout')) {
                return redirect('/')->withErrors([
                    'logout' => 'Your logout request expired and did not sign you out. Please try again.',
                ]);
            }
        });
    })->create();
