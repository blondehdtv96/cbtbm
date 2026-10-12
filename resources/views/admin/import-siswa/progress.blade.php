@extends('layouts.app')

@section('title', 'Proses Import Siswa')
@section('page-title', 'Proses Import Siswa')
@section('page-subtitle', 'Jangan tutup halaman ini sampai proses selesai')

@section('content')
<div class="fade-in" style="max-width: 760px;">
    <div class="card-ios">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-cloud-arrow-up-fill me-2"></i>Mengimport Data Siswa</span>
            @if($fileName)
                <span class="badge-ios secondary" style="font-size: 12px;">{{ $fileName }}</span>
            @endif
        </div>
        <div class="card-body">
            {{-- Ring progress --}}
            <div style="display: flex; align-items: center; gap: 22px; flex-wrap: wrap;">
                <div style="position: relative; width: 104px; height: 104px; flex: 0 0 auto;">
                    <svg viewBox="0 0 120 120" style="width: 104px; height: 104px; transform: rotate(-90deg);">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(37,99,235,0.12)" stroke-width="12"></circle>
                        <circle id="progressRing" cx="60" cy="60" r="52" fill="none" stroke="url(#progressGradient)"
                            stroke-width="12" stroke-linecap="round" stroke-dasharray="326.7" stroke-dashoffset="326.7"
                            style="transition: stroke-dashoffset .4s ease-out;"></circle>
                        <defs>
                            <linearGradient id="progressGradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#2563eb"></stop>
                                <stop offset="100%" stop-color="#6366f1"></stop>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div id="progressPercent" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 19px; color: var(--primary);">
                        0%
                    </div>
                </div>

                <div style="min-width: 220px; flex: 1 1 240px;">
                    <div id="progressState" style="font-weight: 700; font-size: 15px; color: #1e293b;">
                        Menyiapkan proses import...
                    </div>
                    <div id="progressDetail" style="font-size: 13px; color: var(--text-secondary); margin-top: 6px; line-height: 1.7;">
                        <span id="progressDone">{{ $done }}</span> dari <strong>{{ $total }}</strong> baris diproses
                    </div>
                    <div style="height: 8px; background: rgba(37,99,235,0.1); border-radius: 6px; overflow: hidden; margin-top: 12px;">
                        <div id="progressBar" style="height: 100%; width: 0%; background: linear-gradient(135deg, #2563eb, #6366f1); transition: width .4s ease-out;"></div>
                    </div>
                    <div id="progressCounts" style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                        Akun dibuat: <strong id="countImported">0</strong> &middot; Dilewati: <strong id="countSkipped">0</strong>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div style="display: flex; align-items: flex-start; gap: 10px; background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 12px; padding: 12px 16px; margin-top: 22px;">
                <i class="bi bi-info-circle-fill" style="color: #2563eb; font-size: 16px; margin-top: 1px;"></i>
                <div style="font-size: 12px; color: #334155; line-height: 1.7;">
                    Data dikerjakan <strong>bertahap</strong> agar tidak melebihi batas waktu server
                    (penyebab <em>504 Gateway Time-out</em> pada file berisi ribuan siswa).
                    Biarkan halaman ini terbuka — kalau terputus, proses bisa dilanjutkan dari posisi terakhir
                    lewat halaman <strong>Import Siswa</strong>.
                </div>
            </div>

            {{-- Error --}}
            <div id="progressError" style="display: none; align-items: flex-start; gap: 10px; background: rgba(239, 68, 68, 0.07); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 12px 16px; margin-top: 14px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #dc2626; font-size: 16px; margin-top: 1px;"></i>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 13px; color: #991b1b;">Proses terhenti</div>
                    <div id="progressErrorText" style="font-size: 12px; color: #7f1d1d; margin-top: 4px; line-height: 1.7;"></div>
                    <button type="button" id="retryButton" class="btn btn-ios btn-ios-sm btn-ios-danger mt-2" data-no-loading>
                        <i class="bi bi-arrow-clockwise"></i> Coba Lanjutkan Lagi
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3 flex-wrap">
                <a href="{{ route('admin.import-siswa.index') }}" class="btn btn-ios btn-ios-light" id="backLink" style="display: none;">
                    <i class="bi bi-arrow-left"></i> Kembali ke Import Siswa
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const total = {{ $total }};
    const chunkUrl = @json(route('admin.import-siswa.process-chunk'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const ring = document.getElementById('progressRing');
    const ringLength = 2 * Math.PI * 52;
    const percentEl = document.getElementById('progressPercent');
    const barEl = document.getElementById('progressBar');
    const stateEl = document.getElementById('progressState');
    const doneEl = document.getElementById('progressDone');
    const importedEl = document.getElementById('countImported');
    const skippedEl = document.getElementById('countSkipped');
    const errorBox = document.getElementById('progressError');
    const errorText = document.getElementById('progressErrorText');
    const retryButton = document.getElementById('retryButton');
    const backLink = document.getElementById('backLink');

    let running = false;
    let finished = false;

    ring.style.strokeDasharray = ringLength;
    ring.style.strokeDashoffset = ringLength;

    function paint(percent, processed, imported, skipped) {
        percentEl.textContent = percent + '%';
        barEl.style.width = percent + '%';
        ring.style.strokeDashoffset = ringLength * (1 - percent / 100);
        doneEl.textContent = processed;
        importedEl.textContent = imported;
        skippedEl.textContent = skipped;
    }

    function showError(message) {
        running = false;
        stateEl.textContent = 'Import belum selesai';
        errorText.textContent = message;
        errorBox.style.display = 'flex';
        backLink.style.display = 'inline-flex';
    }

    async function step() {
        const response = await fetch(chunkUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (e) {
            payload = null;
        }

        if (!response.ok) {
            throw new Error((payload && payload.message) || 'Server membalas dengan status ' + response.status + '.');
        }

        return payload;
    }

    async function run() {
        if (running || finished) return;

        running = true;
        errorBox.style.display = 'none';
        stateEl.textContent = 'Memproses data siswa...';

        while (running) {
            let payload;

            try {
                payload = await step();
            } catch (error) {
                showError(error.message + ' Data yang sudah masuk tetap tersimpan — klik tombol di bawah untuk melanjutkan dari posisi terakhir.');
                return;
            }

            paint(payload.percent, payload.processed, payload.imported, payload.skipped);

            if (payload.done) {
                finished = true;
                running = false;
                stateEl.textContent = 'Selesai! Mengalihkan ke hasil import...';
                window.location.href = payload.result_url;
                return;
            }
        }
    }

    retryButton.addEventListener('click', run);

    // Peringatkan kalau halaman ditutup saat proses masih jalan.
    window.addEventListener('beforeunload', function (event) {
        if (running && !finished) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    paint(total > 0 ? Math.floor({{ $done }} / total * 100) : 100, {{ $done }}, 0, 0);
    run();
})();
</script>
@endpush
@endsection
