@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Edit data pengguna')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-pencil-fill me-2"></i>Edit User: {{ $user->name }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-ios">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control-ios w-100" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Email</label>
                                <input type="email" name="email" class="form-control-ios w-100" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                <input type="password" name="password" class="form-control-ios w-100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Role</label>
                                <select name="role" class="form-select-ios w-100" required>
                                    <option value="superadmin" {{ $user->role == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                    <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="guru" {{ $user->role == 'guru' ? 'selected' : '' }}>Guru</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-ios">Status</label>
                                <select name="is_active" class="form-select-ios w-100">
                                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>

                            @if($user->role === 'guru' && $user->guru)
                            <div class="col-md-6">
                                <label class="form-label-ios">NIP</label>
                                <input type="text" name="nip" class="form-control-ios w-100" value="{{ old('nip', $user->guru->nip) }}">
                            </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Update</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
