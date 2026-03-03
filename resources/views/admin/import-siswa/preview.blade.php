@extends('layouts.app')

@section('title', 'Preview Import Siswa')
@section('page-title', 'Preview Import Siswa')
@section('page-subtitle', 'Verifikasi data sebelum import')

@section('content')
<div class="fade-in">
    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-list-ol"></i></div>
                <div class="stat-value">{{ count($previewData) }}</div>
                <div class="stat-label">Total Baris</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-value">{{ $validCount }}</div>
                <div class="stat-label">Data Valid</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="stat-value">{{ $errorCount }}</div>
                <div class="stat-label">Data Bermasalah</div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-table me-2"></i>Data Preview</span>
            <div class="d-flex align-items-center gap-2">
                @if($errorCount > 0)
                    <span class="badge-ios warning" style="font-size: 12px;">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ $errorCount }} baris bermasalah
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table-ios" style="min-width: 700px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Status</th>
                        <th>NISN</th>
                        <th>NIS</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewData as $row)
                    <tr style="{{ !$row['valid'] ? 'background: rgba(239, 68, 68, 0.04);' : '' }}">
                        <td>{{ $row['row'] }}</td>
                        <td>
                            @if($row['valid'])
                                <span class="badge-ios success"><i class="bi bi-check-lg"></i> Valid</span>
                            @else
                                <span class="badge-ios danger"><i class="bi bi-x-lg"></i> Error</span>
                            @endif
                        </td>
                        <td>
                            <code style="font-weight: 600;">{{ $row['nisn'] ?: '-' }}</code>
                        </td>
                        <td>
                            <code>{{ $row['nis'] ?: '-' }}</code>
                        </td>
                        <td style="font-weight: 600;">{{ $row['nama'] ?: '-' }}</td>
                        <td>
                            @if($row['kelas'])
                                <span class="badge-ios primary">{{ $row['kelas']->nama_kelas }}</span>
                            @else
                                <span style="color: #ef4444; font-size: 12px;">{{ $row['kelas_input'] ?: '-' }}</span>
                            @endif
                        </td>
                        <td>
                            @if(!$row['valid'])
                                @foreach($row['errors'] as $err)
                                    <div style="font-size: 11px; color: #dc2626; font-weight: 500; line-height: 1.6;">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $err }}
                                    </div>
                                @endforeach
                            @else
                                <span style="font-size: 11px; color: #16a34a;"><i class="bi bi-check-circle-fill me-1"></i>Siap import</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card-ios mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    @if($errorCount > 0)
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: 12px; padding: 12px 16px;">
                            <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b; font-size: 18px;"></i>
                            <div style="font-size: 13px; font-weight: 500; color: #92400e;">
                                <strong>{{ $errorCount }}</strong> baris bermasalah. Anda bisa <strong>lewati baris bermasalah</strong> atau upload ulang file yang sudah diperbaiki.
                            </div>
                        </div>
                    @else
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.15); border-radius: 12px; padding: 12px 16px;">
                            <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 18px;"></i>
                            <div style="font-size: 13px; font-weight: 500; color: #166534;">
                                Semua data valid! <strong>{{ $validCount }}</strong> siswa siap diimport.
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.import-siswa.index') }}" class="btn btn-ios btn-ios-light">
                        <i class="bi bi-arrow-left"></i> Upload Ulang
                    </a>

                    @if($validCount > 0)
                        <form method="POST" action="{{ route('admin.import-siswa.import') }}" id="importForm">
                            @csrf
                            @if($errorCount > 0)
                                <input type="hidden" name="skip_errors" value="1">
                                <button type="submit" class="btn btn-ios btn-ios-warning"
                                    onclick="return confirm('Import {{ $validCount }} data valid dan lewati {{ $errorCount }} baris bermasalah?')">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Import {{ $validCount }} Data Valid
                                </button>
                            @else
                                <button type="submit" class="btn btn-ios btn-ios-primary"
                                    onclick="return confirm('Import {{ $validCount }} siswa ke database?')">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Import {{ $validCount }} Siswa
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
