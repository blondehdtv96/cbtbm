<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentRulesAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = filter_var(
            SystemSetting::get('student_rules_enabled', '1'),
            FILTER_VALIDATE_BOOLEAN
        );
        $version = max(1, (int) SystemSetting::get('student_rules_version', 1));
        $acceptedVersion = (int) optional($request->user())->student_rules_ack_version;

        if (!$enabled || $acceptedVersion >= $version) {
            return $next($request);
        }

        $request->session()->put('student_rules_pending_version', $version);
        $message = 'Baca dan setujui peraturan siswa sebelum memulai ujian.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'rules_required' => true,
            ], 403);
        }

        return redirect()->route('siswa.dashboard')->with('warning', $message);
    }
}
