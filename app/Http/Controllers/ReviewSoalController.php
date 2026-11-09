<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\SoalReview;
use Illuminate\Http\Request;

class ReviewSoalController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $ready = SoalReview::tableReady();

        $counts = ['bankSoals as total_soal'];
        if ($ready) {
            $counts['bankSoals as dicek_soal'] = fn($q) => $q->whereHas('reviews', fn($r) => $r->where('user_id', $userId));
            $counts['bankSoals as benar_soal'] = fn($q) => $q->whereHas('reviews', fn($r) => $r->where('user_id', $userId)->where('status', 'benar'));
            $counts['bankSoals as salah_soal'] = fn($q) => $q->whereHas('reviews', fn($r) => $r->where('user_id', $userId)->where('status', 'salah'));
        }

        $mapels = $this->accessibleMapels()
            ->with('jurusan')
            ->withCount($counts)
            ->orderBy('kode_mapel')
            ->get();

        return view('review-soal.index', compact('mapels', 'ready'));
    }

    public function show(Request $request, Mapel $mapel)
    {
        $this->authorizeMapel($mapel);

        $userId = auth()->id();
        $ready = SoalReview::tableReady();
        $filter = $request->input('filter', 'semua');

        $query = BankSoal::with(['opsiJawabans', 'guru'])
            ->where('mapel_id', $mapel->id)
            ->orderBy('id');

        if ($ready) {
            $query->with(['reviews' => fn($q) => $q->where('user_id', $userId)]);

            match ($filter) {
                'belum' => $query->whereDoesntHave('reviews', fn($q) => $q->where('user_id', $userId)),
                'benar', 'salah' => $query->whereHas('reviews', fn($q) => $q->where('user_id', $userId)->where('status', $filter)),
                default => null,
            };
        }

        if ($request->filled('tipe_soal')) {
            $query->where('tipe_soal', $request->tipe_soal);
        }
        if ($request->filled('search')) {
            $query->where('pertanyaan', 'like', "%{$request->search}%");
        }

        $soals = $query->paginate(10)->withQueryString();

        $stats = ['total' => BankSoal::where('mapel_id', $mapel->id)->count(), 'benar' => 0, 'salah' => 0];
        if ($ready) {
            $reviewCounts = SoalReview::where('user_id', $userId)
                ->whereHas('bankSoal', fn($q) => $q->where('mapel_id', $mapel->id))
                ->selectRaw('status, COUNT(*) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status');
            $stats['benar'] = (int) ($reviewCounts['benar'] ?? 0);
            $stats['salah'] = (int) ($reviewCounts['salah'] ?? 0);
        }
        $stats['belum'] = max(0, $stats['total'] - $stats['benar'] - $stats['salah']);

        return view('review-soal.show', compact('mapel', 'soals', 'stats', 'filter', 'ready'));
    }

    public function store(Request $request, BankSoal $banksoal)
    {
        $this->authorizeMapel($banksoal->mapel);

        if (!SoalReview::tableReady()) {
            return back()->with('error', 'Fitur cek soal belum aktif di server. Jalankan "php artisan migrate" terlebih dahulu.');
        }

        $validated = $request->validate([
            'status' => 'required|in:benar,salah',
            'catatan' => 'nullable|required_if:status,salah|string|max:1000',
        ], [
            'catatan.required_if' => 'Tuliskan bagian yang salah agar pembuat soal bisa memperbaikinya.',
        ]);

        SoalReview::updateOrCreate(
            ['bank_soal_id' => $banksoal->id, 'user_id' => auth()->id()],
            ['status' => $validated['status'], 'catatan' => $validated['catatan'] ?? null]
        );

        ActivityLog::log('review', 'bank_soal', "Cek soal #{$banksoal->id}: {$validated['status']}");

        return redirect()->to(url()->previous() . '#soal-' . $banksoal->id)
            ->with('success', "Soal #{$banksoal->id} ditandai " . strtoupper($validated['status']) . '.');
    }

    public function destroy(BankSoal $banksoal)
    {
        $this->authorizeMapel($banksoal->mapel);

        if (SoalReview::tableReady()) {
            SoalReview::where('bank_soal_id', $banksoal->id)->where('user_id', auth()->id())->delete();
        }

        return redirect()->to(url()->previous() . '#soal-' . $banksoal->id)
            ->with('success', "Penilaian soal #{$banksoal->id} dibatalkan.");
    }

    private function accessibleMapels()
    {
        $user = auth()->user();
        $query = Mapel::query();

        if ($user->isGuru()) {
            $assignedIds = $user->guru ? $user->guru->mapels()->pluck('mapels.id') : collect();
            $query->whereIn('id', $assignedIds);
        }

        return $query;
    }

    private function authorizeMapel(?Mapel $mapel): void
    {
        abort_unless($mapel && $this->accessibleMapels()->whereKey($mapel->id)->exists(), 403,
            'Mata pelajaran ini tidak di-assign ke Anda.');
    }
}
