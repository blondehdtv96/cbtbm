@extends('layouts.app')

@section('title', 'Anti-Cheat Log')
@section('page-title', 'Anti-Cheat Log')
@section('page-subtitle', 'Riwayat pelanggaran selama ujian')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card danger">
            <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
            <div class="stat-value">{{ $totalViolations }}</div>
            <div class="stat-label">Total Pelanggaran</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value">{{ $todayViolations }}</div>
            <div class="stat-label">Hari Ini</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $uniqueStudents }}</div>
            <div class="stat-label">Siswa Terlibat</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card-ios mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.anti-cheat.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label-ios">Cari Siswa</label>
                    <input type="text" name="search" class="form-control-ios w-100" placeholder="Nama siswa..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label-ios">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control-ios w-100" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label-ios">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control-ios w-100" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-ios btn-ios-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card-ios">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-shield-exclamation me-2"></i>Riwayat Pelanggaran</span>
        <span class="badge-ios danger">{{ $logs->total() }} record</span>
    </div>
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="empty-state">
                <i class="bi bi-shield-check"></i>
                <h5>Tidak Ada Pelanggaran</h5>
                <p>Belum ada pelanggaran anti-cheat yang tercatat.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table-ios">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Siswa</th>
                            <th>Ujian</th>
                            <th>Kelas</th>
                            <th>Tipe Pelanggaran</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $index => $log)
                        @php $logData = $log->data ?? []; @endphp
                        <tr>
                            <td>{{ $logs->firstItem() + $index }}</td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px;">{{ $log->created_at->format('d/m/Y') }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $logData['siswa_nama'] ?? ($log->user->name ?? '-') }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $logData['siswa_nisn'] ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge-ios primary">{{ $logData['ujian_nama'] ?? '-' }}</span>
                            </td>
                            <td>{{ $logData['kelas'] ?? '-' }}</td>
                            <td>
                                @php
                                    $vType = $logData['violation_type'] ?? 'tab_switch';
                                    $vLabels = [
                                        'tab_switch' => ['Pindah Tab / Home', 'danger'],
                                        'copy_attempt' => ['Copy Text', 'warning'],
                                        'screenshot' => ['Screenshot', 'warning'],
                                        'devtools' => ['Developer Tools', 'danger'],
                                    ];
                                    $vLabel = $vLabels[$vType] ?? [$vType, 'secondary'];
                                @endphp
                                <span class="badge-ios {{ $vLabel[1] }}">
                                    <i class="bi bi-exclamation-circle-fill"></i> {{ $vLabel[0] }}
                                </span>
                            </td>
                            <td style="font-size: 12px; font-family: monospace;">{{ $log->ip_address }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if($logs->hasPages())
<div class="d-flex justify-content-center mt-4 pagination-ios">
    {{ $logs->links() }}
</div>
@endif
@endsection
