@extends('layouts.app')

@section('title', 'Preview Import Soal')
@section('page-title', 'Preview Import Soal')
@section('page-subtitle', 'Verifikasi soal sebelum import ke database')

@section('content')
<div class="fade-in">
    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-list-ol"></i></div>
                <div class="stat-value">{{ count($previewData) }}</div>
                <div class="stat-label">Total Soal</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-value">{{ $validCount }}</div>
                <div class="stat-label">Soal Valid</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="stat-value">{{ $errorCount }}</div>
                <div class="stat-label">Soal Bermasalah</div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-table me-2"></i>Preview Soal</span>
            <div class="d-flex align-items-center gap-2">
                @if($errorCount > 0)
                    <span class="badge-ios warning" style="font-size: 12px;">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ $errorCount }} soal bermasalah
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table-ios" style="min-width: 900px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th style="width: 70px;">Sheet</th>
                        <th style="width: 70px;">Status</th>
                        <th style="width: 80px;">Mapel</th>
                        <th style="width: 65px;">Tipe</th>
                        <th style="width: 50px;">Bobot</th>
                        <th style="width: 35%;">Pertanyaan</th>
                        <th style="width: 60px;">Jawaban</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewData as $idx => $row)
                    <tr style="{{ !$row['valid'] ? 'background: rgba(239, 68, 68, 0.04);' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            @if($row['tipe_soal'] === 'essay')
                                <span class="badge-ios info" style="font-size: 10px;">Essay</span>
                            @else
                                <span class="badge-ios purple" style="font-size: 10px;">PG</span>
                            @endif
                        </td>
                        <td>
                            @if($row['valid'])
                                <span class="badge-ios success"><i class="bi bi-check-lg"></i> Valid</span>
                            @else
                                <span class="badge-ios danger"><i class="bi bi-x-lg"></i> Error</span>
                            @endif
                        </td>
                        <td>
                            @if($row['mapel'])
                                <span class="badge-ios primary">{{ $row['mapel']->kode_mapel }}</span>
                            @else
                                <span style="color: #ef4444; font-size: 12px;">{{ $row['kode_mapel'] ?: '-' }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-ios {{ $row['tipe_soal'] === 'essay' ? 'info' : 'purple' }}" style="font-size: 10px;">
                                {{ strtoupper($row['tipe_soal']) }}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $row['bobot_nilai'] }}</td>
                        <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;">
                                {{ \Str::limit($row['pertanyaan'], 80) ?: '-' }}
                            </div>
                            @if($row['tipe_soal'] !== 'essay' && !empty($row['opsi']))
                                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    @foreach($row['opsi'] as $opsi)
                                        <span style="{{ $opsi['is_correct'] ? 'color: #16a34a; font-weight: 700;' : '' }}">
                                            {{ $opsi['label'] }}: {{ \Str::limit($opsi['isi'], 15) }}
                                        </span>{{ !$loop->last ? ' | ' : '' }}
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($row['tipe_soal'] !== 'essay')
                                @if(!empty($row['jawaban_benar']) && $row['jawaban_benar'] !== '-')
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: rgba(34, 197, 94, 0.1); color: #16a34a; font-weight: 800; font-size: 13px;">
                                        {{ $row['jawaban_benar'] }}
                                    </span>
                                @else
                                    <span style="color: #ef4444;">-</span>
                                @endif
                            @else
                                <span style="font-size: 11px; color: #64748b;">-</span>
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
                                <strong>{{ $errorCount }}</strong> soal bermasalah. Anda bisa <strong>lewati soal bermasalah</strong> atau upload ulang file yang sudah diperbaiki.
                            </div>
                        </div>
                    @else
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.15); border-radius: 12px; padding: 12px 16px;">
                            <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 18px;"></i>
                            <div style="font-size: 13px; font-weight: 500; color: #166534;">
                                Semua data valid! <strong>{{ $validCount }}</strong> soal siap diimport.
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.import-banksoal.index') }}" class="btn btn-ios btn-ios-light">
                        <i class="bi bi-arrow-left"></i> Upload Ulang
                    </a>

                    @if($validCount > 0)
                        <form method="POST" action="{{ route('admin.import-banksoal.import') }}" id="importForm">
                            @csrf
                            @if($errorCount > 0)
                                <input type="hidden" name="skip_errors" value="1">
                                <button type="submit" class="btn btn-ios btn-ios-warning"
                                    onclick="return confirm('Import {{ $validCount }} soal valid dan lewati {{ $errorCount }} soal bermasalah?')">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Import {{ $validCount }} Soal Valid
                                </button>
                            @else
                                <button type="submit" class="btn btn-ios btn-ios-primary"
                                    onclick="return confirm('Import {{ $validCount }} soal ke Bank Soal?')">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Import {{ $validCount }} Soal
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
