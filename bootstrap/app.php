<?php

use GraphQlApp\Http\Middleware\GraphQlApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'graphql.key'    => GraphQlApiKey::class,
            'central.staff'  => \GraphQlApp\Http\Middleware\RequiresCentralStaff::class,
        ]);

        // Unauthenticated web visitors get redirected to /login which
        // bounces them to the SSO provider — not the local Fortify login.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
