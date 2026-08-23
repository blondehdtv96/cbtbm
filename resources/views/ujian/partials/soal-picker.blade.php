{{-- Dipakai di create.blade.php & edit.blade.php.
     Form pemanggil WAJIB punya:
       <select id="metodeSoalSelect" name="metode_soal">
       <select id="mapelSelect" name="mapel_id">
       <div id="jumlahSoalWrap">...<input id="jumlahSoalInput" name="jumlah_soal">...</div>
     Variabel opsional (mode edit): $ujian, $adaPesertaMulai. --}}
@php
    $existingSoal = isset($ujian) ? $ujian->bankSoals->sortBy('pivot.nomor_urut')->values() : collect();
    $existingSoalData = $existingSoal->map(fn($s) => [
        'id' => $s->id,
        'pertanyaan' => \Illuminate\Support\Str::limit(strip_tags($s->pertanyaan), 100),
        'tipe_soal' => $s->tipe_soal,
        'bobot_nilai' => $s->bobot_nilai,
    ])->values();
    $locked = isset($adaPesertaMulai) && $adaPesertaMulai;
@endphp

<div id="soalPickerWrap" style="display:none;" class="mb-4">
    @if($locked)
        <div style="background: rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:12px; padding:12px 16px; margin-bottom:12px; font-size:13px; color:#92400e;">
            <i class="bi bi-lock-fill me-1"></i> Soal ujian ini tidak bisa diubah lagi karena sudah ada peserta yang mulai mengerjakan.
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <label class="form-label-ios mb-0">Pilih Soal * <small class="text-muted">(dari bank soal mapel terpilih)</small></label>
        <span id="soalSelectedCount" style="font-size:12px; font-weight:700; color: var(--primary);">0 soal dipilih</span>
    </div>

    <input type="text" id="soalPickerSearch" class="form-control-ios w-100 mb-2" placeholder="Cari pertanyaan..." {{ $locked ? 'disabled' : '' }}>

    <div style="max-height: 420px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 12px;">
        <table class="table-ios" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width:36px; text-align:center;"><input type="checkbox" id="soalSelectAll" {{ $locked ? 'disabled' : '' }}></th>
                    <th>Pertanyaan</th>
                    <th style="width:110px;">Tipe</th>
                    <th style="width:70px;">Bobot</th>
                </tr>
            </thead>
            <tbody id="soalPickerBody">
                <tr><td colspan="4" class="text-center text-muted py-3">Pilih mata pelajaran dulu...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="soalPickerHiddenInputs"></div>
</div>

@push('scripts')
<script>
(function () {
    const existingSoal = @json($existingSoalData);
    const locked = {{ $locked ? 'true' : 'false' }};
    let selectedOrder = existingSoal.map(s => s.id);

    const metodeSelect = document.getElementById('metodeSoalSelect');
    const mapelSelect = document.getElementById('mapelSelect');
    const jumlahWrap = document.getElementById('jumlahSoalWrap');
    const wrap = document.getElementById('soalPickerWrap');
    const body = document.getElementById('soalPickerBody');
    const countEl = document.getElementById('soalSelectedCount');
    const searchInput = document.getElementById('soalPickerSearch');
    const selectAll = document.getElementById('soalSelectAll');
    const hiddenWrap = document.getElementById('soalPickerHiddenInputs');

    function syncHiddenInputs() {
        hiddenWrap.innerHTML = '';
        selectedOrder.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'soal_ids[]';
            input.value = id;
            hiddenWrap.appendChild(input);
        });
        countEl.textContent = selectedOrder.length + ' soal dipilih';
    }

    function toggleSoal(id, checked) {
        if (locked) return;
        id = Number(id);
        if (checked) {
            if (!selectedOrder.includes(id)) selectedOrder.push(id);
        } else {
            selectedOrder = selectedOrder.filter(x => x !== id);
        }
        syncHiddenInputs();
    }
    window.__soalPickerToggle = function (checkbox) {
        toggleSoal(checkbox.value, checkbox.checked);
        checkAllState();
    };

    function checkAllState() {
        const boxes = [...document.querySelectorAll('.soal-row-checkbox')];
        const checkedBoxes = boxes.filter(b => b.checked);
        if (!selectAll) return;
        selectAll.checked = boxes.length > 0 && checkedBoxes.length === boxes.length;
        selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < boxes.length;
    }

    function renderRows(soals) {
        if (soals.length === 0) {
            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada soal aktif untuk mapel ini.</td></tr>';
            return;
        }
        body.innerHTML = soals.map(s => {
            const checked = selectedOrder.includes(Number(s.id)) ? 'checked' : '';
            const tipeLabel = s.tipe_soal.replace('_', ' ').toUpperCase();
            return `<tr>
                <td style="text-align:center;"><input type="checkbox" class="soal-row-checkbox" value="${s.id}" ${checked} ${locked ? 'disabled' : ''} onchange="window.__soalPickerToggle(this)"></td>
                <td style="font-size:13px;">${s.pertanyaan}</td>
                <td><span class="badge-ios purple" style="font-size:10px;">${tipeLabel}</span></td>
                <td style="text-align:center; font-weight:700;">${s.bobot_nilai}</td>
            </tr>`;
        }).join('');
        checkAllState();
    }

    selectAll?.addEventListener('change', function () {
        document.querySelectorAll('.soal-row-checkbox').forEach(cb => {
            cb.checked = selectAll.checked;
            toggleSoal(cb.value, cb.checked);
        });
    });

    let fetchTimer = null;
    function fetchSoal() {
        const mapelId = mapelSelect ? mapelSelect.value : '';
        if (!mapelId) {
            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Pilih mata pelajaran dulu...</td></tr>';
            return;
        }
        const params = new URLSearchParams({ mapel_id: mapelId });
        if (searchInput.value.trim()) params.set('search', searchInput.value.trim());

        fetch(`{{ route('ujian.soal-picker') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(renderRows);
    }

    searchInput?.addEventListener('input', function () {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchSoal, 300);
    });

    function toggleMode() {
        const isManual = metodeSelect.value === 'manual';
        wrap.style.display = isManual ? 'block' : 'none';
        if (jumlahWrap) jumlahWrap.style.display = isManual ? 'none' : '';
        if (isManual) fetchSoal();
    }

    metodeSelect?.addEventListener('change', toggleMode);
    mapelSelect?.addEventListener('change', function () {
        if (metodeSelect.value === 'manual') fetchSoal();
    });

    syncHiddenInputs();
    toggleMode();
})();
</script>
@endpush
