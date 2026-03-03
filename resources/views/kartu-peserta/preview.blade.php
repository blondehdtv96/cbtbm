@extends('layouts.app')
@section('title', 'Konfirmasi Cetak Kartu Peserta')
@section('page-title', 'Konfirmasi Cetak Kartu')
@section('page-subtitle', $ujian->nama_ujian)

@push('styles')
<style>
.peserta-card-preview {
    background: white;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 12px;
    font-size: 11px;
    position: relative;
    overflow: hidden;
}
.peserta-card-preview .card-header-strip {
    background: linear-gradient(135deg, var(--primary), #6366f1);
    margin: -12px -12px 10px -12px;
    padding: 10px 12px;
    color: white;
    font-weight: 700;
    font-size: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.peserta-card-preview .card-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), #6366f1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}
.check-all-box {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.peserta-row:hover {
    background: var(--bg-secondary) !important;
}
</style>
@endpush

@section('content')
<div class="fade-in">

    {{-- Back + Info --}}
    <div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
        <a href="{{ route('kartu-peserta.index') }}" class="btn btn-ios btn-ios-light">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <div class="ms-auto d-flex gap-2">
            <button type="button" id="btnCetakTerpilih" class="btn btn-ios btn-ios-success" onclick="submitPrint()">
                <i class="bi bi-printer-fill"></i> Cetak Kartu Terpilih
            </button>
            <a href="{{ route('kartu-peserta.print', $ujian) }}" class="btn btn-ios btn-ios-primary" target="_blank">
                <i class="bi bi-printer"></i> Cetak Semua
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT: Info Ujian + Daftar Peserta --}}
        <div class="col-lg-8">
            {{-- Info Ujian --}}
            <div class="card-ios mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle-fill me-2" style="color:var(--primary);"></i>Informasi Ujian
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Nama Ujian</small>
                            <div style="font-weight:700;">{{ $ujian->nama_ujian }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Mata Pelajaran</small>
                            <div style="font-weight:600;">{{ $ujian->mapel->nama_mapel ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tanggal Mulai</small>
                            <div style="font-weight:600;">{{ $ujian->tanggal_mulai->format('d M Y, H:i') }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tanggal Selesai</small>
                            <div style="font-weight:600;">{{ $ujian->tanggal_selesai->format('d M Y, H:i') }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Durasi</small>
                            <div style="font-weight:600;">{{ $ujian->durasi_menit }} menit</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Token Ujian</small>
                            <div style="font-weight:700;font-size:18px;color:var(--primary);letter-spacing:2px;">
                                {{ $ujian->token }}
                            </div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Kelas Peserta</small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($ujian->kelasList as $kelas)
                                    <span class="badge-ios primary">{{ $kelas->nama_kelas }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Peserta --}}
            <div class="card-ios">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people-fill me-2" style="color:var(--primary);"></i>
                        Daftar Peserta ({{ $pesertaList->count() }} siswa)
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Pilih semua</small>
                        <input type="checkbox" class="check-all-box" id="checkAll">
                    </div>
                </div>
                <div class="card-body p-0">
                    <form id="formPrint" method="GET" action="">
                        <div class="table-responsive">
                            <table class="table-ios">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th>#</th>
                                        <th>Nama Siswa</th>
                                        <th>NIS / NISN</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pesertaList as $i => $peserta)
                                    <tr class="peserta-row">
                                        <td class="text-center">
                                            <input type="checkbox" name="peserta_ids[]"
                                                value="{{ $peserta->id }}"
                                                class="check-box check-all-box"
                                                checked>
                                        </td>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div style="font-weight:600;">{{ $peserta->siswa->nama }}</div>
                                        </td>
                                        <td>
                                            <div style="font-size:13px;">{{ $peserta->siswa->nis ?? '-' }}</div>
                                            <small class="text-muted">{{ $peserta->siswa->nisn ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge-ios primary">
                                                {{ $peserta->siswa->kelas->nama_kelas ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $sc = match($peserta->status) {
                                                    'selesai' => 'success',
                                                    'sedang' => 'warning',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge-ios {{ $sc }}">{{ ucfirst($peserta->status) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox"></i> Tidak ada peserta terdaftar.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Preview Kartu --}}
        <div class="col-lg-4">
            <div class="card-ios">
                <div class="card-header">
                    <i class="bi bi-eye-fill me-2" style="color:var(--primary);"></i>Pratinjau Kartu
                </div>
                <div class="card-body">
                    <p class="text-muted" style="font-size:12px;">Berikut contoh tampilan kartu peserta yang akan dicetak:</p>

                    {{-- Preview Card --}}
                    @if($pesertaList->first())
                    @php $sample = $pesertaList->first()->siswa; @endphp
                    <div class="peserta-card-preview">
                        <div class="card-header-strip">
                            <i class="bi bi-mortarboard-fill me-1"></i> KARTU PESERTA UJIAN
                        </div>
                        <div class="d-flex gap-2 align-items-start mb-2">
                            <div class="card-avatar">
                                {{ strtoupper(substr($sample->nama, 0, 2)) }}
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700;font-size:12px;line-height:1.3;">{{ $sample->nama }}</div>
                                <div style="color:var(--text-muted);font-size:10px;">{{ $sample->kelas->nama_kelas ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="row g-0" style="font-size:10px;border-top:1px solid var(--border-color);padding-top:8px;">
                            <div class="col-6 mb-1">
                                <span class="text-muted">NIS</span>
                                <div style="font-weight:600;">{{ $sample->nis ?? '-' }}</div>
                            </div>
                            <div class="col-6 mb-1">
                                <span class="text-muted">NISN</span>
                                <div style="font-weight:600;">{{ $sample->nisn ?? '-' }}</div>
                            </div>
                            <div class="col-12 mb-1">
                                <span class="text-muted">Mata Pelajaran</span>
                                <div style="font-weight:600;">{{ $ujian->mapel->nama_mapel ?? '-' }}</div>
                            </div>
                            <div class="col-6 mb-1">
                                <span class="text-muted">Tanggal</span>
                                <div style="font-weight:600;">{{ $ujian->tanggal_mulai->format('d/m/Y') }}</div>
                            </div>
                            <div class="col-6 mb-1">
                                <span class="text-muted">Waktu</span>
                                <div style="font-weight:600;">{{ $ujian->tanggal_mulai->format('H:i') }}</div>
                            </div>
                            <div class="col-12" style="background:linear-gradient(135deg,var(--primary),#6366f1);border-radius:6px;padding:6px 10px;margin-top:6px;">
                                <span style="color:rgba(255,255,255,0.7);font-size:9px;display:block;">TOKEN UJIAN</span>
                                <span style="color:white;font-weight:800;font-size:16px;letter-spacing:3px;">{{ $ujian->token }}</span>
                            </div>
                        </div>
                    </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-credit-card-2-front" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0" style="font-size:12px;">Tidak ada peserta</p>
                        </div>
                    @endif

                    <div class="mt-3 p-3" style="background:var(--bg-secondary);border-radius:10px;">
                        <div style="font-size:12px;color:var(--text-muted);">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Petunjuk:</strong><br>
                            • Centang peserta yang ingin dicetak<br>
                            • Klik <strong>"Cetak Kartu Terpilih"</strong> untuk mencetak pilihan<br>
                            • Klik <strong>"Cetak Semua"</strong> untuk mencetak semua peserta
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Check All toggle
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.check-box').forEach(cb => cb.checked = this.checked);
});

// Update check-all state when individual checkboxes change
document.querySelectorAll('.check-box').forEach(cb => {
    cb.addEventListener('change', function() {
        const all = document.querySelectorAll('.check-box');
        const checked = document.querySelectorAll('.check-box:checked');
        document.getElementById('checkAll').indeterminate = checked.length > 0 && checked.length < all.length;
        document.getElementById('checkAll').checked = checked.length === all.length;
    });
});

function submitPrint() {
    const checked = document.querySelectorAll('.check-box:checked');
    if (checked.length === 0) {
        alert('Pilih minimal satu peserta untuk dicetak!');
        return;
    }

    const ids = Array.from(checked).map(cb => cb.value);
    const baseUrl = '{{ route("kartu-peserta.print", $ujian) }}';
    const params = ids.map(id => `peserta_ids[]=${id}`).join('&');
    window.open(`${baseUrl}?${params}`, '_blank');
}
</script>
@endpush
