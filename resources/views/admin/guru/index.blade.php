@extends('layouts.app')
@section('title', 'Data Guru')
@section('page-title', 'Data Guru')
@section('page-subtitle', 'Kelola data guru dan mata pelajaran yang diampu')

@section('content')
<div class="fade-in">

    @if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #166534; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Toolbar --}}
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <div class="position-relative flex-grow-1" style="max-width:340px;">
                <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" class="form-control-ios w-100" placeholder="Cari nama / NIP..." value="{{ request('search') }}" style="padding-left:40px;">
            </div>
            <button type="submit" class="btn btn-ios btn-ios-primary btn-ios-sm"><i class="bi bi-search"></i></button>
        </form>
        <button class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Guru
        </button>
    </div>

    {{-- Tabel --}}
    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Nama Guru</th>
                        <th>NIP</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Mata Pelajaran</th>
                        <th style="text-align:center;width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gurus as $i => $guru)
                    <tr>
                        <td style="text-align:center;color:#94a3b8;font-weight:600;">{{ $gurus->firstItem() + $i }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $guru->nama }}</div>
                        </td>
                        <td><span style="font-size:12px;color:#64748b;">{{ $guru->nip ?? '-' }}</span></td>
                        <td><span style="font-size:12px;">{{ $guru->user->email ?? '-' }}</span></td>
                        <td><span style="font-size:12px;">{{ $guru->telepon ?? '-' }}</span></td>
                        <td>
                            @if($guru->mapels->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($guru->mapels as $m)
                                    <span class="badge-ios info" style="font-size:10px;">{{ $m->nama_mapel }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:#cbd5e1;font-size:12px;">Belum diatur</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-ios btn-ios-sm btn-ios-light" title="Edit"
                                    data-bs-toggle="modal" data-bs-target="#modalEdit{{ $guru->id }}">
                                    <i class="bi bi-pencil-fill" style="color:#f59e0b;"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.guru.destroy', $guru) }}" onsubmit="return confirm('Hapus guru {{ $guru->nama }}? Data akun juga akan ikut terhapus!')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ios btn-ios-sm btn-ios-light" title="Hapus">
                                        <i class="bi bi-trash-fill" style="color:#ef4444;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-people" style="font-size:2.5rem;color:#cbd5e1;"></i>
                            <p style="color:#94a3b8;font-size:13px;margin-top:8px;">Belum ada data guru.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($gurus->hasPages())
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $gurus->links() }}</div>
    @endif
</div>

{{-- ===================== MODAL TAMBAH ===================== --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
            <div style="background:linear-gradient(135deg,#1e293b,#334155);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#16a34a,#15803d);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <h5 style="font-size:16px;font-weight:700;color:white;margin:0;">Tambah Guru</h5>
                    <small style="color:#94a3b8;font-size:12px;">Buat akun guru beserta mata pelajaran yang diampu</small>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.guru.store') }}">
                @csrf
                <div style="padding:24px;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-ios">Nama Guru *</label>
                            <input type="text" name="nama" class="form-control-ios w-100" required placeholder="Nama lengkap">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">NIP</label>
                            <input type="text" name="nip" class="form-control-ios w-100" placeholder="NIP (opsional)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Email *</label>
                            <input type="email" name="email" class="form-control-ios w-100" required placeholder="email@sekolah.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Telepon</label>
                            <input type="text" name="telepon" class="form-control-ios w-100" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-12">
                            <label class="form-label-ios">Mata Pelajaran yang Diampu</label>
                            <div style="max-height:200px;overflow-y:auto;background:var(--bg-secondary);border-radius:12px;padding:12px 16px;">
                                @foreach($mapels as $mapel)
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 0;font-size:13px;">
                                    <input type="checkbox" name="mapel_ids[]" value="{{ $mapel->id }}" style="width:16px;height:16px;">
                                    <span style="font-weight:500;">{{ $mapel->nama_mapel }}</span>
                                    <span style="color:#94a3b8;font-size:11px;">({{ $mapel->kode_mapel }})</span>
                                </label>
                                @endforeach
                                @if($mapels->isEmpty())
                                <p style="color:#94a3b8;font-size:12px;margin:0;">Belum ada mata pelajaran.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid var(--border-color);display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== MODAL EDIT PER GURU ===================== --}}
@foreach($gurus as $guru)
<div class="modal fade" id="modalEdit{{ $guru->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
            <div style="background:linear-gradient(135deg,#1e293b,#334155);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;">
                    <i class="bi bi-pencil-fill"></i>
                </div>
                <div>
                    <h5 style="font-size:16px;font-weight:700;color:white;margin:0;">Edit Guru</h5>
                    <small style="color:#94a3b8;font-size:12px;">{{ $guru->nama }}</small>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.guru.update', $guru) }}">
                @csrf @method('PUT')
                <div style="padding:24px;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-ios">Nama Guru *</label>
                            <input type="text" name="nama" class="form-control-ios w-100" required value="{{ $guru->nama }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">NIP</label>
                            <input type="text" name="nip" class="form-control-ios w-100" value="{{ $guru->nip }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Telepon</label>
                            <input type="text" name="telepon" class="form-control-ios w-100" value="{{ $guru->telepon }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label-ios">Mata Pelajaran yang Diampu</label>
                            <div style="max-height:200px;overflow-y:auto;background:var(--bg-secondary);border-radius:12px;padding:12px 16px;">
                                @php $guruMapelIds = $guru->mapels->pluck('id')->toArray(); @endphp
                                @foreach($mapels as $mapel)
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 0;font-size:13px;">
                                    <input type="checkbox" name="mapel_ids[]" value="{{ $mapel->id }}" {{ in_array($mapel->id, $guruMapelIds) ? 'checked' : '' }} style="width:16px;height:16px;">
                                    <span style="font-weight:500;">{{ $mapel->nama_mapel }}</span>
                                    <span style="color:#94a3b8;font-size:11px;">({{ $mapel->kode_mapel }})</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid var(--border-color);display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
