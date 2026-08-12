@extends('layouts.app')
@section('title', 'Status Peserta – ' . $ujian->nama_ujian)
@section('page-title', 'Status Peserta')
@section('page-subtitle', $ujian->nama_ujian)

@section('content')
<div class="fade-in">

    {{-- Back Button --}}
    <a href="{{ route('status-peserta.index') }}" class="btn btn-ios btn-ios-light mb-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-ios text-center" style="padding:16px;">
                <div style="font-size:28px;font-weight:800;color:#0f172a;">{{ $stats['total'] }}</div>
                <div style="font-size:12px;color:#64748b;font-weight:600;">Total Peserta</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-ios text-center" style="padding:16px;border-left:4px solid #94a3b8;">
                <div style="font-size:28px;font-weight:800;color:#64748b;">{{ $stats['belum'] }}</div>
                <div style="font-size:12px;color:#64748b;font-weight:600;">Belum Mulai</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-ios text-center" style="padding:16px;border-left:4px solid #f59e0b;">
                <div style="font-size:28px;font-weight:800;color:#d97706;">{{ $stats['sedang'] }}</div>
                <div style="font-size:12px;color:#64748b;font-weight:600;">Sedang Mengerjakan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-ios text-center" style="padding:16px;border-left:4px solid #22c55e;">
                <div style="font-size:28px;font-weight:800;color:#16a34a;">{{ $stats['selesai'] }}</div>
                <div style="font-size:12px;color:#64748b;font-weight:600;">Selesai</div>
            </div>
        </div>
    </div>

    {{-- Progress --}}
    @php
        $pctSelesai = $stats['total'] > 0 ? round(($stats['selesai'] / $stats['total']) * 100) : 0;
        $pctSedang = $stats['total'] > 0 ? round(($stats['sedang'] / $stats['total']) * 100) : 0;
    @endphp
    <div class="card-ios mb-4">
        <div class="card-body" style="padding:16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span style="font-size:13px;font-weight:600;color:#64748b;">Progress Pengerjaan</span>
                <span style="font-size:14px;font-weight:800;">{{ $pctSelesai }}% selesai</span>
            </div>
            <div style="height:12px;background:var(--bg-secondary);border-radius:8px;overflow:hidden;display:flex;">
                <div style="width:{{ $pctSelesai }}%;background:linear-gradient(90deg,#22c55e,#16a34a);transition:width 0.5s;border-radius:8px 0 0 8px;"></div>
                <div style="width:{{ $pctSedang }}%;background:linear-gradient(90deg,#f59e0b,#d97706);transition:width 0.5s;"></div>
            </div>
        </div>
    </div>

    {{-- Ujian Info --}}
    <div class="card-ios mb-4" style="border-left:4px solid var(--primary);">
        <div class="card-body" style="padding:14px 20px;font-size:13px;">
            <div class="d-flex flex-wrap gap-3">
                <span><strong>Mapel:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</span>
                <span><strong>Jenis:</strong> {{ ucfirst($ujian->jenis_ujian) }}</span>
                <span><strong>Durasi:</strong> {{ $ujian->durasi_menit }} menit</span>
                @if($ujian->sesiUjian)
                <span><strong>Sesi:</strong> {{ $ujian->sesiUjian->nama_sesi }} ({{ substr($ujian->sesiUjian->jam_mulai, 0, 5) }}–{{ substr($ujian->sesiUjian->jam_selesai, 0, 5) }})</span>
                @endif
                <span><strong>Status:</strong> <span class="badge-ios {{ $ujian->status=='publish'?'success':($ujian->status=='selesai'?'info':'secondary') }}">{{ ucfirst($ujian->status) }}</span></span>
            </div>
        </div>
    </div>

    @unless($ujian->isActive())
    <div class="alert alert-warning alert-ios" style="font-size:13px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Jadwal ujian ini sedang tidak aktif (di luar rentang tanggal mulai–selesai). Reset peserta tetap bisa dilakukan,
        tapi siswa baru bisa login lagi setelah jadwal ujian diperpanjang lewat menu Ujian.
    </div>
    @endunless

    {{-- Filter --}}
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <form method="GET" action="{{ route('status-peserta.show', $ujian) }}" class="d-flex gap-2 flex-grow-1 flex-wrap">
            <div class="position-relative" style="max-width:280px;flex:1;">
                <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" class="form-control-ios w-100" placeholder="Cari nama siswa..." value="{{ request('search') }}" style="padding-left:40px;">
            </div>
            <select name="filter_status" class="form-select-ios" style="width:auto;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="belum" {{ request('filter_status')=='belum'?'selected':'' }}>Belum Mulai</option>
                <option value="sedang" {{ request('filter_status')=='sedang'?'selected':'' }}>Sedang Mengerjakan</option>
                <option value="selesai" {{ request('filter_status')=='selesai'?'selected':'' }}>Selesai</option>
            </select>
            <button type="submit" class="btn btn-ios btn-ios-primary btn-ios-sm"><i class="bi bi-search"></i></button>
            @if(request('search') || request('filter_status'))
            <a href="{{ route('status-peserta.show', $ujian) }}" class="btn btn-ios btn-ios-light btn-ios-sm"><i class="bi bi-x-lg"></i> Reset</a>
            @endif
        </form>
    </div>

    {{-- Peserta Table --}}
    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Waktu Mulai</th>
                        <th style="text-align:center;">Waktu Selesai</th>
                        <th style="text-align:center;">Durasi</th>
                        <th style="text-align:center;">Terjawab</th>
                        <th style="text-align:center;">Nilai</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pesertaList as $i => $peserta)
                    @php
                        $durasi = null;
                        if ($peserta->waktu_mulai && $peserta->waktu_selesai) {
                            $durasi = $peserta->waktu_mulai->diffInMinutes($peserta->waktu_selesai);
                        } elseif ($peserta->waktu_mulai && $peserta->status == 'sedang') {
                            $durasi = $peserta->waktu_mulai->diffInMinutes(now());
                        }
                    @endphp
                    <tr>
                        <td style="text-align:center;color:#94a3b8;font-weight:600;">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $peserta->siswa->nama ?? '-' }}</div>
                            <small style="color:#94a3b8;">{{ $peserta->siswa->nisn ?? $peserta->siswa->nis ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge-ios secondary" style="font-size:11px;">{{ $peserta->siswa->kelas->nama_kelas ?? '-' }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($peserta->status == 'selesai')
                                <span class="badge-ios success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                            @elseif($peserta->status == 'sedang')
                                <span class="badge-ios warning"><i class="bi bi-clock-fill me-1"></i>Sedang</span>
                            @else
                                <span class="badge-ios secondary"><i class="bi bi-dash-circle me-1"></i>Belum</span>
                            @endif
                        </td>
                        <td style="text-align:center;font-size:12px;">
                            {{ $peserta->waktu_mulai ? $peserta->waktu_mulai->format('H:i:s') : '—' }}
                        </td>
                        <td style="text-align:center;font-size:12px;">
                            {{ $peserta->waktu_selesai ? $peserta->waktu_selesai->format('H:i:s') : '—' }}
                        </td>
                        <td style="text-align:center;font-size:12px;">
                            @if($durasi !== null)
                                <span style="font-weight:600;">{{ $durasi }} mnt</span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align:center;font-size:12px;">
                            @if(in_array($peserta->status, ['sedang', 'selesai']))
                                {{ $peserta->menjawab_count }} / {{ $ujian->jumlah_soal }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($peserta->status == 'selesai' && $peserta->nilai !== null)
                                <span style="font-weight:800;font-size:15px;color:{{ $peserta->nilai >= 70 ? '#16a34a' : '#dc2626' }};">
                                    {{ number_format($peserta->nilai, 0) }}
                                </span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if(in_array($peserta->status, ['sedang', 'selesai']))
                                <button type="button" class="btn btn-ios btn-ios-sm btn-ios-warning"
                                        title="Reset peserta (kendala saat ujian)"
                                        onclick="openResetModal({{ $peserta->id }}, '{{ addslashes($peserta->siswa->nama ?? '-') }}', '{{ $peserta->status }}', {{ $peserta->menjawab_count }})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="bi bi-people" style="font-size:2rem;color:#cbd5e1;"></i>
                            <p style="color:#94a3b8;font-size:13px;margin-top:8px;">Belum ada peserta untuk ujian ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legend --}}
    <div style="margin-top:12px;font-size:12px;color:#94a3b8;">
        Menampilkan {{ $pesertaList->count() }} peserta
        @if(request('filter_status'))
        (filter: {{ request('filter_status') }})
        @endif
    </div>
</div>

{{-- Reset Peserta Modal --}}
<div class="modal fade modal-ios" id="resetModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset Peserta Ujian</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" id="resetForm">
            @csrf
            <div class="modal-body">
                <p>
                    Reset <strong id="resetNamaSiswa"></strong> (status saat ini: <span id="resetStatusSekarang"></span>,
                    sudah menjawab <strong id="resetMenjawab"></strong> soal).
                </p>
                <div class="alert alert-info alert-ios" style="font-size:12px;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Semua jawaban yang sudah tersimpan <strong>tidak akan dihapus</strong> — siswa melanjutkan persis
                    dari posisi terakhir dia mengerjakan. Status dikembalikan ke "Sedang" dan siswa bisa login lagi.
                </div>
                <div class="mb-3">
                    <label class="form-label-ios">Sisa Waktu Baru (menit)</label>
                    <input type="number" name="menit" id="resetMenit" class="form-control-ios w-100" min="1" max="{{ $ujian->durasi_menit }}" value="{{ $ujian->durasi_menit }}" required>
                    <small class="text-muted">Maks. {{ $ujian->durasi_menit }} menit (durasi ujian ini).</small>
                </div>
                <div class="mb-2">
                    <label class="form-label-ios">Catatan Kendala (opsional)</label>
                    <textarea name="catatan" class="form-control-ios w-100" rows="2" placeholder="mis. Listrik padam, koneksi terputus, dsb."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-ios btn-ios-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Peserta
                </button>
            </div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
function openResetModal(pesertaId, namaSiswa, status, menjawab) {
    document.getElementById('resetForm').action = "{{ url('status-peserta/'.$ujian->id.'/peserta') }}/" + pesertaId + "/reset";
    document.getElementById('resetNamaSiswa').textContent = namaSiswa;
    document.getElementById('resetStatusSekarang').textContent = status === 'selesai' ? 'Selesai' : 'Sedang Mengerjakan';
    document.getElementById('resetMenjawab').textContent = menjawab;
    document.getElementById('resetMenit').value = {{ $ujian->durasi_menit }};
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>
@endpush
@endsection
