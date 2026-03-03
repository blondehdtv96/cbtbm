@extends('layouts.app')
@section('title', 'Sesi Ujian')
@section('page-title', 'Sesi Ujian')
@section('page-subtitle', 'Kelola sesi dan jam ujian')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Tambah Sesi</button>
    </div>

    <div class="card-ios">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-fill me-2" style="color:var(--primary);"></i>Daftar Sesi Ujian</span>
            <span class="badge-ios primary">{{ $sesiList->total() }} sesi</span>
        </div>
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Sesi</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Durasi</th>
                        <th>Ujian</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sesiList as $i => $sesi)
                    @php
                        $mulai = \Carbon\Carbon::parse($sesi->jam_mulai);
                        $selesai = \Carbon\Carbon::parse($sesi->jam_selesai);
                        $durasi = $mulai->diffInMinutes($selesai);
                    @endphp
                    <tr>
                        <td>{{ $sesiList->firstItem() + $i }}</td>
                        <td><strong>{{ $sesi->nama_sesi }}</strong></td>
                        <td>
                            <span style="background:var(--bg-secondary);padding:4px 10px;border-radius:8px;font-weight:600;font-size:13px;">
                                <i class="bi bi-clock" style="color:var(--primary);"></i>
                                {{ substr($sesi->jam_mulai, 0, 5) }}
                            </span>
                        </td>
                        <td>
                            <span style="background:var(--bg-secondary);padding:4px 10px;border-radius:8px;font-weight:600;font-size:13px;">
                                <i class="bi bi-clock-fill" style="color:var(--primary);"></i>
                                {{ substr($sesi->jam_selesai, 0, 5) }}
                            </span>
                        </td>
                        <td><span class="badge-ios info">{{ $durasi }} menit</span></td>
                        <td><span class="badge-ios purple">{{ $sesi->ujians_count }} ujian</span></td>
                        <td><span class="badge-ios {{ $sesi->is_active ? 'success' : 'danger' }}">{{ $sesi->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-ios btn-ios-sm btn-ios-light" onclick="editSesi({{ json_encode($sesi) }})" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.sesi.destroy', $sesi) }}" method="POST" onsubmit="return confirm('Yakin hapus sesi ini?')">@csrf @method('DELETE')<button class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-inbox"></i> Belum ada sesi ujian. Klik "Tambah Sesi" untuk membuat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sesiList->hasPages())
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $sesiList->links() }}</div>
    @endif

    {{-- Info --}}
    <div class="card-ios mt-4" style="border-left: 4px solid var(--primary);">
        <div class="card-body" style="font-size:13px;">
            <i class="bi bi-info-circle-fill me-2" style="color:var(--primary);"></i>
            <strong>Petunjuk:</strong> Atur jam sesi ujian sesuai jadwal sekolah. Sesi yang aktif akan muncul sebagai pilihan saat guru membuat ujian.
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade modal-ios" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Sesi Ujian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('admin.sesi.store') }}">@csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label-ios">Nama Sesi *</label>
                    <input type="text" name="nama_sesi" class="form-control-ios w-100" placeholder="contoh: Sesi 1" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label-ios">Jam Mulai *</label>
                        <input type="time" name="jam_mulai" class="form-control-ios w-100" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label-ios">Jam Selesai *</label>
                        <input type="time" name="jam_selesai" class="form-control-ios w-100" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-ios btn-ios-primary">Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade modal-ios" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Sesi Ujian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" id="editForm">@csrf @method('PUT')
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label-ios">Nama Sesi *</label>
                    <input type="text" name="nama_sesi" id="edit_nama_sesi" class="form-control-ios w-100" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label-ios">Jam Mulai *</label>
                        <input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-control-ios w-100" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label-ios">Jam Selesai *</label>
                        <input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-control-ios w-100" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="edit_is_active">
                        <span class="form-label-ios mb-0">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-ios btn-ios-primary">Update</button>
            </div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
function editSesi(s) {
    document.getElementById('editForm').action = '{{ url("admin/sesi") }}/' + s.id;
    document.getElementById('edit_nama_sesi').value = s.nama_sesi;
    document.getElementById('edit_jam_mulai').value = s.jam_mulai ? s.jam_mulai.substring(0, 5) : '';
    document.getElementById('edit_jam_selesai').value = s.jam_selesai ? s.jam_selesai.substring(0, 5) : '';
    document.getElementById('edit_is_active').checked = s.is_active == 1;
}
</script>
@endpush
@endsection
