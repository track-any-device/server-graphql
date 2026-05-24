<?php

namespace GraphQlApp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use TrackAnyDevice\Core\Models\User;

/**
 * SSO callback for the GraphQL Explorer (singleton app, not tenant-scoped).
 *
 * The Socialite 'sso' driver is already configured via SsoClientServiceProvider
 * using APP_SURFACE=graphql to load the graphql oauth_clients row from the DB.
 * We simply call ->stateless()->user() and establish a web session.
 */
class SsoCallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('sso');
            $socialiteUser = $driver->stateless()->user();
        } catch (\Throwable $e) {
            // Do NOT redirect to /login — that creates a redirect loop.
            abort(401, 'Sign-in failed or the link has expired. Close this tab and try again.');
        }

        /** @var User|null $user */
        $user = User::find($socialiteUser->getId());

        if (! $user) {
            abort(401, 'Your account was not found. Contact an administrator.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
