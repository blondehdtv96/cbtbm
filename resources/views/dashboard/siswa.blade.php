@extends('layouts.app')

@section('title', 'Siswa Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('content')
<div class="fade-in">
    @if($siswa)
    <div class="card-ios mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar" style="width: 56px; height: 56px; border-radius: 16px; font-size: 20px;">
                    {{ strtoupper(substr($siswa->nama, 0, 2)) }}
                </div>
                <div>
                    <h5 style="font-weight: 700; margin: 0;">{{ $siswa->nama }}</h5>
                    <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">
                        NIS: {{ $siswa->nis }} • {{ $siswa->kelas->nama_kelas ?? '' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Available Exams -->
    <h5 style="font-weight: 700; margin-bottom: 16px;">
        <i class="bi bi-lightning-fill text-warning me-2"></i>Ujian Tersedia
    </h5>

    @if($ujianTersedia->count() > 0)
        <div class="row g-3 mb-4">
            @foreach($ujianTersedia as $ujian)
                <div class="col-md-6 col-lg-4">
                    <div class="card-ios" style="border-left: 4px solid var(--primary);">
                        <div class="card-body">
                            <h6 style="font-weight: 700; margin-bottom: 8px;">{{ $ujian->nama_ujian }}</h6>
                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                                <div><i class="bi bi-book me-1"></i> {{ $ujian->mapel->nama_mapel ?? '-' }}</div>
                                <div><i class="bi bi-clock me-1"></i> {{ $ujian->durasi_menit }} menit</div>
                                <div><i class="bi bi-list-ol me-1"></i> {{ $ujian->jumlah_soal }} soal</div>
                                <div><i class="bi bi-calendar me-1"></i> {{ $ujian->tanggal_selesai->format('d M Y H:i') }}</div>
                            </div>
                            <a href="{{ route('exam.start', $ujian) }}" class="btn btn-ios btn-ios-primary w-100">
                                <i class="bi bi-play-fill"></i> Mulai Ujian
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card-ios mb-4">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-calendar-check"></i>
                    <h5>Tidak ada ujian tersedia</h5>
                    <p>Ujian yang dijadwalkan akan muncul di sini</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Exam History -->
    <h5 style="font-weight: 700; margin-bottom: 16px;">
        <i class="bi bi-clock-history me-2"></i>Riwayat Ujian
    </h5>

    <div class="card-ios">
        <div class="card-body p-0">
            @if($riwayatUjian->count() > 0)
                <table class="table-ios">
                    <thead>
                        <tr>
                            <th>Ujian</th>
                            <th>Mapel</th>
                            <th>Nilai</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayatUjian as $riwayat)
                        <tr>
                            <td><strong>{{ $riwayat->ujian->nama_ujian ?? '-' }}</strong></td>
                            <td>{{ $riwayat->ujian->mapel->nama_mapel ?? '-' }}</td>
                            <td>
                                @if($riwayat->ujian && $riwayat->ujian->tampilkan_nilai)
                                    <span class="badge-ios {{ $riwayat->nilai >= 75 ? 'success' : ($riwayat->nilai >= 50 ? 'warning' : 'danger') }}">
                                        {{ $riwayat->nilai }}
                                    </span>
                                @else
                                    <span class="badge-ios secondary">Tersembunyi</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $riwayat->waktu_selesai ? $riwayat->waktu_selesai->format('d M Y H:i') : '-' }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Belum ada riwayat ujian</h5>
                    <p>Riwayat ujian yang sudah dikerjakan akan muncul di sini</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
