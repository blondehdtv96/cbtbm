@extends('layouts.app')
@section('title', 'Profil Guru')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Kelola informasi akun dan keamanan')

@section('content')
<div class="fade-in">

    @if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #166534; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#dc2626;padding:14px 18px;border-radius:14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:500;font-size:14px;">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
    </div>
    @endif

    <div class="row g-4">
        {{-- Profile Card --}}
        <div class="col-lg-4">
            <div class="card-ios" style="overflow:hidden;">
                <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:32px 24px;text-align:center;color:white;">
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.2);display:inline-flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;margin-bottom:12px;">
                        {{ strtoupper(substr($guru->nama, 0, 2)) }}
                    </div>
                    <h5 style="font-weight:700;font-size:18px;margin:0;">{{ $guru->nama }}</h5>
                    <p style="font-size:13px;opacity:0.8;margin:4px 0 0;">Guru</p>
                </div>
                <div style="padding:20px 24px;">
                    <div style="font-size:13px;">
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border-color);">
                            <span style="color:#64748b;">NIP</span>
                            <strong>{{ $guru->nip ?? '-' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border-color);">
                            <span style="color:#64748b;">Email</span>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border-color);">
                            <span style="color:#64748b;">Telepon</span>
                            <span>{{ $guru->telepon ?? '-' }}</span>
                        </div>
                        <div class="py-2">
                            <span style="color:#64748b;">Mata Pelajaran</span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @forelse($guru->mapels as $m)
                                <span class="badge-ios info" style="font-size:11px;">{{ $m->nama_mapel }}</span>
                                @empty
                                <span style="color:#cbd5e1;font-size:12px;">Belum diatur oleh admin</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Forms --}}
        <div class="col-lg-8">
            {{-- Update Profile --}}
            <div class="card-ios mb-4">
                <div class="card-header"><i class="bi bi-person-fill me-2" style="color:var(--primary);"></i>Informasi Profil</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('guru.profil.update') }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-ios">Nama Lengkap *</label>
                                <input type="text" name="nama" class="form-control-ios w-100" value="{{ old('nama', $guru->nama) }}" required>
                                @error('nama')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">NIP</label>
                                <input type="text" name="nip" class="form-control-ios w-100" value="{{ old('nip', $guru->nip) }}">
                                @error('nip')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Email</label>
                                <input type="email" class="form-control-ios w-100" value="{{ $user->email }}" disabled style="opacity:0.6;">
                                <small style="color:#94a3b8;font-size:11px;">Email tidak dapat diubah</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Telepon</label>
                                <input type="text" name="telepon" class="form-control-ios w-100" value="{{ old('telepon', $guru->telepon) }}" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-shield-lock-fill me-2" style="color:#f59e0b;"></i>Ubah Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('guru.profil.password') }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-ios">Password Lama *</label>
                                <input type="password" name="current_password" class="form-control-ios w-100" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-ios">Password Baru *</label>
                                <input type="password" name="password" class="form-control-ios w-100" required minlength="6">
                                @error('password')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-ios">Konfirmasi Password *</label>
                                <input type="password" name="password_confirmation" class="form-control-ios w-100" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-ios btn-ios-light" style="border:1.5px solid #f59e0b;color:#d97706;font-weight:600;">
                                <i class="bi bi-key-fill me-1"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
