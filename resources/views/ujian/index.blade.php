@extends('layouts.app')
@section('title', 'Manajemen Ujian')
@section('page-title', 'Ujian')
@section('page-subtitle', 'Kelola ujian berdasarkan jadwal dan sesi pelaksanaan')

@push('styles')
<style>
    .exam-index-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
    .exam-index-filter { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .exam-order-note {
        display:inline-flex; align-items:center; gap:7px; margin-bottom:12px; padding:8px 11px;
        border-radius:10px; color:#1e40af; background:rgba(59,130,246,.07); font-size:10px; font-weight:600;
    }
    .exam-session-badge {
        display:inline-flex; align-items:center; gap:8px; min-width:150px; padding:7px 9px;
        border-radius:10px; border:1px solid transparent;
    }
    .exam-session-badge > i { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; font-size:14px; }
    .exam-session-badge strong { display:block; color:var(--text-primary); font-size:10px; line-height:1.25; }
    .exam-session-badge small { display:block; margin-top:2px; color:var(--text-secondary); font-size:9px; font-weight:650; }
    .exam-session-badge.morning { background:#fffbeb; border-color:#fde68a; }.exam-session-badge.morning>i{color:#d97706;background:#fef3c7}
    .exam-session-badge.noon { background:#fff7ed; border-color:#fed7aa; }.exam-session-badge.noon>i{color:#ea580c;background:#ffedd5}
    .exam-session-badge.afternoon { background:#f5f3ff; border-color:#ddd6fe; }.exam-session-badge.afternoon>i{color:#7c3aed;background:#ede9fe}
    .exam-session-badge.night { background:#eef2ff; border-color:#c7d2fe; }.exam-session-badge.night>i{color:#4338ca;background:#e0e7ff}
    .exam-session-badge.none { background:#f8fafc; border-color:#e2e8f0; }.exam-session-badge.none>i{color:#64748b;background:#f1f5f9}
    .exam-schedule { display:grid; gap:4px; margin-top:7px; color:var(--text-secondary); font-size:9px; }
    .exam-schedule span { display:flex; align-items:center; gap:5px; white-space:nowrap; }
    .exam-schedule i { width:12px; color:var(--primary); text-align:center; }
    .exam-mapel-code { display:block; margin-top:2px; color:var(--text-muted); font-size:9px; font-weight:700; }
    @media(max-width:767.98px){
        .exam-index-filter,.exam-index-filter input,.exam-index-filter select{width:100%!important}
        .exam-index-filter .btn{flex:1}.exam-index-toolbar>a{width:100%;justify-content:center}
    }
</style>
@endpush

@section('content')
<div class="fade-in">
    <div class="exam-index-toolbar">
        <form class="exam-index-filter" method="GET">
            <input type="text" name="search" class="form-control-ios" placeholder="Cari ujian..." value="{{ request('search') }}" style="width:200px;">
            <select name="status" class="form-select-ios" style="width:140px;">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="publish" {{ request('status') === 'publish' ? 'selected' : '' }}>Publish</option>
                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
            <select name="sesi_ujian_id" class="form-select-ios" style="width:190px;">
                <option value="">Semua Sesi</option>
                @foreach($sesiList as $sesi)
                    <option value="{{ $sesi->id }}" {{ (string) request('sesi_ujian_id') === (string) $sesi->id ? 'selected' : '' }}>{{ $sesi->nama_sesi }} · {{ $sesi->jam_format }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-ios btn-ios-light" title="Terapkan Filter"><i class="bi bi-funnel-fill"></i></button>
            @if(request()->hasAny(['search','status','sesi_ujian_id']))
                <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            @endif
        </form>
        <a href="{{ route('ujian.create') }}" class="btn btn-ios btn-ios-primary"><i class="bi bi-plus-lg"></i> Buat Ujian</a>
    </div>

    <div class="exam-order-note"><i class="bi bi-clock-history"></i>Jadwal aktif dan mendatang ditampilkan lebih dahulu, berurutan dari jam paling awal.</div>

    <div class="card-ios">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-ios">
                    <thead><tr><th>Nama Ujian</th><th>Mapel</th><th>Durasi</th><th>Soal</th><th>Sesi & Jadwal</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($ujians as $ujian)
                        @php
                            $sesi = $ujian->sesiUjian;
                            $sessionTheme = 'none'; $sessionIcon = 'bi-calendar2-minus'; $period = 'Tanpa sesi';
                            if ($sesi) {
                                $hour = (int) substr($sesi->jam_mulai, 0, 2);
                                if ($hour < 10) { $sessionTheme='morning'; $sessionIcon='bi-sunrise-fill'; $period='Pagi'; }
                                elseif ($hour < 15) { $sessionTheme='noon'; $sessionIcon='bi-sun-fill'; $period='Siang'; }
                                elseif ($hour < 18) { $sessionTheme='afternoon'; $sessionIcon='bi-sunset-fill'; $period='Sore'; }
                                else { $sessionTheme='night'; $sessionIcon='bi-moon-stars-fill'; $period='Malam'; }
                            }
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $ujian->nama_ujian }}</strong>
                                <br><small class="text-muted">{{ ucfirst($ujian->jenis_ujian) }} • oleh {{ $ujian->guru->nama ?? '-' }}</small>
                            </td>
                            <td>
                                {{ $ujian->mapel->nama_mapel ?? '-' }}
                                @if($ujian->mapel?->kode_mapel)<span class="exam-mapel-code">{{ $ujian->mapel->kode_mapel }}</span>@endif
                            </td>
                            <td><i class="bi bi-hourglass-split me-1 text-primary"></i>{{ $ujian->durasi_menit }} menit</td>
                            <td><i class="bi bi-ui-checks me-1 text-primary"></i>{{ $ujian->jumlah_soal }}</td>
                            <td>
                                <span class="exam-session-badge {{ $sessionTheme }}">
                                    <i class="bi {{ $sessionIcon }}"></i>
                                    <span>
                                        <strong>{{ $sesi->nama_sesi ?? 'Tanpa Sesi' }} · {{ $period }}</strong>
                                        <small>{{ $sesi?->jam_format ?? 'Jam manual' }}</small>
                                    </span>
                                </span>
                                <span class="exam-schedule">
                                    <span><i class="bi bi-play-circle-fill"></i>Mulai {{ $ujian->tanggal_mulai->format('d M Y, H:i') }}</span>
                                    <span><i class="bi bi-stop-circle-fill"></i>Selesai {{ $ujian->tanggal_selesai->format('d M Y, H:i') }}</span>
                                </span>
                            </td>
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
                                    <form action="{{ route('ujian.destroy', $ujian) }}" method="POST" data-confirm="Yakin ingin menghapus ujian ini?" data-confirm-title="Hapus Ujian" data-confirm-ok="Ya, Hapus">@csrf @method('DELETE')
                                        <button class="btn btn-ios btn-ios-sm btn-ios-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-calendar2-week"></i><h5>Belum ada ujian</h5><p>Ubah filter atau buat jadwal ujian baru.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $ujians->withQueryString()->links() }}</div>
</div>
@endsection
