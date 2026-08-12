@extends('layouts.app')

@section('title', 'Monitoring Sistem')
@section('page-title', 'Monitoring Sistem')
@section('page-subtitle', 'Pemantauan penggunaan aplikasi dan aktivitas pengguna secara real-time')

@section('content')
<div class="fade-in">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-broadcast"></i></div>
                <div class="stat-value">{{ $stats['online_now'] }}</div>
                <div class="stat-label">Online Sekarang</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="stat-value">{{ $stats['logins_today'] }}</div>
                <div class="stat-label">Login Hari Ini</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="stat-value">{{ $stats['ujian_berlangsung'] }}</div>
                <div class="stat-label">Ujian Berlangsung</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-activity"></i></div>
                <div class="stat-value">{{ $stats['activities_today'] }}</div>
                <div class="stat-label">Aktivitas Hari Ini</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Trend Chart --}}
        <div class="col-lg-7">
            <div class="card-ios h-100">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i>Tren Login 7 Hari Terakhir
                </div>
                <div class="card-body">
                    <canvas id="loginTrendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- System Info --}}
        <div class="col-lg-5">
            <div class="card-ios h-100">
                <div class="card-header">
                    <i class="bi bi-cpu-fill me-2"></i>Info Sistem
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; margin-bottom: 16px;">
                        <div><span style="color: var(--text-muted);">PHP</span><br><strong>{{ $system['php_version'] }}</strong></div>
                        <div><span style="color: var(--text-muted);">Laravel</span><br><strong>{{ $system['laravel_version'] }}</strong></div>
                        <div><span style="color: var(--text-muted);">Cache Driver</span><br><strong>{{ $system['cache_driver'] }}</strong></div>
                        <div><span style="color: var(--text-muted);">Queue Driver</span><br><strong>{{ $system['queue_driver'] }}</strong></div>
                        <div><span style="color: var(--text-muted);">Session Driver</span><br><strong>{{ $system['session_driver'] }}</strong></div>
                        <div><span style="color: var(--text-muted);">Ukuran Database</span><br><strong>{{ $system['db_size_human'] }}</strong></div>
                    </div>

                    <div style="font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Disk Storage</span>
                        <span>{{ $diskUsage['used_human'] }} / {{ $diskUsage['total_human'] }}</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 6px;">
                        <div class="progress-bar {{ $diskUsage['used_percent'] > 85 ? 'bg-danger' : ($diskUsage['used_percent'] > 60 ? 'bg-warning' : 'bg-success') }}"
                             role="progressbar" style="width: {{ $diskUsage['used_percent'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Most Active Users --}}
    @if($mostActive->count() > 0)
    <div class="card-ios mb-4">
        <div class="card-header">
            <i class="bi bi-trophy-fill me-2"></i>Pengguna Paling Aktif (30 Hari Terakhir)
        </div>
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Jumlah Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mostActive as $entry)
                    <tr>
                        <td style="font-weight: 600;">{{ $entry->user->name ?? 'Pengguna dihapus' }}</td>
                        <td><span class="badge-ios secondary">{{ ucfirst($entry->user->role ?? '-') }}</span></td>
                        <td>{{ number_format($entry->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div class="card-ios mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label-ios">Cari Pengguna</label>
                        <input type="text" name="search" class="form-control-ios w-100" placeholder="Nama..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-ios">Modul</label>
                        <select name="module" class="form-control-ios w-100">
                            <option value="">Semua</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-ios">Aksi</label>
                        <select name="action" class="form-control-ios w-100">
                            <option value="">Semua</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-ios">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control-ios w-100" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-ios">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control-ios w-100" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-ios btn-ios-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Activity Table --}}
    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-list-check me-2"></i>Aktivitas Terbaru</span>
            <span class="badge-ios primary">{{ $logs->total() }} record</span>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Belum Ada Aktivitas</h5>
                    <p>Tidak ada aktivitas yang cocok dengan filter.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table-ios">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Modul</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; font-size: 13px;">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td><span class="badge-ios secondary">{{ $log->module }}</span></td>
                                <td><span class="badge-ios primary">{{ $log->action }}</span></td>
                                <td style="font-size: 13px;">{{ $log->description }}</td>
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
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('loginTrendChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(collect($trend)->pluck('date')) !!},
        datasets: [{
            label: 'Login',
            data: {!! json_encode(collect($trend)->pluck('total')) !!},
            backgroundColor: 'rgba(37, 99, 235, 0.6)',
            borderRadius: 6,
            maxBarThickness: 36,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
@endpush
@endsection
