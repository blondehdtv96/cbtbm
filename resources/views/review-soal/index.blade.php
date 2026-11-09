@extends('layouts.app')
@section('title', 'Cek Soal')
@section('page-title', 'Cek Soal')
@section('page-subtitle', 'Periksa kebenaran soal dan kunci jawaban per mata pelajaran')

@section('content')
<div class="fade-in">

    @unless($ready)
        <div class="alert alert-warning" style="border-radius:14px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Fitur cek soal belum aktif di server ini. Minta admin menjalankan <code>php artisan migrate</code>.
        </div>
    @endunless

    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No.</th>
                        <th>Kode Mapel</th>
                        <th>Mata Pelajaran</th>
                        <th style="text-align:center;">Total Soal</th>
                        <th style="text-align:center;">Benar</th>
                        <th style="text-align:center;">Salah</th>
                        <th style="min-width:180px;">Progres Cek</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapels as $idx => $m)
                        @php
                            $dicek = $m->dicek_soal ?? 0;
                            $persen = $m->total_soal > 0 ? round($dicek / $m->total_soal * 100) : 0;
                        @endphp
                        <tr>
                            <td style="text-align:center; font-weight:700; color:#64748b;">{{ $idx + 1 }}.</td>
                            <td>
                                <span style="font-family:'Courier New', monospace; font-weight:800; font-size:13px; color:#2563eb; background:rgba(37,99,235,0.07); padding:4px 10px; border-radius:8px;">{{ $m->kode_mapel }}</span>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0f172a;">{{ $m->nama_mapel }}</div>
                                <small style="color:#94a3b8; font-size:11px;">{{ $m->jurusan->nama_jurusan ?? 'Umum' }}</small>
                            </td>
                            <td style="text-align:center; font-weight:800; color:#0f172a;">{{ $m->total_soal }}</td>
                            <td style="text-align:center;">
                                @if(($m->benar_soal ?? 0) > 0)
                                    <span class="badge-ios success"><i class="bi bi-check-lg"></i> {{ $m->benar_soal }}</span>
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if(($m->salah_soal ?? 0) > 0)
                                    <span class="badge-ios danger"><i class="bi bi-x-lg"></i> {{ $m->salah_soal }}</span>
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex-grow:1; height:8px; background:#f1f5f9; border-radius:99px; overflow:hidden;">
                                        <div style="width:{{ $persen }}%; height:100%; background:linear-gradient(90deg,#22c55e,#10b981);"></div>
                                    </div>
                                    <small style="font-weight:700; color:#64748b; white-space:nowrap;">{{ $dicek }}/{{ $m->total_soal }}</small>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($m->total_soal > 0)
                                    <a href="{{ route('guru.review-soal.show', $m) }}" class="btn btn-ios btn-ios-sm btn-ios-primary">
                                        <i class="bi bi-clipboard-check me-1"></i> Cek Soal
                                    </a>
                                @else
                                    <span style="color:#cbd5e1; font-size:12px;">Belum ada soal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-journal-x"></i>
                                <h5>Belum ada mata pelajaran</h5>
                                <p>Minta admin meng-assign mata pelajaran ke akun Anda melalui menu Data Guru.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
