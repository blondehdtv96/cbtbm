<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ujian;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonitoringController extends Controller
{
    public function index(Request $request, DatabaseBackupService $backups)
    {
        $onlineThreshold = now()->subMinutes(5);

        $stats = [
            'online_now' => User::where('last_seen_at', '>=', $onlineThreshold)->count(),
            'logins_today' => ActivityLog::where('action', 'login')->whereDate('created_at', today())->count(),
            'ujian_berlangsung' => Ujian::where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->count(),
            'activities_today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        $system = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'db_size_human' => $backups->humanSize($backups->totalSize()),
        ];

        $diskUsage = $backups->diskUsage();

        // 7-day login trend (fills in zero for days with no logins)
        $loginsByDay = ActivityLog::where('action', 'login')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => Carbon::parse($date)->translatedFormat('d M'),
                'total' => (int) ($loginsByDay[$date] ?? 0),
            ];
        }

        $mostActive = ActivityLog::selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('user')
            ->get();

        // Activity log table (filterable, paginated)
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.monitoring.index', compact(
            'stats', 'system', 'diskUsage', 'trend', 'mostActive', 'logs', 'modules', 'actions'
        ));
    }
}
