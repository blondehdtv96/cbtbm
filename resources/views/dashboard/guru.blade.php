@extends('layouts.app')

@section('title', 'Guru Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('content')
<div class="fade-in">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-database-fill"></i></div>
                <div class="stat-value">{{ number_format($totalSoal) }}</div>
                <div class="stat-label">Soal Dibuat</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="stat-value">{{ number_format($totalUjian) }}</div>
                <div class="stat-label">Ujian Dibuat</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-broadcast"></i></div>
                <div class="stat-value">{{ number_format($ujianAktif) }}</div>
                <div class="stat-label">Ujian Aktif</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <a href="{{ route('banksoal.create') }}" class="text-decoration-none">
                <div class="stat-card info" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="margin-bottom: 0;"><i class="bi bi-plus-circle-fill"></i></div>
                        <div>
                            <div style="font-weight: 700; font-size: 16px;">Buat Soal Baru</div>
                            <div class="stat-label" style="margin-top: 0;">Tambahkan soal ke bank soal</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-6">
            <a href="{{ route('ujian.create') }}" class="text-decoration-none">
                <div class="stat-card purple" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="margin-bottom: 0;"><i class="bi bi-calendar-plus-fill"></i></div>
                        <div>
                            <div style="font-weight: 700; font-size: 16px;">Buat Ujian Baru</div>
                            <div class="stat-label" style="margin-top: 0;">Jadwalkan ujian untuk siswa</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card-ios mt-4">
        <div class="card-header"><i class="bi bi-clock-history me-2"></i>Ujian Terbaru Saya</div>
        <div class="card-body p-0">
            @if($recentUjians->count() > 0)
                <table class="table-ios">
                    <thead><tr><th>Nama Ujian</th><th>Mapel</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach($recentUjians as $ujian)
                        <tr>
                            <td><strong>{{ $ujian->nama_ujian }}</strong></td>
                            <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
                            <td><span class="badge-ios {{ $ujian->status === 'publish' ? 'success' : 'secondary' }}">{{ ucfirst($ujian->status) }}</span></td>
                            <td><a href="{{ route('ujian.show', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-light">Detail</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state"><i class="bi bi-inbox"></i><h5>Belum ada ujian</h5></div>
            @endif
        </div>
    </div>
</div>
@endsection
