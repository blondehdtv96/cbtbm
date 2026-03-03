@extends('layouts.app')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')
@section('page-subtitle', 'Buat pengguna baru (Admin / Guru)')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-person-plus-fill me-2"></i>Form Tambah User</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-ios">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select-ios w-100" id="roleSelect" required onchange="toggleFields()">
                                    <option value="">Pilih Role</option>
                                    <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="guru" {{ old('role') == 'guru' ? 'selected' : '' }}>Guru</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control-ios w-100" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-ios">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control-ios w-100" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control-ios w-100" required>
                            </div>

                            <!-- Guru Fields -->
                            <div class="col-md-6 guru-fields" style="display: none;">
                                <label class="form-label-ios">NIP</label>
                                <input type="text" name="nip" class="form-control-ios w-100" value="{{ old('nip') }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>

                    {{-- Info: untuk siswa --}}
                    <div style="margin-top: 20px; background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-info-circle-fill" style="color: #2563eb; font-size: 16px;"></i>
                        <div style="font-size: 13px; color: #1e3a5f; font-weight: 500;">
                            Untuk menambah akun <strong>Siswa</strong>, gunakan menu <a href="{{ route('admin.siswa.create') }}" style="color: #2563eb; font-weight: 700;">Tambah Siswa</a> atau <a href="{{ route('admin.import-siswa.index') }}" style="color: #2563eb; font-weight: 700;">Import Siswa</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleFields() {
    const role = document.getElementById('roleSelect').value;
    document.querySelectorAll('.guru-fields').forEach(el => el.style.display = role === 'guru' ? 'block' : 'none');
}
toggleFields();
</script>
@endpush
@endsection
