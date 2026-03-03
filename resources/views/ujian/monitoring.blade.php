@extends('layouts.app')
@section('title', 'Monitoring Ujian')
@section('page-title', 'Monitoring: ' . $ujian->nama_ujian)

@section('content')
<div class="fade-in">
    <a href="{{ route('ujian.show', $ujian) }}" class="btn btn-ios btn-ios-light mb-4"><i class="bi bi-arrow-left"></i> Kembali</a>

    @php
        $belum = $ujian->pesertaUjians->where('status', 'belum')->count();
        $sedang = $ujian->pesertaUjians->where('status', 'sedang')->count();
        $selesai = $ujian->pesertaUjians->where('status', 'selesai')->count();
        $total = $ujian->pesertaUjians->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><div class="stat-card primary"><div class="stat-value">{{ $total }}</div><div class="stat-label">Total Peserta</div></div></div>
        <div class="col-sm-3"><div class="stat-card secondary" style="--stat-before: #94a3b8;"><div class="stat-value">{{ $belum }}</div><div class="stat-label">Belum Mulai</div></div></div>
        <div class="col-sm-3"><div class="stat-card warning"><div class="stat-value">{{ $sedang }}</div><div class="stat-label">Sedang Mengerjakan</div></div></div>
        <div class="col-sm-3"><div class="stat-card success"><div class="stat-value">{{ $selesai }}</div><div class="stat-label">Selesai</div></div></div>
    </div>

    <div class="progress-ios mb-4" style="height: 12px;">
        <div class="progress-bar" style="width: {{ $total > 0 ? round(($selesai / $total) * 100) : 0 }}%"></div>
    </div>

    <div class="card-ios">
        <div class="card-header"><i class="bi bi-broadcast me-2"></i>Status Peserta</div>
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Nama</th><th>NIS</th><th>Status</th><th>Waktu Mulai</th><th>Nilai</th></tr></thead>
                <tbody>
                    @foreach($ujian->pesertaUjians->sortByDesc('status') as $p)
                    <tr>
                        <td><strong>{{ $p->siswa->nama ?? '-' }}</strong></td>
                        <td>{{ $p->siswa->nis ?? '-' }}</td>
                        <td>
                            @if($p->status === 'sedang')
                                <span class="badge-ios warning"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Mengerjakan</span>
                            @elseif($p->status === 'selesai')
                                <span class="badge-ios success">Selesai</span>
                            @else
                                <span class="badge-ios secondary">Belum Mulai</span>
                            @endif
                        </td>
                        <td><small>{{ $p->waktu_mulai ? $p->waktu_mulai->format('H:i:s') : '-' }}</small></td>
                        <td>{{ $p->status === 'selesai' ? $p->nilai : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
