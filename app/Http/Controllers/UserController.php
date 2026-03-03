<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', '!=', 'siswa');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $jurusans = Jurusan::where('is_active', true)->get();
        return view('admin.users.create', compact('jurusans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:superadmin,admin,guru',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'nip' => 'nullable|unique:gurus,nip',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        if ($request->role === 'guru') {
            Guru::create([
                'nip' => $request->nip,
                'nama' => $request->name,
                'user_id' => $user->id,
            ]);
        }

        ActivityLog::log('create', 'user', "Membuat user: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        $jurusans = Jurusan::where('is_active', true)->get();
        return view('admin.users.edit', compact('user', 'jurusans'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:superadmin,admin,guru',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($user->role === 'guru' && $user->guru) {
            $user->guru->update([
                'nama' => $request->name,
                'nip' => $request->nip ?? $user->guru->nip,
            ]);
        }

        ActivityLog::log('update', 'user', "Mengupdate user: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri!');
        }

        ActivityLog::log('delete', 'user', "Menghapus user: {$user->name}");
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus!');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLog::log('update', 'user', "User {$user->name} {$status}");

        return back()->with('success', "User berhasil {$status}!");
    }
}
