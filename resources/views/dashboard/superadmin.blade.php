@extends('layouts.app')

@section('title', 'Super Admin Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('content')
<div class="fade-in">
    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-value">{{ number_format($totalSiswa) }}</div>
                <div class="stat-label">Total Siswa</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stat-value">{{ number_format($totalGuru) }}</div>
                <div class="stat-label">Total Guru</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="stat-value">{{ number_format($totalUjian) }}</div>
                <div class="stat-label">Total Ujian</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-broadcast"></i></div>
                <div class="stat-value">{{ number_format($ujianAktif) }}</div>
                <div class="stat-label">Ujian Aktif</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-database-fill"></i></div>
                <div class="stat-value">{{ number_format($totalSoal) }}</div>
                <div class="stat-label">Bank Soal</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-door-open-fill"></i></div>
                <div class="stat-value">{{ number_format($totalKelas) }}</div>
                <div class="stat-label">Total Kelas</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-building"></i></div>
                <div class="stat-value">{{ number_format($totalJurusan) }}</div>
                <div class="stat-label">Total Jurusan</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-person-fill"></i></div>
                <div class="stat-value">{{ number_format($totalUser) }}</div>
                <div class="stat-label">Total Pengguna</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Exams -->
        <div class="col-lg-7">
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clock-history me-2"></i>Ujian Terbaru</span>
                    <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-sm btn-ios-light">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    @if($recentUjians->count() > 0)
                        <table class="table-ios">
                            <thead>
                                <tr>
                                    <th>Nama Ujian</th>
                                    <th>Mapel</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentUjians as $ujian)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">{{ $ujian->nama_ujian }}</div>
                                        <small class="text-muted">{{ $ujian->tanggal_mulai->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
                                    <td>
                                        @if($ujian->status === 'draft')
                                            <span class="badge-ios secondary">Draft</span>
                                        @elseif($ujian->status === 'publish')
                                            <span class="badge-ios success">Publish</span>
                                        @elseif($ujian->status === 'selesai')
                                            <span class="badge-ios info">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Belum ada ujian</h5>
                            <p>Ujian yang dibuat akan muncul di sini</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Live Exam Monitoring -->
        <div class="col-lg-5">
            <div class="card-ios">
                <div class="card-header">
                    <i class="bi bi-broadcast me-2"></i>Ujian Berlangsung
                </div>
                <div class="card-body">
                    @if($ujianBerlangsung->count() > 0)
                        @foreach($ujianBerlangsung as $active)
                            <div style="padding: 14px; background: rgba(34, 197, 94, 0.06); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(34, 197, 94, 0.12);">
                                <div style="font-weight: 600; font-size: 14px;">{{ $active->nama_ujian }}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                                    {{ $active->mapel->nama_mapel ?? '' }} •
                                    {{ $active->pesertaUjians->where('status', 'sedang')->count() }} sedang mengerjakan
                                </div>
                                <div class="progress-ios mt-2">
                                    @php
                                        $total = $active->pesertaUjians->count();
                                        $done = $active->pesertaUjians->where('status', 'selesai')->count();
                                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                                    @endphp
                                    <div class="progress-bar" style="width: {{ $pct }}%"></div>
                                </div>
                                <small class="text-muted">{{ $done }}/{{ $total }} selesai ({{ $pct }}%)</small>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-broadcast text-muted" style="font-size: 32px;"></i>
                            <p class="text-muted mt-2 mb-0" style="font-size: 13px;">Tidak ada ujian berlangsung</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
