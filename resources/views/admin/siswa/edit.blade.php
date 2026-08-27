@extends('layouts.app')

@section('title', 'Edit Siswa')
@section('page-title', 'Edit Siswa')
@section('page-subtitle', 'Edit data siswa: ' . $siswa->nama)

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-pencil-fill me-2"></i>Edit Siswa: {{ $siswa->nama }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.siswa.update', $siswa) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-ios">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control-ios w-100" value="{{ old('name', $siswa->nama) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">NISN <small class="text-muted">(untuk login)</small> <span class="text-danger">*</span></label>
                                <input type="text" name="nisn" class="form-control-ios w-100" value="{{ old('nisn', $siswa->nisn) }}"
                                       maxlength="20" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">NIS <span class="text-danger">*</span></label>
                                <input type="text" name="nis" class="form-control-ios w-100" value="{{ old('nis', $siswa->nis) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" class="form-select-ios w-100" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}" {{ (int) old('kelas_id', $siswa->kelas_id) === (int) $kelas->id ? 'selected' : '' }}>
                                            {{ $kelas->nama_kelas }} - {{ $kelas->jurusan->nama_jurusan ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">Status</label>
                                <select name="is_active" class="form-select-ios w-100">
                                    <option value="1" {{ $siswa->user && $siswa->user->is_active ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ $siswa->user && !$siswa->user->is_active ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>

                            {{-- Reset Password Section --}}
                            <div class="col-12">
                                <div style="background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="bi bi-key-fill" style="color: #2563eb; font-size: 18px;"></i>
                                        <div style="font-size: 13px; color: #1e3a5f; font-weight: 500;">
                                            Siswa login menggunakan <strong>NISN</strong> dan password yang sudah di-generate.
                                        </div>
                                    </div>
                                    @if($siswa->user)
                                    <form method="POST" action="{{ route('admin.siswa.reset-password', $siswa->user) }}" style="margin: 0;" onsubmit="return confirm('Reset password siswa ini? Password baru akan di-generate otomatis.')">
                                        @csrf
                                        <button type="submit" class="btn btn-ios btn-ios-warning btn-ios-sm">
                                            <i class="bi bi-arrow-clockwise"></i> Reset Password
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Update</button>
                            <a href="{{ route('admin.siswa.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
