<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->tooManyRequestsResponse($request, (int) RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, (int) ($decayMinutes * 60));

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * Keyed by user+IP when authenticated. For pre-auth requests (login),
     * keyed by the submitted account identifier+IP instead of IP alone —
     * many students share one public IP behind a school's NAT gateway, so
     * an IP-only key would let a handful of login attempts lock out the
     * entire school for a minute at a time.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->id . '|' . $request->ip());
        }

        // Login siswa memakai NIS; 'nisn' tetap dibaca agar halaman lama yang
        // masih ter-cache di browser siswa tidak kehilangan kunci per-akunnya.
        $identifier = $request->input('nis')
            ?? $request->input('nisn')
            ?? $request->input('email');

        // Isi request tidak dijamin berupa string: form yang mengirim field
        // bernama array (nis[]=...) membuat strtolower() melempar TypeError —
        // dulu login balas "500 Server Error" sebelum validasi sempat jalan.
        // Nilai non-skalar cukup diperlakukan seperti tanpa identitas.
        if (is_scalar($identifier) && trim((string) $identifier) !== '') {
            return sha1('login|' . mb_strtolower(trim((string) $identifier)) . '|' . $request->ip());
        }

        return sha1($request->ip());
    }

    /**
     * Jawaban saat batas percobaan terlampaui.
     *
     * Endpoint AJAX (autosave jawaban, muat soal) tetap menerima JSON karena
     * skrip ujian mengandalkannya, sedangkan submit form biasa (login, token
     * ujian) dikembalikan ke halamannya dengan pesan yang bisa dibaca user —
     * bukan tumpahan JSON mentah di layar.
     */
    protected function tooManyRequestsResponse(Request $request, int $retryAfter): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too many requests. Please slow down.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        $field = match (true) {
            $request->has('nis') => 'nis',
            $request->has('nisn') => 'nisn',
            $request->has('email') => 'email',
            default => 'throttle',
        };
        $seconds = max(1, $retryAfter);

        return back()
            ->withErrors([
                $field => "Terlalu banyak percobaan. Silakan tunggu {$seconds} detik, lalu coba lagi.",
            ])
            ->withInput($request->except(['password', 'password_confirmation', '_token']));
    }

    /**
     * Calculate remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return RateLimiter::remaining($key, $maxAttempts);
    }

    /**
     * Add rate limit headers.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }
}
