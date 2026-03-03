@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')
@section('page-subtitle', 'Buat akun siswa baru')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Info Box --}}
            <div style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.06), rgba(99, 102, 241, 0.06)); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(37, 99, 235, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="bi bi-info-circle-fill" style="color: #2563eb; font-size: 18px;"></i>
                </div>
                <div style="font-size: 13px; color: #1e3a5f; font-weight: 500;">
                    <strong>Login Siswa:</strong> Siswa login menggunakan <strong>NISN</strong> sebagai username. Password akan di-<strong>generate otomatis</strong> oleh sistem dan ditampilkan setelah penyimpanan.
                </div>
            </div>

            <div class="card-ios">
                <div class="card-header"><i class="bi bi-person-plus-fill me-2"></i>Form Tambah Siswa</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.siswa.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-ios">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control-ios w-100" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap siswa">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">NISN <span class="text-danger">*</span></label>
                                <input type="text" name="nisn" class="form-control-ios w-100" value="{{ old('nisn') }}"
                                       placeholder="Contoh: 0012345678" inputmode="numeric" maxlength="20" required>
                                <small class="text-muted" style="font-size: 11px; margin-top: 4px; display: block;">Nomor Induk Siswa Nasional (digunakan untuk login)</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">NIS <span class="text-danger">*</span></label>
                                <input type="text" name="nis" class="form-control-ios w-100" value="{{ old('nis') }}"
                                       placeholder="Contoh: 20240001" required>
                                <small class="text-muted" style="font-size: 11px; margin-top: 4px; display: block;">Nomor Induk Siswa (internal sekolah)</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" class="form-select-ios w-100" required>
                                    <option value="">Pilih Kelas</option>
                                    @foreach($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}" {{ old('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                            {{ $kelas->nama_kelas }} - {{ $kelas->jurusan->nama_jurusan ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                            <a href="{{ route('admin.siswa.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
