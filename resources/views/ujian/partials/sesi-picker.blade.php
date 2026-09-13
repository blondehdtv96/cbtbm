@php
    $selectedSesi = (string) ($selectedSesi ?? '');
@endphp

@once
@push('styles')
<style>
    .session-picker-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:9px; }
    .session-picker-hint { color:var(--text-muted); font-size:10px; font-weight:600; }
    .session-picker-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:10px; }
    .session-choice { position:relative; min-width:0; }
    .session-choice input { position:absolute; opacity:0; pointer-events:none; }
    .session-choice-card {
        display:flex; align-items:center; gap:11px; min-height:76px; padding:12px;
        border:1.5px solid var(--border-color); border-radius:13px; background:#fff;
        cursor:pointer; transition:border-color .18s,box-shadow .18s,transform .18s,background .18s;
    }
    .session-choice-card:hover { transform:translateY(-1px); border-color:rgba(37,99,235,.28); box-shadow:var(--shadow-sm); }
    .session-choice input:focus-visible + .session-choice-card { outline:3px solid rgba(37,99,235,.16); outline-offset:2px; }
    .session-choice input:checked + .session-choice-card {
        border-color:var(--primary); background:rgba(37,99,235,.045); box-shadow:0 0 0 3px rgba(37,99,235,.09);
    }
    .session-choice-icon {
        display:inline-flex; align-items:center; justify-content:center; flex:0 0 42px; height:42px;
        border-radius:12px; font-size:19px;
    }
    .session-choice-icon.none { color:#64748b; background:#f1f5f9; }
    .session-choice-icon.morning { color:#d97706; background:#fffbeb; }
    .session-choice-icon.noon { color:#ea580c; background:#fff7ed; }
    .session-choice-icon.afternoon { color:#7c3aed; background:#f5f3ff; }
    .session-choice-icon.night { color:#4338ca; background:#eef2ff; }
    .session-choice-content { min-width:0; flex:1; }
    .session-choice-name { display:flex; align-items:center; gap:6px; margin-bottom:3px; font-size:12px; font-weight:800; color:var(--text-primary); }
    .session-choice-period { padding:2px 6px; border-radius:999px; color:var(--text-secondary); background:var(--bg-glass-dark); font-size:8px; text-transform:uppercase; letter-spacing:.05em; }
    .session-choice-time { display:flex; align-items:center; gap:6px; color:var(--text-secondary); font-size:11px; font-weight:650; }
    .session-choice-order { display:block; margin-top:3px; color:var(--text-muted); font-size:9px; }
    .session-choice-check { color:var(--primary); font-size:16px; opacity:0; transform:scale(.7); transition:.18s; }
    .session-choice input:checked + .session-choice-card .session-choice-check { opacity:1; transform:scale(1); }
    .session-sync-note {
        display:flex; align-items:flex-start; gap:8px; margin-top:10px; padding:9px 11px;
        border-radius:10px; color:#1e40af; background:rgba(59,130,246,.07); font-size:10px; line-height:1.5;
    }
    @media(max-width:575.98px){.session-picker-grid{grid-template-columns:1fr}.session-choice-card{min-height:68px}}
</style>
@endpush
@endonce

<div class="{{ $columnClass ?? 'col-12' }}">
    <div class="session-picker-header">
        <label class="form-label-ios mb-0">Sesi Ujian</label>
        <span class="session-picker-hint"><i class="bi bi-sort-down me-1"></i>Diurutkan dari jam paling awal</span>
    </div>
    <div class="session-picker-grid" role="radiogroup" aria-label="Pilih sesi ujian">
        <label class="session-choice">
            <input type="radio" name="sesi_ujian_id" value="" {{ $selectedSesi === '' ? 'checked' : '' }}>
            <span class="session-choice-card">
                <span class="session-choice-icon none"><i class="bi bi-calendar2-minus"></i></span>
                <span class="session-choice-content">
                    <span class="session-choice-name">Tanpa Sesi</span>
                    <span class="session-choice-time"><i class="bi bi-pencil-square"></i> Atur jam secara manual</span>
                </span>
                <i class="bi bi-check-circle-fill session-choice-check"></i>
            </span>
        </label>

        @foreach($sesiList as $position => $sesi)
            @php
                $hour = (int) substr($sesi->jam_mulai, 0, 2);
                if ($hour < 10) {
                    $period = 'Pagi'; $icon = 'bi-sunrise-fill'; $theme = 'morning';
                } elseif ($hour < 15) {
                    $period = 'Siang'; $icon = 'bi-sun-fill'; $theme = 'noon';
                } elseif ($hour < 18) {
                    $period = 'Sore'; $icon = 'bi-sunset-fill'; $theme = 'afternoon';
                } else {
                    $period = 'Malam'; $icon = 'bi-moon-stars-fill'; $theme = 'night';
                }
                $startTime = substr($sesi->jam_mulai, 0, 5);
                $endTime = substr($sesi->jam_selesai, 0, 5);
            @endphp
            <label class="session-choice">
                <input type="radio" name="sesi_ujian_id" value="{{ $sesi->id }}"
                    data-session-start="{{ $startTime }}" data-session-end="{{ $endTime }}"
                    {{ $selectedSesi === (string) $sesi->id ? 'checked' : '' }}>
                <span class="session-choice-card">
                    <span class="session-choice-icon {{ $theme }}"><i class="bi {{ $icon }}"></i></span>
                    <span class="session-choice-content">
                        <span class="session-choice-name">{{ $sesi->nama_sesi }} <span class="session-choice-period">{{ $period }}</span></span>
                        <span class="session-choice-time"><strong>{{ $startTime }}</strong><i class="bi bi-arrow-right"></i><strong>{{ $endTime }}</strong></span>
                        <span class="session-choice-order">Urutan sesi {{ $position + 1 }}</span>
                    </span>
                    <i class="bi bi-check-circle-fill session-choice-check"></i>
                </span>
            </label>
        @endforeach
    </div>
    <div class="session-sync-note" id="sessionSyncNote">
        <i class="bi bi-clock-history mt-1"></i>
        <span>Pilih sesi agar jam pada <strong>Tanggal Mulai</strong> dan <strong>Tanggal Selesai</strong> otomatis mengikuti rentang sesi. Pilih “Tanpa Sesi” untuk mengatur jam sendiri.</span>
    </div>
    @error('sesi_ujian_id')<span class="field-error">{{ $message }}</span>@enderror
</div>

@once
@push('scripts')
<script>
(function () {
    const sessionRadios = [...document.querySelectorAll('input[name="sesi_ujian_id"]')];
    const startInput = document.getElementById('tanggalMulai');
    const endInput = document.getElementById('tanggalSelesai');
    if (!sessionRadios.length || !startInput || !endInput) return;

    function datePart(value) {
        return value && value.includes('T') ? value.split('T')[0] : '';
    }

    function syncScheduleWithSession() {
        const selected = sessionRadios.find(radio => radio.checked);
        if (!selected || !selected.value) return;

        const startDate = datePart(startInput.value);
        const endDate = datePart(endInput.value) || startDate;
        if (startDate) startInput.value = `${startDate}T${selected.dataset.sessionStart}`;
        if (endDate) endInput.value = `${endDate}T${selected.dataset.sessionEnd}`;
    }

    sessionRadios.forEach(radio => radio.addEventListener('change', syncScheduleWithSession));
    startInput.addEventListener('change', syncScheduleWithSession);
    endInput.addEventListener('change', syncScheduleWithSession);
})();
</script>
@endpush
@endonce