<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Routes gated to 'siswa' bounce to the siswa login page; every other
        // role (guru/admin/superadmin) bounces to the staff login page.
        $loginRoute = in_array('siswa', $roles) ? 'login' : 'staff.login';

        if (!auth()->check()) {
            return redirect()->route($loginRoute);
        }

        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if (auth()->user()->isLocked()) {
            auth()->logout();
            return redirect()->route($loginRoute)
                ->withErrors(['email' => 'Akun Anda dikunci sementara. Coba lagi nanti.']);
        }

        if (!auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route($loginRoute)
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.']);
        }

        return $next($request);
    }
}
