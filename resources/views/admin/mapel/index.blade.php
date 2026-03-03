@extends('layouts.app')
@section('title', 'Manajemen Mata Pelajaran')
@section('page-title', 'Mata Pelajaran')
@section('page-subtitle', 'Kelola data mata pelajaran')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Tambah Mapel</button>
    </div>

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Kode</th><th>Nama</th><th>Jurusan</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($mapels as $mapel)
                    <tr>
                        <td><span class="badge-ios info">{{ $mapel->kode_mapel }}</span></td>
                        <td><strong>{{ $mapel->nama_mapel }}</strong></td>
                        <td>{{ $mapel->jurusan->nama_jurusan ?? '-' }}</td>
                        <td><span class="badge-ios {{ $mapel->is_umum ? 'purple' : 'primary' }}">{{ $mapel->is_umum ? 'Umum' : 'Kejuruan' }}</span></td>
                        <td><span class="badge-ios {{ $mapel->is_active ? 'success' : 'danger' }}">{{ $mapel->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-ios btn-ios-sm btn-ios-light" onclick="editMapel({{ json_encode($mapel) }})" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.mapel.destroy', $mapel) }}" method="POST" onsubmit="return confirm('Yakin?')">@csrf @method('DELETE')<button class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button></form>
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
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $mapels->links() }}</div>
</div>

<!-- Add Modal -->
<div class="modal fade modal-ios" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Mata Pelajaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('admin.mapel.store') }}">@csrf
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Mapel</label><input type="text" name="nama_mapel" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Kode Mapel</label><input type="text" name="kode_mapel" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Jurusan (kosongkan jika umum)</label><select name="jurusan_id" class="form-select-ios w-100">
                    <option value="">Umum - Semua Jurusan</option>@foreach($jurusans as $j)<option value="{{ $j->id }}">{{ $j->nama_jurusan }}</option>@endforeach
                </select></div>
                <div class="mb-3">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_umum" value="1"> <span class="form-label-ios mb-0">Mata Pelajaran Umum</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade modal-ios" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Mata Pelajaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" id="editForm">@csrf @method('PUT')
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Mapel</label><input type="text" name="nama_mapel" id="edit_nama_mapel" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Kode Mapel</label><input type="text" name="kode_mapel" id="edit_kode_mapel" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Jurusan</label><select name="jurusan_id" id="edit_mapel_jurusan" class="form-select-ios w-100">
                    <option value="">Umum</option>@foreach($jurusans as $j)<option value="{{ $j->id }}">{{ $j->nama_jurusan }}</option>@endforeach
                </select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Update</button></div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
function editMapel(m) {
    document.getElementById('editForm').action = '/cbtbm/public/admin/mapel/' + m.id;
    document.getElementById('edit_nama_mapel').value = m.nama_mapel;
    document.getElementById('edit_kode_mapel').value = m.kode_mapel;
    document.getElementById('edit_mapel_jurusan').value = m.jurusan_id || '';
}
</script>
@endpush
@endsection
