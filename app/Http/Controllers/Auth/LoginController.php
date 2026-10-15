<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PasswordVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    public function __construct(protected PasswordVerifier $passwords)
    {
    }

    /**
     * Siswa login page (NIS + password).
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
        // NIS adalah identitas login siswa: kolomnya wajib dan unik untuk
        // setiap siswa, sedangkan NISN boleh kosong (nullable) sehingga tidak
        // semua siswa punya nilai yang bisa dipakai masuk.
        $request->validate([
            'nis' => 'required|string',
            'password' => 'required',
        ]);

        $siswa = Siswa::where('nis', $request->input('nis'))->first();

        if (!$siswa) {
            return back()->withErrors([
                'nis' => 'NIS tidak ditemukan dalam sistem.',
            ])->withInput($request->only('nis'));
        }

        $user = $siswa->user;

        if (!$user) {
            return back()->withErrors([
                'nis' => 'Akun untuk NIS ini tidak ditemukan.',
            ])->withInput($request->only('nis'));
        }

        $result = $this->passwords->check($user, $request->input('password'));

        if ($result === PasswordVerifier::UNUSABLE_HASH) {
            return back()->withErrors([
                'nis' => 'Password akun Anda tidak bisa diverifikasi. Hubungi administrator untuk reset password.',
            ])->withInput($request->only('nis'));
        }

        if ($result === PasswordVerifier::VALID) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'nis' => 'Akun Anda tidak aktif. Hubungi administrator.',
                ])->withInput($request->only('nis'));
            }

            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();
            $this->rememberLoginDate();

            $user->update([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_login' => now(),
            ]);

            ActivityLog::log('login', 'auth', 'Siswa login via NIS');

            $rulesEnabled = filter_var(
                \App\Models\SystemSetting::get('student_rules_enabled', '1'),
                FILTER_VALIDATE_BOOLEAN
            );
            $rulesVersion = max(1, (int) \App\Models\SystemSetting::get('student_rules_version', 1));

            if ($rulesEnabled && (int) $user->student_rules_ack_version < $rulesVersion) {
                $request->session()->put('student_rules_pending_version', $rulesVersion);
            } else {
                $request->session()->forget('student_rules_pending_version');
            }

            return $this->redirectByRole($user);
        }

        $this->handleFailedLogin($user);

        return back()->withErrors([
            'nis' => 'NIS atau password salah.',
        ])->withInput($request->only('nis'));
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

        // Auth::attempt() tidak dipakai: pemeriksaannya memanggil Hash::check()
        // di dalam user provider, jadi hash yang tidak sah melempar exception
        // dan login staff pun balas 500 — sama seperti jalur siswa.
        $result = $user
            ? $this->passwords->check($user, $request->input('password'))
            : PasswordVerifier::INVALID;

        if ($result === PasswordVerifier::UNUSABLE_HASH) {
            return back()->withErrors([
                'email' => 'Password akun Anda tidak bisa diverifikasi. Hubungi administrator untuk reset password.',
            ])->withInput($request->only('email'));
        }

        if ($result === PasswordVerifier::VALID) {
            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

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

            $this->rememberLoginDate();

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
     * Handle failed login attempt - increment counter (account is never locked)
     */
    protected function handleFailedLogin(User $user)
    {
        $user->increment('login_attempts');
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', 'auth', 'User logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget(\App\Http\Middleware\LogoutOnNewDay::COOKIE));

        return redirect()->route('login');
    }

    /**
     * Stamps today's date in a cookie so LogoutOnNewDay can force a
     * re-login once the calendar day rolls over, regardless of how much
     * of the session/remember-me lifetime is still left.
     */
    protected function rememberLoginDate(): void
    {
        Cookie::queue(
            \App\Http\Middleware\LogoutOnNewDay::COOKIE,
            now()->toDateString(),
            now()->diffInMinutes(now()->endOfDay()) + 1
        );
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
