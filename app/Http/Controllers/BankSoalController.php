<?php

namespace App\Http\Controllers;

use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\OpsiJawaban;
use App\Models\Guru;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class BankSoalController extends Controller
{
    public function index(Request $request)
    {
        // ─── MODE 1: Grouped list by Mapel (default) ─────────────────────
        if (!$request->filled('mapel_id')) {
            $guruId = (auth()->user()->isGuru() && auth()->user()->guru)
                ? auth()->user()->guru->id
                : null;

            $query = \App\Models\Mapel::with('jurusan')
                ->withCount([
                'bankSoals as total_soal' => fn($q) => $guruId ? $q->where('guru_id', $guruId) : $q,
                'bankSoals as pg_soal' => fn($q) => ($guruId ? $q->where('guru_id', $guruId) : $q)->whereIn('tipe_soal', ['pg', 'pg_kompleks']),
                'bankSoals as essay_soal' => fn($q) => ($guruId ? $q->where('guru_id', $guruId) : $q)->where('tipe_soal', 'essay'),
            ]);

            // Guru: hanya tampilkan mapel yang di-assign via Data Guru
            if ($guruId) {
                $guru = \App\Models\Guru::find($guruId);
                $assignedIds = $guru ? $guru->mapels()->pluck('mapels.id')->toArray() : [];
                $query->whereIn('id', $assignedIds);
            }

            $mapels = $query->orderBy('kode_mapel')->get();

            return view('banksoal.index', compact('mapels'));
        }

        // ─── MODE 2: Individual soals filtered by mapel ───────────────────
        $mapel = \App\Models\Mapel::findOrFail($request->mapel_id);
        $query = BankSoal::with(['mapel', 'guru', 'opsiJawabans'])
            ->where('mapel_id', $request->mapel_id);

        if (auth()->user()->isGuru() && auth()->user()->guru) {
            $query->where('guru_id', auth()->user()->guru->id);
        }
        if ($request->filled('tipe_soal')) {
            $query->where('tipe_soal', $request->tipe_soal);
        }
        if ($request->filled('search')) {
            $query->where('pertanyaan', 'like', "%{$request->search}%");
        }

        $soals = $query->latest()->paginate(20);
        $mapelList = \App\Models\Mapel::where('is_active', true)->get();

        return view('banksoal.soal_list', compact('soals', 'mapel', 'mapelList'));
    }

    public function create()
    {
        $mapels = Mapel::where('is_active', true);

        // Guru: hanya mapel yang di-assign
        $user = auth()->user();
        if ($user->isGuru() && $user->guru) {
            $assignedIds = $user->guru->mapels()->pluck('mapels.id') ?? collect();
            $mapels->whereIn('id', $assignedIds);
        }

        $mapels = $mapels->orderBy('nama_mapel')->get();
        return view('banksoal.create', compact('mapels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'tipe_soal' => 'required|in:pg,essay,pg_kompleks,menjodohkan',
            'bobot_nilai' => 'required|integer|min:1',
            'pertanyaan' => 'required|string',
            'gambar_soal' => 'nullable|image|max:2048',
        ]);

        $guru = auth()->user()->guru;
        if (!$guru) {
            return back()->with('error', 'Profil guru tidak ditemukan.');
        }

        $data = $request->only(['mapel_id', 'tipe_soal', 'bobot_nilai', 'pertanyaan']);
        $data['guru_id'] = $guru->id;
        $data['status'] = 'aktif';

        if ($request->hasFile('gambar_soal')) {
            $data['gambar_soal'] = $request->file('gambar_soal')->store('soal-images', 'public');
        }

        $bankSoal = BankSoal::create($data);

        // Save options for PG type
        if (in_array($request->tipe_soal, ['pg', 'pg_kompleks'])) {
            $labels = $request->input('opsi_label', []);
            $contents = $request->input('opsi_isi', []);
            $corrects = $request->input('opsi_correct', []);

            foreach ($labels as $i => $label) {
                if (!empty($contents[$i])) {
                    OpsiJawaban::create([
                        'bank_soal_id' => $bankSoal->id,
                        'opsi_label' => $label,
                        'isi_opsi' => $contents[$i],
                        'is_correct' => in_array($i, $corrects),
                    ]);
                }
            }
        }

        ActivityLog::log('create', 'bank_soal', "Membuat soal: {$bankSoal->id}");

        // Redirect back to soal list for this mapel
        return redirect()->route('banksoal.index', ['mapel_id' => $bankSoal->mapel_id])
            ->with('success', 'Soal berhasil ditambahkan!');
    }

    public function show(BankSoal $banksoal)
    {
        $banksoal->load(['mapel', 'guru', 'opsiJawabans']);
        return view('banksoal.show', compact('banksoal'));
    }

    public function edit(BankSoal $banksoal)
    {
        $banksoal->load('opsiJawabans');
        $mapels = Mapel::where('is_active', true)->get();
        return view('banksoal.edit', compact('banksoal', 'mapels'));
    }

    public function update(Request $request, BankSoal $banksoal)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'tipe_soal' => 'required|in:pg,essay,pg_kompleks,menjodohkan',
            'bobot_nilai' => 'required|integer|min:1',
            'pertanyaan' => 'required|string',
        ]);

        $data = $request->only(['mapel_id', 'tipe_soal', 'bobot_nilai', 'pertanyaan', 'status']);

        if ($request->hasFile('gambar_soal')) {
            $data['gambar_soal'] = $request->file('gambar_soal')->store('soal-images', 'public');
        }

        $banksoal->update($data);

        // Update options
        if (in_array($request->tipe_soal, ['pg', 'pg_kompleks'])) {
            $banksoal->opsiJawabans()->delete();

            $labels = $request->input('opsi_label', []);
            $contents = $request->input('opsi_isi', []);
            $corrects = $request->input('opsi_correct', []);

            foreach ($labels as $i => $label) {
                if (!empty($contents[$i])) {
                    OpsiJawaban::create([
                        'bank_soal_id' => $banksoal->id,
                        'opsi_label' => $label,
                        'isi_opsi' => $contents[$i],
                        'is_correct' => in_array($i, $corrects),
                    ]);
                }
            }
        }

        ActivityLog::log('update', 'bank_soal', "Mengupdate soal: {$banksoal->id}");

        return redirect()->route('banksoal.index')->with('success', 'Soal berhasil diupdate!');
    }

    public function destroy(BankSoal $banksoal)
    {
        $mapelId = $banksoal->mapel_id;
        ActivityLog::log('delete', 'bank_soal', "Menghapus soal: {$banksoal->id}");
        $banksoal->delete();
        return redirect()->route('banksoal.index', ['mapel_id' => $mapelId])
            ->with('success', 'Soal berhasil dihapus!');
    }

    public function bulkDestroy(Request $request)
    {
        $mapelId = $request->input('mapel_id');

        // Mode 1: Hapus semua soal untuk satu mapel (dari halaman index)
        if ($request->input('delete_all_mapel')) {
            $soals = BankSoal::where('mapel_id', $mapelId);

            if (auth()->user()->isGuru() && auth()->user()->guru) {
                $soals->where('guru_id', auth()->user()->guru->id);
            }

            $count = $soals->count();
            $soals->delete();

            ActivityLog::log('delete', 'bank_soal', "Hapus semua {$count} soal mapel ID: {$mapelId}");

            return redirect()->route('banksoal.index')->with('success', "{$count} soal berhasil dihapus!");
        }

        // Mode 2: Hapus soal terpilih (dari halaman soal list)
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada soal yang dipilih.');
        }

        $soals = BankSoal::whereIn('id', $ids);

        if (auth()->user()->isGuru() && auth()->user()->guru) {
            $soals->where('guru_id', auth()->user()->guru->id);
        }

        $count = $soals->count();
        $soals->delete();

        ActivityLog::log('delete', 'bank_soal', "Hapus massal {$count} soal");

        $redirect = $mapelId
            ? route('banksoal.index', ['mapel_id' => $mapelId])
            : route('banksoal.index');

        return redirect($redirect)->with('success', "{$count} soal berhasil dihapus!");
    }
}
