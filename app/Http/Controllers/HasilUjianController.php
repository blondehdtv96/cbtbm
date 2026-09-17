<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use Illuminate\Http\Request;

class HasilUjianController extends Controller
{
    /**
     * Daftar semua ujian untuk manajemen & download hasil secara terpusat,
     * terpisah dari menu Ujian supaya admin tidak perlu masuk ke tiap ujian
     * satu-satu hanya untuk mengunduh nilai.
     */
    public function index(Request $request)
    {
        $query = Ujian::with(['mapel', 'guru', 'sesiUjian', 'kelasList'])
            ->withCount([
                'pesertaUjians',
                'pesertaUjians as selesai_count' => fn($q) => $q->where('status', 'selesai'),
            ])
            ->withAvg(['pesertaUjians as rata_rata_nilai' => fn($q) => $q->where('status', 'selesai')], 'nilai');

        if ($request->filled('search')) {
            $query->where('nama_ujian', 'like', "%{$request->search}%");
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        // Default: hanya tampilkan ujian yang sudah punya peserta selesai
        // (paling relevan untuk didownload), kecuali admin cari yang lain.
        if ($request->boolean('semua', false) === false && !$request->filled('search') && !$request->filled('mapel_id')) {
            $query->whereHas('pesertaUjians', fn($q) => $q->where('status', 'selesai'));
        }

        $ujians = $query->latest()->paginate(15)->withQueryString();

        $mapelList = \App\Models\Mapel::orderBy('nama_mapel')->get();

        return view('hasil-ujian.index', compact('ujians', 'mapelList'));
    }
}
