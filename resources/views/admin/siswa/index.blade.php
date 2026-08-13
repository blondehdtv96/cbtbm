@extends('layouts.app')

@section('title', 'Daftar Siswa')
@section('page-title', 'Manajemen Siswa')
@section('page-subtitle', 'Kelola data dan akun siswa')

@section('content')
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control-ios" placeholder="Cari nama/NISN/NIS..." value="{{ request('search') }}" style="width: 220px;">
            <select name="kelas_id" class="form-select-ios" style="width: 180px;">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                        {{ $kelas->nama_kelas }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-ios btn-ios-light"><i class="bi bi-search"></i></button>
        </form>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.siswa.export', request()->only(['search', 'kelas_id'])) }}" class="btn btn-ios btn-ios-light">
                <i class="bi bi-download"></i> Download Data
            </a>
            <a href="{{ route('admin.import-siswa.index') }}" class="btn btn-ios btn-ios-success">
                <i class="bi bi-cloud-arrow-up-fill"></i> Import
            </a>
            <a href="{{ route('admin.siswa.create') }}" class="btn btn-ios btn-ios-primary">
                <i class="bi bi-plus-lg"></i> Tambah Siswa
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div style="background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.1); border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(37, 99, 235, 0.12); display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-people-fill" style="color: #2563eb; font-size: 20px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--primary);">{{ \App\Models\Siswa::count() }}</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Total Siswa</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div style="background: rgba(34, 197, 94, 0.06); border: 1px solid rgba(34, 197, 94, 0.1); border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(34, 197, 94, 0.12); display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 20px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--success);">{{ \App\Models\User::where('role', 'siswa')->where('is_active', true)->count() }}</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Siswa Aktif</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div style="background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.1); border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(239, 68, 68, 0.12); display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-x-circle-fill" style="color: #ef4444; font-size: 20px;"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--danger);">{{ \App\Models\User::where('role', 'siswa')->where('is_active', false)->count() }}</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Siswa Nonaktif</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-ios">
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table-ios" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NISN</th>
                        <th>NIS</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Login Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswas as $idx => $siswa)
                    <tr>
                        <td>{{ $siswas->firstItem() + $idx }}</td>
                        <td>
                            <code style="font-weight: 700; background: rgba(37, 99, 235, 0.08); padding: 3px 8px; border-radius: 6px; color: var(--primary);">{{ $siswa->nisn }}</code>
                        </td>
                        <td><code>{{ $siswa->nis }}</code></td>
                        <td><strong>{{ $siswa->nama }}</strong></td>
                        <td>
                            <span class="badge-ios primary">{{ $siswa->kelas->nama_kelas ?? '-' }}</span>
                        </td>
                        <td>
                            @if($siswa->user && $siswa->user->plain_password)
                            <code style="font-weight:700;background:rgba(245,158,11,0.1);padding:3px 8px;border-radius:6px;color:#d97706;letter-spacing:1px;">{{ $siswa->user->plain_password }}</code>
                            @else
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($siswa->user)
                                <span class="badge-ios {{ $siswa->user->is_active ? 'success' : 'danger' }}">
                                    {{ $siswa->user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            @else
                                <span class="badge-ios secondary">No Account</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $siswa->user && $siswa->user->last_login ? $siswa->user->last_login->format('d M Y H:i') : 'Belum login' }}
                            </small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.siswa.edit', $siswa) }}" class="btn btn-ios btn-ios-sm btn-ios-light" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($siswa->user)
                                <form action="{{ route('admin.siswa.toggle-active', $siswa) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ios btn-ios-sm {{ $siswa->user->is_active ? 'btn-ios-warning' : 'btn-ios-success' }}" title="{{ $siswa->user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-{{ $siswa->user->is_active ? 'pause' : 'play' }}-fill"></i>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('admin.siswa.destroy', $siswa) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus siswa {{ $siswa->nama }}? Akun login juga akan dihapus.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ios btn-ios-sm btn-ios-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h5>Belum ada data siswa</h5>
                                <p>Tambahkan siswa satu per satu atau gunakan fitur Import</p>
                                <div class="d-flex gap-2 justify-content-center mt-3">
                                    <a href="{{ route('admin.siswa.create') }}" class="btn btn-ios btn-ios-primary btn-ios-sm"><i class="bi bi-plus-lg"></i> Tambah Siswa</a>
                                    <a href="{{ route('admin.import-siswa.index') }}" class="btn btn-ios btn-ios-success btn-ios-sm"><i class="bi bi-cloud-arrow-up-fill"></i> Import Excel</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3 pagination-ios">
        {{ $siswas->withQueryString()->links() }}
    </div>
</div>
@endsection
