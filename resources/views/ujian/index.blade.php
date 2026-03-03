@extends('layouts.app')
@section('title', 'Manajemen Ujian')
@section('page-title', 'Ujian')
@section('page-subtitle', 'Kelola ujian')

@section('content')
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control-ios" placeholder="Cari ujian..." value="{{ request('search') }}" style="width: 200px;">
            <select name="status" class="form-select-ios" style="width: 140px;">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="publish" {{ request('status') == 'publish' ? 'selected' : '' }}>Publish</option>
                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
            <button type="submit" class="btn btn-ios btn-ios-light"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('ujian.create') }}" class="btn btn-ios btn-ios-primary"><i class="bi bi-plus-lg"></i> Buat Ujian</a>
    </div>

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Nama Ujian</th><th>Mapel</th><th>Durasi</th><th>Soal</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($ujians as $ujian)
                    <tr>
                        <td>
                            <strong>{{ $ujian->nama_ujian }}</strong>
                            <br><small class="text-muted">{{ ucfirst($ujian->jenis_ujian) }} • oleh {{ $ujian->guru->nama ?? '-' }}</small>
                        </td>
                        <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $ujian->durasi_menit }} menit</td>
                        <td>{{ $ujian->jumlah_soal }}</td>
                        <td><small>{{ $ujian->tanggal_mulai->format('d M Y H:i') }}<br>{{ $ujian->tanggal_selesai->format('d M Y H:i') }}</small></td>
                        <td>
                            @if($ujian->status === 'draft')<span class="badge-ios secondary">Draft</span>
                            @elseif($ujian->status === 'publish')<span class="badge-ios success">Publish</span>
                            @elseif($ujian->status === 'berlangsung')<span class="badge-ios warning">Berlangsung</span>
                            @else<span class="badge-ios info">Selesai</span>@endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('ujian.show', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-light" title="Detail"><i class="bi bi-eye"></i></a>
                                @if($ujian->status === 'draft')
                                    <form action="{{ route('ujian.publish', $ujian) }}" method="POST" class="d-inline">@csrf @method('PATCH')
                                        <button class="btn btn-ios btn-ios-sm btn-ios-success" title="Publish"><i class="bi bi-send-fill"></i></button>
                                    </form>
                                @endif
                                @if($ujian->status === 'publish' || $ujian->status === 'berlangsung')
                                    <a href="{{ route('ujian.monitoring', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-warning" title="Monitor"><i class="bi bi-broadcast"></i></a>
                                @endif
                                <a href="{{ route('ujian.hasil', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-primary" title="Hasil"><i class="bi bi-graph-up"></i></a>
                                <a href="{{ route('ujian.edit', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('ujian.destroy', $ujian) }}" method="POST" onsubmit="return confirm('Yakin hapus?')">@csrf @method('DELETE')
                                    <button class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-pencil-square"></i><h5>Belum ada ujian</h5></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $ujians->withQueryString()->links() }}</div>
</div>
@endsection
