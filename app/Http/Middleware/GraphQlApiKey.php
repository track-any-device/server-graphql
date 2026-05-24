<?php

namespace GraphQlApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use TrackAnyDevice\Core\Models\User;

class GraphQlApiKey
{
    /**
     * Handle an incoming request.
     *
     * Checks for a machine-to-machine API key + secret pair.
     * If present and valid, authenticates a system user so that
     * Lighthouse guards (@guard, @auth) are satisfied.
     *
     * If the headers are absent the request falls through untouched so that
     * Sanctum can still handle normal user sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerKey = $request->bearerToken();
        $apiKey    = $bearerKey ?? $request->header('X-Api-Key');
        $apiSecret = $request->header('X-Api-Secret');

        if ($apiKey !== null) {
            $expectedKey    = env('GRAPHQL_KEY');
            $expectedSecret = env('GRAPHQL_SECRET');

            if (
                $expectedKey
                && $expectedSecret
                && hash_equals($expectedKey, $apiKey)
                && hash_equals($expectedSecret, (string) $apiSecret)
            ) {
                // Bind a virtual system user so @auth / @guard pass.
                // No role attribute — avoids the Role enum cast on User.
                $system = new User(['name' => 'System', 'email' => 'system@internal']);
                $system->exists = false;

                // Make sanctum the active guard for this request, then
                // bind the virtual user so @guard(with:["sanctum"]) passes.
                Auth::shouldUse('sanctum');
                Auth::guard('sanctum')->setUser($system);
            }
        }

        return $next($request);
    }
}
