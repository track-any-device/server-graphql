<?php

namespace GraphQlApp\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Must run in register() — before any service provider boots —
        // so the flag is set before FortifyServiceProvider::boot() fires.
        Fortify::ignoreRoutes();
    }

    public function boot(): void {}
}
