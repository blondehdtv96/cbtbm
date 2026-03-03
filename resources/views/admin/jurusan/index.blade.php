@extends('layouts.app')
@section('title', 'Manajemen Jurusan')
@section('page-title', 'Manajemen Jurusan')
@section('page-subtitle', 'Kelola data jurusan')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Tambah Jurusan
        </button>
    </div>

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Kode</th><th>Nama Jurusan</th><th>Jumlah Kelas</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($jurusans as $jurusan)
                    <tr>
                        <td><span class="badge-ios primary">{{ $jurusan->kode_jurusan }}</span></td>
                        <td><strong>{{ $jurusan->nama_jurusan }}</strong></td>
                        <td>{{ $jurusan->kelas_count }}</td>
                        <td><span class="badge-ios {{ $jurusan->is_active ? 'success' : 'danger' }}">{{ $jurusan->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-ios btn-ios-sm btn-ios-light" onclick="editJurusan({{ json_encode($jurusan) }})" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.jurusan.destroy', $jurusan) }}" method="POST" onsubmit="return confirm('Yakin?')">@csrf @method('DELETE')<button class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $jurusans->links() }}</div>
</div>

<!-- Add Modal -->
<div class="modal fade modal-ios" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Jurusan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('admin.jurusan.store') }}">@csrf
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Jurusan</label><input type="text" name="nama_jurusan" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Kode Jurusan</label><input type="text" name="kode_jurusan" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Deskripsi</label><textarea name="deskripsi" class="form-control-ios w-100" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade modal-ios" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Jurusan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" id="editForm">@csrf @method('PUT')
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Jurusan</label><input type="text" name="nama_jurusan" id="edit_nama" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Kode Jurusan</label><input type="text" name="kode_jurusan" id="edit_kode" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Deskripsi</label><textarea name="deskripsi" id="edit_deskripsi" class="form-control-ios w-100" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Update</button></div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
function editJurusan(j) {
    document.getElementById('editForm').action = '/cbtbm/public/admin/jurusan/' + j.id;
    document.getElementById('edit_nama').value = j.nama_jurusan;
    document.getElementById('edit_kode').value = j.kode_jurusan;
    document.getElementById('edit_deskripsi').value = j.deskripsi || '';
}
</script>
@endpush
@endsection
