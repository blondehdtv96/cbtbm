<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Siswa login page (NISN + password).
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nisn' => 'required|string',
            'password' => 'required',
        ]);

        $siswa = Siswa::where('nisn', $request->nisn)->first();

        if (!$siswa) {
            return back()->withErrors([
                'nisn' => 'NISN tidak ditemukan dalam sistem.',
            ])->withInput($request->only('nisn'));
        }

        $user = $siswa->user;

        if (!$user) {
            return back()->withErrors([
                'nisn' => 'Akun untuk NISN ini tidak ditemukan.',
            ])->withInput($request->only('nisn'));
        }

        if ($user->isLocked()) {
            $minutes = $user->locked_until->diffInMinutes(now());
            return back()->withErrors([
                'nisn' => "Akun dikunci. Coba lagi dalam {$minutes} menit.",
            ])->withInput($request->only('nisn'));
        }

        if (Hash::check($request->password, $user->password)) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'nisn' => 'Akun Anda tidak aktif. Hubungi administrator.',
                ])->withInput($request->only('nisn'));
            }

            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            $user->update([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_login' => now(),
            ]);

            ActivityLog::log('login', 'auth', 'Siswa login via NISN');

            return $this->redirectByRole($user);
        }

        $this->handleFailedLogin($user);

        return back()->withErrors([
            'nisn' => 'NISN atau password salah.',
        ])->withInput($request->only('nisn'));
    }

    /**
     * Staff (Guru/Admin/Superadmin) login page — email + password.
     * Not linked from the siswa page; only reachable via direct URL.
     */
    public function showStaffLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.staff-login');
    }

    public function staffLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->isLocked()) {
            $minutes = $user->locked_until->diffInMinutes(now());
            return back()->withErrors([
                'email' => "Akun dikunci. Coba lagi dalam {$minutes} menit.",
            ])->withInput($request->only('email'));
        }

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // This page is staff-only — a siswa account with valid credentials
            // still doesn't belong here. Not treated as a failed attempt since
            // the credentials themselves were correct.
            if (!in_array($user->role, ['guru', 'admin', 'superadmin'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Halaman ini khusus untuk Guru dan Admin.',
                ])->withInput($request->only('email'));
            }

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda tidak aktif. Hubungi administrator.',
                ]);
            }

            $user->update([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_login' => now(),
            ]);

            ActivityLog::log('login', 'auth', 'Staff login via email');

            return $this->redirectByRole($user);
        }

        if ($user) {
            $this->handleFailedLogin($user);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle failed login attempt - increment counter and lock if needed
     */
    protected function handleFailedLogin(User $user)
    {
        $maxAttempts = (int) Setting::getValue('max_login_attempts', 3);
        $lockDuration = (int) Setting::getValue('lock_duration_minutes', 30);

        $user->increment('login_attempts');

        if ($user->login_attempts >= $maxAttempts) {
            $user->update([
                'locked_until' => now()->addMinutes($lockDuration),
            ]);
        }
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', 'auth', 'User logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectByRole($user)
    {
        return match ($user->role) {
            'superadmin' => redirect()->route('superadmin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'siswa' => redirect()->route('siswa.dashboard'),
            default => redirect('/'),
        };
    }
}
