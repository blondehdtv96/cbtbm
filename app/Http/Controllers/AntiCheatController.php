<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AntiCheatController extends Controller
{
    /** Pilihan retensi (dalam hari) untuk pembersihan log pelanggaran */
    private const RETENTION_OPTIONS = [7, 30, 90];

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats
        $totalViolations = ActivityLog::where('action', 'cheat_detected')->count();
        $todayViolations = ActivityLog::where('action', 'cheat_detected')
            ->whereDate('created_at', today())
            ->count();
        $uniqueStudents = ActivityLog::where('action', 'cheat_detected')
            ->distinct('user_id')
            ->count('user_id');

        $retentionOptions = self::RETENTION_OPTIONS;
        $hasFilter = $request->filled('search') || $request->filled('date_from') || $request->filled('date_to');

        return view('anti-cheat.index', compact(
            'logs', 'totalViolations', 'todayViolations', 'uniqueStudents',
            'retentionOptions', 'hasFilter'
        ));
    }

    /**
     * Hapus satu baris log pelanggaran
     */
    public function destroy(ActivityLog $log)
    {
        if ($log->action !== 'cheat_detected') {
            return back()->with('error', 'Data yang dipilih bukan log pelanggaran anti-cheat.');
        }

        $siswa = $log->data['siswa_nama'] ?? ($log->user->name ?? 'tidak diketahui');
        $waktu = $log->created_at->format('d/m/Y H:i');
        $log->delete();

        ActivityLog::log('delete', 'anti_cheat', "Menghapus 1 log pelanggaran ({$siswa} — {$waktu})");

        return back()->with('success', 'Log pelanggaran berhasil dihapus.');
    }

    /**
     * Hapus beberapa log pelanggaran terpilih
     */
    public function bulkDestroy(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada log pelanggaran yang dipilih.');
        }

        $deleted = ActivityLog::where('action', 'cheat_detected')
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted === 0) {
            return back()->with('warning', 'Tidak ada log pelanggaran yang terhapus.');
        }

        ActivityLog::log('delete', 'anti_cheat', "Menghapus {$deleted} log pelanggaran terpilih (bulk delete)");

        return back()->with('success', "{$deleted} log pelanggaran berhasil dihapus.");
    }

    /**
     * Bersihkan log pelanggaran: semua yang cocok dengan filter aktif,
     * atau hanya yang lebih lama dari sekian hari (retensi).
     */
    public function clear(Request $request)
    {
        $request->validate([
            'older_than' => 'nullable|in:' . implode(',', self::RETENTION_OPTIONS),
        ]);

        $query = $this->filteredQuery($request);
        $olderThan = $request->input('older_than');

        if ($olderThan) {
            $query->where('created_at', '<', now()->subDays((int) $olderThan)->startOfDay());
        }

        $deleted = $query->delete();

        if ($deleted === 0) {
            return back()->with('warning', 'Tidak ada log pelanggaran yang cocok untuk dihapus.');
        }

        $keterangan = $olderThan
            ? "Membersihkan {$deleted} log pelanggaran lebih lama dari {$olderThan} hari"
            : "Membersihkan {$deleted} log pelanggaran";

        ActivityLog::log('delete', 'anti_cheat', $keterangan);

        return redirect()->route('admin.anti-cheat.index')
            ->with('success', "{$deleted} log pelanggaran berhasil dihapus.");
    }

    /**
     * Query log pelanggaran dengan filter dari halaman monitoring
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = ActivityLog::where('action', 'cheat_detected');

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

        return $query;
    }
}
