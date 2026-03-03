<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AntiCheatController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::where('action', 'cheat_detected')
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by search (student name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Stats
        $totalViolations = ActivityLog::where('action', 'cheat_detected')->count();
        $todayViolations = ActivityLog::where('action', 'cheat_detected')
            ->whereDate('created_at', today())
            ->count();
        $uniqueStudents = ActivityLog::where('action', 'cheat_detected')
            ->distinct('user_id')
            ->count('user_id');

        return view('anti-cheat.index', compact(
            'logs', 'totalViolations', 'todayViolations', 'uniqueStudents'
        ));
    }
}
