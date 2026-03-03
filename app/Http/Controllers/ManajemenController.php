<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\SesiUjian;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ManajemenController extends Controller
{
    // ===== JURUSAN =====
    public function jurusanIndex()
    {
        $jurusans = Jurusan::withCount('kelas')->paginate(15);
        return view('admin.jurusan.index', compact('jurusans'));
    }

    public function jurusanStore(Request $request)
    {
        $request->validate([
            'nama_jurusan' => 'required|string|max:255',
            'kode_jurusan' => 'required|string|max:20|unique:jurusans',
        ]);

        Jurusan::create($request->only(['nama_jurusan', 'kode_jurusan', 'deskripsi']));
        ActivityLog::log('create', 'jurusan', "Membuat jurusan: {$request->nama_jurusan}");

        return back()->with('success', 'Jurusan berhasil ditambahkan!');
    }

    public function jurusanUpdate(Request $request, Jurusan $jurusan)
    {
        $request->validate([
            'nama_jurusan' => 'required|string|max:255',
            'kode_jurusan' => 'required|string|max:20|unique:jurusans,kode_jurusan,' . $jurusan->id,
        ]);

        $jurusan->update($request->only(['nama_jurusan', 'kode_jurusan', 'deskripsi', 'is_active']));
        ActivityLog::log('update', 'jurusan', "Mengupdate jurusan: {$jurusan->nama_jurusan}");

        return back()->with('success', 'Jurusan berhasil diupdate!');
    }

    public function jurusanDestroy(Jurusan $jurusan)
    {
        ActivityLog::log('delete', 'jurusan', "Menghapus jurusan: {$jurusan->nama_jurusan}");
        $jurusan->delete();
        return back()->with('success', 'Jurusan berhasil dihapus!');
    }

    // ===== KELAS =====
    public function kelasIndex()
    {
        $kelasList = Kelas::with('jurusan')->withCount('siswas')->paginate(15);
        $jurusans = Jurusan::where('is_active', true)->get();
        return view('admin.kelas.index', compact('kelasList', 'jurusans'));
    }

    public function kelasStore(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat' => 'required|in:10,11,12',
        ]);

        Kelas::create($request->only(['nama_kelas', 'jurusan_id', 'tingkat']));
        ActivityLog::log('create', 'kelas', "Membuat kelas: {$request->nama_kelas}");

        return back()->with('success', 'Kelas berhasil ditambahkan!');
    }

    public function kelasUpdate(Request $request, Kelas $kelas)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat' => 'required|in:10,11,12',
        ]);

        $kelas->update($request->only(['nama_kelas', 'jurusan_id', 'tingkat', 'is_active']));
        ActivityLog::log('update', 'kelas', "Mengupdate kelas: {$kelas->nama_kelas}");

        return back()->with('success', 'Kelas berhasil diupdate!');
    }

    public function kelasDestroy(Kelas $kelas)
    {
        ActivityLog::log('delete', 'kelas', "Menghapus kelas: {$kelas->nama_kelas}");
        $kelas->delete();
        return back()->with('success', 'Kelas berhasil dihapus!');
    }

    // ===== MAPEL =====
    public function mapelIndex()
    {
        $mapels = Mapel::with('jurusan')->paginate(15);
        $jurusans = Jurusan::where('is_active', true)->get();
        return view('admin.mapel.index', compact('mapels', 'jurusans'));
    }

    public function mapelStore(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:255',
            'kode_mapel' => 'required|string|max:20|unique:mapels',
        ]);

        Mapel::create($request->only(['nama_mapel', 'kode_mapel', 'jurusan_id', 'is_umum']));
        ActivityLog::log('create', 'mapel', "Membuat mapel: {$request->nama_mapel}");

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan!');
    }

    public function mapelUpdate(Request $request, Mapel $mapel)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:255',
            'kode_mapel' => 'required|string|max:20|unique:mapels,kode_mapel,' . $mapel->id,
        ]);

        $mapel->update($request->only(['nama_mapel', 'kode_mapel', 'jurusan_id', 'is_umum', 'is_active']));
        ActivityLog::log('update', 'mapel', "Mengupdate mapel: {$mapel->nama_mapel}");

        return back()->with('success', 'Mata pelajaran berhasil diupdate!');
    }

    public function mapelDestroy(Mapel $mapel)
    {
        ActivityLog::log('delete', 'mapel', "Menghapus mapel: {$mapel->nama_mapel}");
        $mapel->delete();
        return back()->with('success', 'Mata pelajaran berhasil dihapus!');
    }

    // ===== SESI UJIAN =====
    public function sesiIndex()
    {
        $sesiList = SesiUjian::withCount('ujians')->orderBy('jam_mulai')->paginate(15);
        return view('admin.sesi.index', compact('sesiList'));
    }

    public function sesiStore(Request $request)
    {
        $request->validate([
            'nama_sesi' => 'required|string|max:255',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ]);

        SesiUjian::create($request->only(['nama_sesi', 'jam_mulai', 'jam_selesai']));
        ActivityLog::log('create', 'sesi_ujian', "Membuat sesi: {$request->nama_sesi}");

        return back()->with('success', 'Sesi ujian berhasil ditambahkan!');
    }

    public function sesiUpdate(Request $request, SesiUjian $sesi)
    {
        $request->validate([
            'nama_sesi' => 'required|string|max:255',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ]);

        $sesi->update($request->only(['nama_sesi', 'jam_mulai', 'jam_selesai', 'is_active']));
        ActivityLog::log('update', 'sesi_ujian', "Mengupdate sesi: {$sesi->nama_sesi}");

        return back()->with('success', 'Sesi ujian berhasil diupdate!');
    }

    public function sesiDestroy(SesiUjian $sesi)
    {
        ActivityLog::log('delete', 'sesi_ujian', "Menghapus sesi: {$sesi->nama_sesi}");
        $sesi->delete();
        return back()->with('success', 'Sesi ujian berhasil dihapus!');
    }
}
