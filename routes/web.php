<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use GraphQlApp\Http\Controllers\SsoCallbackController;

// Redirect unauthenticated visitors to the SSO login provider.
Route::get('/login', function () {
    return Socialite::driver('sso')->redirect();
})->name('login');

// SSO callback — exchanges auth code for session.
Route::get('/sso/callback', SsoCallbackController::class)->name('sso.callback');

// GraphQL Explorer — requires central staff SSO session.
Route::middleware(['auth', 'central.staff'])->get('/', fn () => view('explorer'))->name('explorer');

// Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');
