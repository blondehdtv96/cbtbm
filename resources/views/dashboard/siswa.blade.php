@extends('layouts.app')

@section('title', 'Siswa Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@push('styles')
<link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="student-dashboard fade-in">

    @if($pelanggaranAktif->count() > 0)
    <div class="sd-violation-stack" role="alert">
        @foreach($pelanggaranAktif as $i => $pelanggaran)
        <div class="sd-violation-card" style="--sd-vc-delay: {{ $i * 90 }}ms;">
            <div class="sd-violation-icon">
                <span class="sd-violation-ring"></span>
                <i class="bi bi-shield-exclamation"></i>
            </div>
            <div class="sd-violation-content">
                <div class="sd-violation-head">
                    <span class="sd-violation-tag">Pelanggaran Anti-Cheat</span>
                    <span class="sd-violation-time">
                        <i class="bi bi-clock-history"></i>
                        {{ $pelanggaran->violated_at?->format('d M Y, H:i') }}
                    </span>
                </div>
                <p class="sd-violation-title">{{ $pelanggaran->ujian->nama_ujian ?? 'Ujian' }} dihentikan otomatis</p>
                <p class="sd-violation-body">{{ $pelanggaran->violation_detail }}</p>
                <div class="sd-violation-footer">
                    <i class="bi bi-lock-fill"></i>
                    Menunggu peninjauan admin/pengawas &bull; jawaban Anda tetap tersimpan aman
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($siswa)
    @php
        $nilaiVisible = $riwayatUjian->filter(fn($r) => $r->ujian && $r->ujian->tampilkan_nilai);
        $rataRata = $nilaiVisible->count() > 0 ? round($nilaiVisible->avg('nilai')) : null;
    @endphp
    <div class="sd-hero">
        <span class="sd-hero-blob one"></span>
        <span class="sd-hero-blob two"></span>

        <div class="sd-hero-top">
            <div class="sd-avatar">{{ strtoupper(substr($siswa->nama, 0, 2)) }}</div>
            <div>
                <p class="sd-hero-name">{{ $siswa->nama }}</p>
                <p class="sd-hero-meta">NIS {{ $siswa->nis }} &bull; {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
            </div>
        </div>

        <div class="sd-hero-stats">
            <div class="sd-stat-pill">
                <span class="val">{{ $ujianTersedia->count() }}</span>
                <span class="lbl">Tersedia</span>
            </div>
            <div class="sd-stat-pill">
                <span class="val">{{ $riwayatUjian->count() }}</span>
                <span class="lbl">Selesai</span>
            </div>
            <div class="sd-stat-pill">
                <span class="val">{{ $rataRata ?? '-' }}</span>
                <span class="lbl">Rata&#8209;rata</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Ujian Tersedia -->
    <div class="sd-section-title">
        <span class="sd-section-icon warn"><i class="bi bi-lightning-charge-fill"></i></span>
        Ujian Tersedia
        <span class="sd-section-count">{{ $ujianTersedia->count() }}</span>
    </div>

    @if($ujianTersedia->count() > 0)
        <div class="sd-exam-grid">
            @foreach($ujianTersedia as $ujian)
                @php
                    $totalWindow = $ujian->tanggal_mulai->diffInSeconds($ujian->tanggal_selesai) ?: 1;
                    $elapsed = min($totalWindow, max(0, $ujian->tanggal_mulai->diffInSeconds(now())));
                    $elapsedPct = min(100, round(($elapsed / $totalWindow) * 100));
                @endphp
                <div class="sd-exam-card">
                    <div class="sd-exam-head">
                        <div class="sd-mapel-badge"><i class="bi bi-journal-bookmark-fill"></i></div>
                        <span class="sd-live-tag"><span class="sd-live-dot"></span> LIVE</span>
                    </div>

                    <h6 class="sd-exam-title">{{ $ujian->nama_ujian }}</h6>
                    <p class="sd-exam-mapel"><i class="bi bi-book me-1"></i>{{ $ujian->mapel->nama_mapel ?? '-' }}</p>

                    <div class="sd-countdown" data-countdown data-end="{{ $ujian->tanggal_selesai->toIso8601String() }}">
                        <i class="bi bi-hourglass-split"></i>
                        <span class="sd-countdown-time">--:--:--</span>
                        <span class="sd-countdown-label">sisa waktu</span>
                    </div>

                    <div class="sd-metrics">
                        <div class="sd-metric"><i class="bi bi-clock"></i> {{ $ujian->durasi_menit }} menit</div>
                        <div class="sd-metric"><i class="bi bi-list-ol"></i> {{ $ujian->jumlah_soal }} soal</div>
                    </div>

                    <div class="sd-progress-track">
                        <div class="sd-progress-fill" style="width: {{ $elapsedPct }}%;"></div>
                    </div>

                    <a href="{{ route('exam.start', $ujian) }}" class="sd-btn-start">
                        <i class="bi bi-play-fill"></i> Mulai Ujian
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="sd-empty">
            <i class="bi bi-calendar-check"></i>
            <h6>Tidak ada ujian tersedia</h6>
            <p>Ujian yang dijadwalkan akan muncul di sini</p>
        </div>
    @endif

    <!-- Riwayat Ujian -->
    <div class="sd-section-title">
        <span class="sd-section-icon"><i class="bi bi-clock-history"></i></span>
        Riwayat Ujian
        <span class="sd-section-count">{{ $riwayatUjian->count() }}</span>
    </div>

    @if($riwayatUjian->count() > 0)
        <div class="sd-history-list">
            @foreach($riwayatUjian as $riwayat)
                @php
                    $showNilai = $riwayat->ujian && $riwayat->ujian->tampilkan_nilai;
                    $nilai = $riwayat->nilai ?? 0;
                    $ringColor = $nilai >= 75 ? '#14B8A6' : ($nilai >= 50 ? '#FACC15' : '#EF4444');
                @endphp
                <div class="sd-history-card">
                    @if($showNilai)
                        <div class="sd-ring" style="--pct: {{ $nilai }}; --ring-color: {{ $ringColor }};">
                            <div class="sd-ring-inner">{{ $nilai }}</div>
                        </div>
                    @else
                        <div class="sd-ring" style="--pct: 100; --ring-color: #cbd5e1;">
                            <div class="sd-ring-inner"><i class="bi bi-eye-slash"></i></div>
                        </div>
                    @endif

                    <div class="sd-history-body">
                        <p class="sd-history-title">{{ $riwayat->ujian->nama_ujian ?? '-' }}</p>
                        <div class="sd-history-sub">
                            <span>{{ $riwayat->ujian->mapel->nama_mapel ?? '-' }}</span>
                            <span class="sd-dot-sep"></span>
                            <span class="sd-history-date">{{ $riwayat->waktu_selesai ? $riwayat->waktu_selesai->format('d M Y, H:i') : '-' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sd-empty">
            <i class="bi bi-inbox"></i>
            <h6>Belum ada riwayat ujian</h6>
            <p>Riwayat ujian yang sudah dikerjakan akan muncul di sini</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const studentRules = @json($studentRules);

        async function enforceStudentRules() {
            while (studentRules.show) {
                await window.AppPopup.document(studentRules.content, {
                    title: studentRules.title + ' • Versi ' + studentRules.version,
                    type: 'info',
                    okText: 'Saya Mengerti dan Menyetujui',
                    scrollText: 'Gulir dan Baca Sampai Selesai',
                });

                try {
                    const response = await fetch(@json(route('siswa.rules.acknowledge')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({}),
                    });
                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Persetujuan belum dapat disimpan.');
                    }

                    studentRules.show = false;
                } catch (error) {
                    await window.AppPopup.alert(
                        'Persetujuan peraturan gagal disimpan. Periksa koneksi lalu coba kembali.\n\n' + error.message,
                        { title: 'Gagal Menyimpan Persetujuan', type: 'danger', okText: 'Coba Lagi' }
                    );
                }
            }
        }

        if (studentRules.show) {
            enforceStudentRules();
        }

        function pad(n) { return String(n).padStart(2, '0'); }

        function tick() {
            document.querySelectorAll('[data-countdown]').forEach(function (el) {
                var end = new Date(el.getAttribute('data-end')).getTime();
                var now = Date.now();
                var diff = Math.max(0, end - now);
                var timeEl = el.querySelector('.sd-countdown-time');

                if (diff <= 0) {
                    timeEl.textContent = 'Selesai';
                    return;
                }

                var h = Math.floor(diff / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                timeEl.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            });
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>
@endpush
