@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('content')
<div class="fade-in">
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
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-database-fill"></i></div>
                <div class="stat-value">{{ number_format($totalSoal) }}</div>
                <div class="stat-label">Bank Soal</div>
            </div>
        </div>
    </div>

    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-clock-history me-2"></i>Ujian Terbaru</span>
            <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-sm btn-ios-light">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
            @if($recentUjians->count() > 0)
                <table class="table-ios">
                    <thead><tr><th>Nama Ujian</th><th>Mapel</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($recentUjians as $ujian)
                        <tr>
                            <td><strong>{{ $ujian->nama_ujian }}</strong><br><small class="text-muted">{{ $ujian->tanggal_mulai->format('d M Y H:i') }}</small></td>
                            <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
                            <td><span class="badge-ios {{ $ujian->status === 'publish' ? 'success' : ($ujian->status === 'draft' ? 'secondary' : 'info') }}">{{ ucfirst($ujian->status) }}</span></td>
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
