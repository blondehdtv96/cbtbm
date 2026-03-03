@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola semua pengguna sistem')

@section('content')
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control-ios" placeholder="Cari nama/email..." value="{{ request('search') }}" style="width: 220px;">
            <select name="role" class="form-select-ios" style="width: 160px;">
                <option value="">Semua Role</option>
                <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="guru" {{ request('role') == 'guru' ? 'selected' : '' }}>Guru</option>
            </select>
            <button type="submit" class="btn btn-ios btn-ios-light"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn btn-ios btn-ios-primary">
            <i class="bi bi-plus-lg"></i> Tambah User
        </a>
    </div>

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Login Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge-ios {{ $user->role === 'superadmin' ? 'danger' : ($user->role === 'admin' ? 'purple' : ($user->role === 'guru' ? 'info' : 'primary')) }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-ios {{ $user->is_active ? 'success' : 'danger' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $user->last_login ? $user->last_login->format('d M Y H:i') : 'Belum login' }}</small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ios btn-ios-sm btn-ios-light"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ios btn-ios-sm {{ $user->is_active ? 'btn-ios-warning' : 'btn-ios-success' }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-{{ $user->is_active ? 'pause' : 'play' }}-fill"></i>
                                    </button>
                                </form>
                                @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus user ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3 pagination-ios">
        {{ $users->withQueryString()->links() }}
    </div>
</div>
@endsection
