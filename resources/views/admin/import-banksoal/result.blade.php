@extends('layouts.app')

@section('title', 'Hasil Import Soal')
@section('page-title', 'Hasil Import Soal')
@section('page-subtitle', 'Import soal berhasil dilakukan')

@section('content')
<div class="fade-in">
    {{-- Success Banner --}}
    <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(16, 185, 129, 0.08)); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 18px; padding: 28px 32px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #22c55e, #10b981); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="bi bi-check-lg" style="font-size: 28px; color: #fff;"></i>
            </div>
            <div>
                <div style="font-size: 20px; font-weight: 800; color: #166534;">Import Berhasil! 🎉</div>
                <div style="font-size: 14px; color: #15803d; font-weight: 500; margin-top: 4px;">
                    <strong>{{ $successCount }}</strong> soal berhasil diimport ke Bank Soal
                </div>
            </div>
        </div>
    </div>

    {{-- Imported Data --}}
    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-list-check me-2"></i>Soal yang Diimport</span>
            <span class="badge-ios success">{{ $successCount }} soal</span>
        </div>
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 60px;">ID</th>
                        <th>Pertanyaan</th>
                        <th>Mapel</th>
                        <th>Tipe</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($importedSoals as $idx => $soal)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><code style="font-weight: 700;">#{{ $soal['id'] }}</code></td>
                        <td style="font-weight: 500;">{{ $soal['pertanyaan'] }}</td>
                        <td><span class="badge-ios primary">{{ $soal['mapel'] }}</span></td>
                        <td><span class="badge-ios {{ $soal['tipe_soal'] === 'essay' ? 'info' : 'purple' }}">{{ strtoupper($soal['tipe_soal']) }}</span></td>
                        <td>
                            <span class="badge-ios {{ $soal['tingkat'] == 'mudah' ? 'success' : ($soal['tingkat'] == 'sedang' ? 'warning' : 'danger') }}">
                                {{ ucfirst($soal['tingkat']) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <a href="{{ route('banksoal.index') }}" class="btn btn-ios btn-ios-primary">
            <i class="bi bi-database-fill me-1"></i> Lihat Bank Soal
        </a>
        <a href="{{ route('admin.import-banksoal.index') }}" class="btn btn-ios btn-ios-light">
            <i class="bi bi-cloud-arrow-up me-1"></i> Import Lagi
        </a>
    </div>
</div>
@endsection
