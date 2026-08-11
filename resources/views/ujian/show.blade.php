@extends('layouts.app')
@section('title', 'Detail Ujian')
@section('page-title', $ujian->nama_ujian)

@section('content')
<div class="fade-in">
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light"><i class="bi bi-arrow-left"></i> Kembali</a>
        @if($ujian->status === 'draft')
            <form action="{{ route('ujian.publish', $ujian) }}" method="POST">@csrf @method('PATCH')
                <button class="btn btn-ios btn-ios-success"><i class="bi bi-send-fill"></i> Publish</button>
            </form>
        @endif
        <a href="{{ route('ujian.hasil', $ujian) }}" class="btn btn-ios btn-ios-primary"><i class="bi bi-graph-up"></i> Hasil</a>
        <a href="{{ route('kartu-peserta.preview', $ujian) }}" class="btn btn-ios btn-ios-light ms-auto" style="border:1.5px solid var(--primary);color:var(--primary);">
            <i class="bi bi-credit-card-2-front-fill"></i> Cetak Kartu Peserta
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-ios mb-3">
                <div class="card-header">Informasi Ujian</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6"><small class="text-muted">Jenis</small><div style="font-weight: 600;">{{ ucfirst($ujian->jenis_ujian) }}</div></div>
                        <div class="col-6"><small class="text-muted">Mapel</small><div style="font-weight: 600;">{{ $ujian->mapel->nama_mapel ?? '-' }}</div></div>
                        <div class="col-6"><small class="text-muted">Durasi</small><div style="font-weight: 600;">{{ $ujian->durasi_menit }} menit</div></div>
                        <div class="col-6"><small class="text-muted">Jumlah Soal</small><div style="font-weight: 600;">{{ $ujian->jumlah_soal }}</div></div>
                        <div class="col-6"><small class="text-muted">Mulai</small><div style="font-weight: 600;">{{ $ujian->tanggal_mulai->format('d M Y H:i') }}</div></div>
                        <div class="col-6"><small class="text-muted">Selesai</small><div style="font-weight: 600;">{{ $ujian->tanggal_selesai->format('d M Y H:i') }}</div></div>
                        <div class="col-6"><small class="text-muted">Token</small><div style="font-weight: 700; font-size: 18px; color: var(--primary);">{{ $ujian->token }}</div></div>
                        <div class="col-6"><small class="text-muted">Status</small><div><span class="badge-ios {{ $ujian->status === 'publish' ? 'success' : ($ujian->status === 'draft' ? 'secondary' : 'info') }}">{{ ucfirst($ujian->status) }}</span></div></div>
                    </div>
                </div>
            </div>

            <div class="card-ios">
                <div class="card-header">Soal Terpilih ({{ $ujian->bankSoals->count() }})</div>
                <div class="card-body p-0">
                    <table class="table-ios">
                        <thead><tr><th>#</th><th>Pertanyaan</th><th>Tipe</th></tr></thead>
                        <tbody>
                            @foreach($ujian->bankSoals as $i => $soal)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ Str::limit($soal->pertanyaan, 60) }}</td>
                                <td><span class="badge-ios purple">{{ strtoupper($soal->tipe_soal) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-ios mb-3">
                <div class="card-header">Kelas Peserta</div>
                <div class="card-body">
                    @foreach($ujian->kelasList as $kelas)
                        <span class="badge-ios primary mb-1">{{ $kelas->nama_kelas }}</span>
                    @endforeach
                </div>
            </div>

            <div class="card-ios">
                <div class="card-header">Peserta ({{ $ujian->pesertaUjians->count() }})</div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @foreach($ujian->pesertaUjians as $p)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-weight: 600; font-size: 13px;">{{ $p->siswa->nama ?? '-' }}</div>
                                <small class="text-muted">{{ $p->siswa->nis ?? '' }}</small>
                            </div>
                            <span class="badge-ios {{ $p->status === 'selesai' ? 'success' : ($p->status === 'sedang' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($p->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
