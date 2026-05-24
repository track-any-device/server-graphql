<?php

namespace GraphQlApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TrackAnyDevice\Core\Enums\Role;

class RequiresCentralStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, [Role::Admin, Role::Supervisor, Role::Staff], true)) {
            abort(403, 'GraphQL Explorer is restricted to central staff.');
        }

        return $next($request);
    }
}
