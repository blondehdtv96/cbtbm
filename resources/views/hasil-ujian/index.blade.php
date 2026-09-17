@extends('layouts.app')
@section('title', 'Hasil Ujian')
@section('page-title', 'Hasil Ujian')
@section('page-subtitle', 'Kelola dan unduh hasil ujian semua siswa dari satu tempat')

@section('content')
<div class="fade-in">

    {{-- Search & Filter --}}
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <form method="GET" class="d-flex gap-2 flex-grow-1 flex-wrap">
            <div class="position-relative flex-grow-1" style="max-width:360px;">
                <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" class="form-control-ios w-100" placeholder="Cari nama ujian..." value="{{ request('search') }}" style="padding-left:40px;">
            </div>
            <select name="mapel_id" class="form-select-ios" style="width:auto;" onchange="this.form.submit()">
                <option value="">Semua Mapel</option>
                @foreach($mapelList as $mapel)
                    <option value="{{ $mapel->id }}" {{ request('mapel_id') == $mapel->id ? 'selected' : '' }}>{{ $mapel->nama_mapel }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-search"></i></button>
            @if(!request()->boolean('semua'))
            <a href="{{ request()->fullUrlWithQuery(['semua' => 1]) }}" class="btn btn-ios btn-ios-light" title="Tampilkan juga ujian yang belum ada peserta selesai">
                Tampilkan Semua Ujian
            </a>
            @else
            <a href="{{ request()->fullUrlWithQuery(['semua' => null]) }}" class="btn btn-ios btn-ios-light">
                Hanya yang Sudah Ada Hasil
            </a>
            @endif
        </form>
    </div>

    {{-- Ujian List --}}
    @forelse($ujians as $ujian)
    @php
        $total = $ujian->peserta_ujians_count;
        $selesai = $ujian->selesai_count;
        $pctSelesai = $total > 0 ? round(($selesai / $total) * 100) : 0;
        $rata = $ujian->rata_rata_nilai;
    @endphp
    <div class="card-ios mb-3" style="overflow:visible;">
        <div class="card-body" style="padding:18px 22px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h6 style="font-weight:700;font-size:15px;margin:0;">{{ $ujian->nama_ujian }}</h6>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">
                        <span>{{ $ujian->mapel->nama_mapel ?? '-' }}</span>
                        @if($ujian->guru)
                        <span class="mx-1">•</span>
                        <span>{{ $ujian->guru->nama ?? '' }}</span>
                        @endif
                        @if($ujian->sesiUjian)
                        <span class="mx-1">•</span>
                        <span>{{ $ujian->sesiUjian->nama_sesi }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    @php
                        $statusColor = match($ujian->status) {
                            'publish' => 'success',
                            'selesai' => 'info',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge-ios {{ $statusColor }}">{{ ucfirst($ujian->status) }}</span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                {{-- Stats --}}
                <div class="d-flex gap-4 flex-wrap" style="font-size:12px;">
                    <div>
                        <span style="color:#64748b;">Selesai: </span>
                        <strong style="color:#0f172a;">{{ $selesai }}/{{ $total }}</strong>
                        <span style="color:#94a3b8;">({{ $pctSelesai }}%)</span>
                    </div>
                    <div>
                        <span style="color:#64748b;">Rata-rata Nilai: </span>
                        @if($rata !== null)
                            <strong style="color:{{ $rata >= 75 ? '#16a34a' : ($rata >= 50 ? '#ca8a04' : '#dc2626') }};">{{ round($rata, 1) }}</strong>
                        @else
                            <strong style="color:#94a3b8;">-</strong>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-ios btn-ios-sm btn-ios-success d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" {{ $selesai === 0 ? 'disabled' : '' }}>
                            <i class="bi bi-download"></i> Download
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Semua Kelas</h6></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('ujian.cetak-nilai', $ujian) }}">
                                    <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel Nilai
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('ujian.nilai-resmi.excel', $ujian) }}">
                                    <i class="bi bi-file-earmark-ruled-fill text-success"></i> Lembar Nilai Resmi (Excel)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('ujian.nilai-resmi.print', $ujian) }}" target="_blank">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Lembar Nilai Resmi (PDF)
                                </a>
                            </li>
                            @if($ujian->kelasList->isNotEmpty())
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Per Kelas</h6></li>
                            @foreach($ujian->kelasList as $kelas)
                            <li>
                                <a class="dropdown-item" href="{{ route('ujian.cetak-nilai', ['ujian' => $ujian->id, 'kelas_id' => $kelas->id]) }}">
                                    <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel — {{ $kelas->nama_kelas }}
                                </a>
                            </li>
                            @endforeach
                            @endif
                        </ul>
                    </div>
                    <a href="{{ route('ujian.hasil', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-primary">
                        <i class="bi bi-eye-fill me-1"></i>Detail
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card-ios">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-bar-graph" style="font-size:3rem;color:#cbd5e1;"></i>
            <h6 style="color:#64748b;margin-top:12px;">Belum ada hasil ujian</h6>
            <p style="color:#94a3b8;font-size:13px;">Hasil akan muncul di sini setelah ada peserta yang menyelesaikan ujian.</p>
        </div>
    </div>
    @endforelse

    @if($ujians->hasPages())
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $ujians->links() }}</div>
    @endif
</div>
@endsection
