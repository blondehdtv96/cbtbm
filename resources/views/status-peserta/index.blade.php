@extends('layouts.app')
@section('title', 'Status Peserta')
@section('page-title', 'Status Peserta')
@section('page-subtitle', 'Monitor pengerjaan ujian peserta secara real-time')

@section('content')
<div class="fade-in">

    {{-- Search --}}
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <div class="position-relative flex-grow-1" style="max-width:360px;">
                <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" class="form-control-ios w-100" placeholder="Cari nama ujian..." value="{{ request('search') }}" style="padding-left:40px;">
            </div>
            <select name="status" class="form-select-ios" style="width:auto;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                <option value="publish" {{ request('status')=='publish'?'selected':'' }}>Publish</option>
                <option value="selesai" {{ request('status')=='selesai'?'selected':'' }}>Selesai</option>
            </select>
            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Ujian List --}}
    @forelse($ujians as $ujian)
    @php
        $total = $ujian->peserta_ujians_count;
        $pctSelesai = $total > 0 ? round(($ujian->selesai_count / $total) * 100) : 0;
        $pctSedang = $total > 0 ? round(($ujian->sedang_count / $total) * 100) : 0;
    @endphp
    <div class="card-ios mb-3" style="overflow:hidden;">
        <div class="card-body" style="padding:18px 22px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h6 style="font-weight:700;font-size:15px;margin:0;">{{ $ujian->nama_ujian }}</h6>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">
                        <span>{{ $ujian->mapel->nama_mapel ?? '-' }}</span>
                        <span class="mx-1">•</span>
                        <span>{{ $ujian->jenis_ujian ? ucfirst($ujian->jenis_ujian) : '' }}</span>
                        @if($ujian->sesiUjian)
                        <span class="mx-1">•</span>
                        <span>{{ $ujian->sesiUjian->nama_sesi }}</span>
                        @endif
                        @if($ujian->guru)
                        <span class="mx-1">•</span>
                        <span>{{ $ujian->guru->nama ?? '' }}</span>
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
                    <a href="{{ route('status-peserta.show', $ujian) }}" class="btn btn-ios btn-ios-sm btn-ios-primary">
                        <i class="bi bi-eye-fill me-1"></i>Detail
                    </a>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px;">
                <div style="flex:1;">
                    <div style="height:8px;background:var(--bg-secondary);border-radius:8px;overflow:hidden;display:flex;">
                        <div style="width:{{ $pctSelesai }}%;background:linear-gradient(90deg,#22c55e,#16a34a);transition:width 0.3s;"></div>
                        <div style="width:{{ $pctSedang }}%;background:linear-gradient(90deg,#f59e0b,#d97706);transition:width 0.3s;"></div>
                    </div>
                </div>
                <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;">{{ $pctSelesai }}%</div>
            </div>

            {{-- Status Badges --}}
            <div class="d-flex gap-3 flex-wrap" style="font-size:12px;">
                <div class="d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block;"></span>
                    <span style="color:#64748b;">Belum: <strong style="color:#0f172a;">{{ $ujian->belum_count }}</strong></span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                    <span style="color:#64748b;">Sedang: <strong style="color:#0f172a;">{{ $ujian->sedang_count }}</strong></span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                    <span style="color:#64748b;">Selesai: <strong style="color:#0f172a;">{{ $ujian->selesai_count }}</strong></span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span style="color:#64748b;">Total: <strong style="color:#0f172a;">{{ $total }}</strong></span>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card-ios">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard2-check" style="font-size:3rem;color:#cbd5e1;"></i>
            <h6 style="color:#64748b;margin-top:12px;">Belum ada ujian</h6>
            <p style="color:#94a3b8;font-size:13px;">Buat ujian terlebih dahulu untuk memantau status peserta.</p>
        </div>
    </div>
    @endforelse

    @if($ujians->hasPages())
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $ujians->links() }}</div>
    @endif
</div>
@endsection
