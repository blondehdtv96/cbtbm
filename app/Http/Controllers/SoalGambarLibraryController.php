<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BankSoal;
use App\Models\OpsiJawaban;
use App\Models\SoalGambarLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SoalGambarLibraryController extends Controller
{
    /**
     * List gambar yang sudah diupload, untuk dicek guru sebelum mengisi Excel import.
     */
    public function index(Request $request)
    {
        $query = SoalGambarLibrary::with('uploader')->latest();

        if ($request->filled('search')) {
            $query->where('original_filename', 'like', '%' . $request->search . '%');
        }

        $totalGambar = SoalGambarLibrary::count();
        $gambars = $query->paginate(60);

        return view('admin.soal-gambar.index', compact('gambars', 'totalGambar'));
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
            'gambar' => 'required|array|min:1',
            'gambar.*' => 'image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'gambar.*.image' => 'File harus berupa gambar.',
            'gambar.*.mimes' => 'Format gambar harus jpg, jpeg, atau png.',
            'gambar.*.max' => 'Ukuran gambar maksimal 1MB.',
        ]);

        $uploaded = 0;
        $skipped = [];

        foreach ($request->file('gambar') as $file) {
            $originalName = $file->getClientOriginalName();

            if (SoalGambarLibrary::where('original_filename', $originalName)->exists()) {
                $skipped[] = $originalName;
                continue;
            }

            $storedPath = $file->store('soal-gambar/library', 'public');

            SoalGambarLibrary::create([
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
