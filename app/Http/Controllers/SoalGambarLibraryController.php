<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\OpsiJawaban;
use App\Models\SoalGambarLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SoalGambarLibraryController extends Controller
{
    /**
     * List gambar yang sudah diupload, dipisah per mata pelajaran supaya guru
     * tidak perlu menyisir gambar mapel lain saat mengisi Excel import.
     */
    public function index(Request $request)
    {
        $mapels = Mapel::where('is_active', true)->orderBy('nama_mapel')->get();

        $selectedMapelId = $request->filled('mapel_id')
            ? (int) $request->integer('mapel_id')
            : optional($mapels->first())->id;

        $query = SoalGambarLibrary::with('uploader')
            ->where('mapel_id', $selectedMapelId)
            ->latest();

        if ($request->filled('search')) {
            $query->where('original_filename', 'like', '%' . $request->search . '%');
        }

        $totalGambar = SoalGambarLibrary::where('mapel_id', $selectedMapelId)->count();
        $gambars = $query->paginate(60)->withQueryString();

        return view('admin.soal-gambar.index', compact('gambars', 'totalGambar', 'mapels', 'selectedMapelId'));
    }

    /**
     * Upload satu atau beberapa gambar ke pustaka. Nama file asli dijaga unik
     * karena itulah yang dicocokkan dengan kolom Excel saat import.
     *
     * Sengaja TIDAK membatasi jumlah file per request di level validasi — batas
     * nyata datang dari PHP sendiri (`max_file_uploads`, default 20 file per
     * request). Untuk upload dalam jumlah besar, frontend (lihat index.blade.php)
     * mengirim file dalam beberapa batch kecil secara berurutan supaya batas itu
     * tidak pernah tersentuh, dan endpoint ini merespon JSON supaya bisa dipanggil
     * berulang lewat fetch tanpa reload halaman.
     */
    public function store(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'gambar' => 'required|array|min:1',
            'gambar.*' => 'image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'mapel_id.required' => 'Pilih mata pelajaran dulu sebelum upload.',
            'mapel_id.exists' => 'Mata pelajaran tidak valid.',
            'gambar.*.image' => 'File harus berupa gambar.',
            'gambar.*.mimes' => 'Format gambar harus jpg, jpeg, atau png.',
            'gambar.*.max' => 'Ukuran gambar maksimal 1MB.',
        ]);

        $mapelId = $request->integer('mapel_id');
        $uploaded = 0;
        $skipped = [];

        foreach ($request->file('gambar') as $file) {
            $originalName = $file->getClientOriginalName();

            if (SoalGambarLibrary::where('mapel_id', $mapelId)->where('original_filename', $originalName)->exists()) {
                $skipped[] = $originalName;
                continue;
            }

            $storedPath = $file->store('soal-gambar/library', 'public');

            SoalGambarLibrary::create([
                'mapel_id' => $mapelId,
                'original_filename' => $originalName,
                'stored_path' => $storedPath,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id(),
            ]);

            $uploaded++;
        }

        if ($uploaded > 0) {
            ActivityLog::log('create', 'soal_gambar_library', "Upload {$uploaded} gambar ke pustaka gambar soal");
        }

        if ($request->wantsJson()) {
            return response()->json(['uploaded' => $uploaded, 'skipped' => $skipped]);
        }

        if (!empty($skipped)) {
            return back()->with(
                'warning',
                "{$uploaded} gambar berhasil diupload. Dilewati karena nama file sudah ada: " . implode(', ', $skipped)
            );
        }

        return back()->with('success', "{$uploaded} gambar berhasil diupload ke pustaka.");
    }

    /**
     * Hapus gambar dari pustaka — ditolak kalau masih dipakai oleh soal/opsi manapun,
     * supaya tidak ada gambar rusak (broken image) di soal yang sudah dipakai.
     */
    public function destroy(SoalGambarLibrary $soalGambar)
    {
        $usedByBankSoal = BankSoal::where('gambar_soal', $soalGambar->stored_path)->exists();
        $usedByOpsi = OpsiJawaban::where('gambar_opsi', $soalGambar->stored_path)->exists();

        if ($usedByBankSoal || $usedByOpsi) {
            return back()->with('error', 'Gambar tidak bisa dihapus karena masih dipakai oleh soal yang sudah diimport.');
        }

        Storage::disk('public')->delete($soalGambar->stored_path);
        $soalGambar->delete();

        ActivityLog::log('delete', 'soal_gambar_library', "Menghapus gambar: {$soalGambar->original_filename}");

        return back()->with('success', 'Gambar berhasil dihapus dari pustaka.');
    }
}
