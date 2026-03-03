<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use App\Models\Mapel;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    /**
     * Daftar guru.
     */
    public function index(Request $request)
    {
        $query = Guru::with(['user', 'mapels']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('nip', 'like', "%{$request->search}%");
            });
        }

        $gurus = $query->orderBy('nama')->paginate(15)->withQueryString();
        $mapels = Mapel::where('is_active', true)->orderBy('nama_mapel')->get();

        return view('admin.guru.index', compact('gurus', 'mapels'));
    }

    /**
     * Simpan guru baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:gurus,nip',
            'email' => 'required|email|unique:users,email',
            'telepon' => 'nullable|string|max:20',
            'mapel_ids' => 'nullable|array',
            'mapel_ids.*' => 'exists:mapels,id',
        ]);

        // Buat user akun guru
        $user = User::create([
            'name' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $guru = Guru::create([
            'nama' => $request->nama,
            'nip' => $request->nip,
            'user_id' => $user->id,
            'telepon' => $request->telepon,
        ]);

        // Assign mapel
        if ($request->filled('mapel_ids')) {
            $guru->mapels()->sync($request->mapel_ids);
        }

        ActivityLog::log('create', 'guru', "Menambah guru: {$guru->nama}");

        return back()->with('success', "Guru \"{$guru->nama}\" berhasil ditambahkan! (Password default: password)");
    }

    /**
     * Update data guru.
     */
    public function update(Request $request, Guru $guru)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:gurus,nip,' . $guru->id,
            'telepon' => 'nullable|string|max:20',
            'mapel_ids' => 'nullable|array',
            'mapel_ids.*' => 'exists:mapels,id',
        ]);

        $guru->update([
            'nama' => $request->nama,
            'nip' => $request->nip,
            'telepon' => $request->telepon,
        ]);

        // Update user name
        if ($guru->user) {
            $guru->user->update(['name' => $request->nama]);
        }

        // Sync mapel
        $guru->mapels()->sync($request->mapel_ids ?? []);

        ActivityLog::log('update', 'guru', "Mengupdate guru: {$guru->nama}");

        return back()->with('success', "Data guru \"{$guru->nama}\" berhasil diupdate!");
    }

    /**
     * Hapus guru.
     */
    public function destroy(Guru $guru)
    {
        $nama = $guru->nama;

        // Delete user account too
        if ($guru->user) {
            $guru->user->delete();
        }

        $guru->delete();

        ActivityLog::log('delete', 'guru', "Menghapus guru: {$nama}");

        return back()->with('success', "Guru \"{$nama}\" berhasil dihapus!");
    }

    /**
     * Halaman profil guru (untuk guru login).
     */
    public function profil()
    {
        $user = auth()->user();
        $guru = $user->guru;

        if (!$guru) {
            return redirect()->route('guru.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $guru->load('mapels');

        return view('guru.profil', compact('user', 'guru'));
    }

    /**
     * Update data profil guru.
     */
    public function updateProfil(Request $request)
    {
        $user = auth()->user();
        $guru = $user->guru;

        if (!$guru) {
            return back()->with('error', 'Profil guru tidak ditemukan.');
        }

        $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:gurus,nip,' . $guru->id,
            'telepon' => 'nullable|string|max:20',
        ]);

        $guru->update([
            'nama' => $request->nama,
            'nip' => $request->nip,
            'telepon' => $request->telepon,
        ]);

        $user->update(['name' => $request->nama]);

        return back()->with('success', 'Profil berhasil diupdate!');
    }

    /**
     * Update password guru.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password lama salah!');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diubah!');
    }
}
