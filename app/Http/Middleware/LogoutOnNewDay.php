<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * Ends any logged-in session the moment the calendar day changes, even if
 * the session or remember-me cookie would otherwise still be valid — users
 * must log in fresh each day. Only the login session is torn down; any exam
 * answers already saved to the database are untouched.
 */
class LogoutOnNewDay
{
    const COOKIE = 'login_date';

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && $request->cookie(self::COOKIE) !== now()->toDateString()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Cookie::queue(Cookie::forget(self::COOKIE));

            return redirect()->route('login')->withErrors([
                'nisn' => 'Sesi Anda berakhir karena pergantian hari. Silakan login kembali.',
            ]);
        }

        return $next($request);
    }
}
