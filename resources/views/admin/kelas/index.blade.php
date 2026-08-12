@extends('layouts.app')
@section('title', 'Manajemen Kelas')
@section('page-title', 'Manajemen Kelas')
@section('page-subtitle', 'Kelola data kelas')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Tambah Kelas</button>
    </div>

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Nama Kelas</th><th>Jurusan</th><th>Tingkat</th><th>Siswa</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($kelasList as $kelas)
                    <tr>
                        <td><strong>{{ $kelas->nama_kelas }}</strong></td>
                        <td>{{ $kelas->jurusan->nama_jurusan ?? '-' }}</td>
                        <td><span class="badge-ios primary">Kelas {{ $kelas->tingkat }}</span></td>
                        <td>{{ $kelas->siswas_count }} siswa</td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-ios btn-ios-sm btn-ios-light" onclick="editKelas({{ json_encode($kelas) }})" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.kelas.destroy', $kelas) }}" method="POST" onsubmit="return confirm('Yakin?')">@csrf @method('DELETE')<button class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button></form>
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
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $kelasList->links() }}</div>
</div>

<!-- Add Modal -->
<div class="modal fade modal-ios" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('admin.kelas.store') }}">@csrf
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Kelas</label><input type="text" name="nama_kelas" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Jurusan</label><select name="jurusan_id" class="form-select-ios w-100" required>
                    <option value="">Pilih</option>@foreach($jurusans as $j)<option value="{{ $j->id }}">{{ $j->nama_jurusan }}</option>@endforeach
                </select></div>
                <div class="mb-3"><label class="form-label-ios">Tingkat</label><select name="tingkat" class="form-select-ios w-100" required>
                    <option value="10">10</option><option value="11">11</option><option value="12">12</option>
                </select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade modal-ios" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" id="editForm">@csrf @method('PUT')
            <div class="modal-body">
                <div class="mb-3"><label class="form-label-ios">Nama Kelas</label><input type="text" name="nama_kelas" id="edit_nama_kelas" class="form-control-ios w-100" required></div>
                <div class="mb-3"><label class="form-label-ios">Jurusan</label><select name="jurusan_id" id="edit_jurusan_id" class="form-select-ios w-100" required>
                    @foreach($jurusans as $j)<option value="{{ $j->id }}">{{ $j->nama_jurusan }}</option>@endforeach
                </select></div>
                <div class="mb-3"><label class="form-label-ios">Tingkat</label><select name="tingkat" id="edit_tingkat" class="form-select-ios w-100" required>
                    <option value="10">10</option><option value="11">11</option><option value="12">12</option>
                </select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-ios btn-ios-primary">Update</button></div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
function editKelas(k) {
    document.getElementById('editForm').action = "{{ route('admin.kelas.update', ['kelas' => '__ID__']) }}".replace('__ID__', k.id);
    document.getElementById('edit_nama_kelas').value = k.nama_kelas;
    document.getElementById('edit_jurusan_id').value = k.jurusan_id;
    document.getElementById('edit_tingkat').value = k.tingkat;
}
</script>
@endpush
@endsection
