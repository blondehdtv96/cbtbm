<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * Siswa-only routes (siswa/exam) bounce to the siswa login page; everything
     * else (superadmin/admin/guru) is staff, so it bounces to the staff login
     * page instead of exposing the siswa page as the only entry point.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('siswa*', 'exam*')) {
            return route('login');
        }

        return route('staff.login');
    }
}
