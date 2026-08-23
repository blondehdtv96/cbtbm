{{-- Dipakai di create.blade.php & edit.blade.php.
     Butuh: $kelasList (Kelas, dengan relasi 'jurusan' sudah di-eager-load), $selectedKelas (array id). --}}
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <label class="form-label-ios mb-0">Kelas Peserta * <small class="text-muted">(pilih satu atau lebih)</small></label>
        <span id="kelasSelectedCount" style="font-size: 12px; font-weight: 700; color: var(--primary);">{{ count($selectedKelas) }} kelas dipilih</span>
    </div>

    @php
        $kelasByTingkat = $kelasList->groupBy('tingkat')->sortKeys();
    @endphp

    <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px;">
        @forelse($kelasByTingkat as $tingkat => $kelasTingkat)
            @php $kelasByJurusan = $kelasTingkat->groupBy(fn($k) => $k->jurusan->nama_jurusan ?? 'Umum'); @endphp
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:700; font-size:13px; color: var(--primary);">
                        <input type="checkbox" class="kelas-tingkat-toggle" data-tingkat="{{ $tingkat }}" onchange="toggleKelasTingkat(this)">
                        Tingkat {{ $tingkat }}
                    </label>
                </div>
                <div class="row g-2 ms-1">
                    @foreach($kelasByJurusan as $jurusanNama => $kelasJurusan)
                        <div class="col-12 mb-1">
                            <div style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px;">{{ $jurusanNama }}</div>
                            <div class="row g-2">
                                @foreach($kelasJurusan as $kelas)
                                <div class="col-md-4 col-lg-3">
                                    <label style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: var(--bg-glass-dark); border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 500;">
                                        <input type="checkbox" name="kelas_ids[]" class="kelas-checkbox" data-tingkat="{{ $tingkat }}" value="{{ $kelas->id }}" onchange="updateKelasCount()" {{ in_array($kelas->id, $selectedKelas) ? 'checked' : '' }}>
                                        {{ $kelas->nama_kelas }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted mb-0" style="font-size: 13px;">Belum ada kelas aktif.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function updateKelasCount() {
    const checked = document.querySelectorAll('.kelas-checkbox:checked').length;
    document.getElementById('kelasSelectedCount').textContent = checked + ' kelas dipilih';

    document.querySelectorAll('.kelas-tingkat-toggle').forEach(toggle => {
        const tingkat = toggle.dataset.tingkat;
        const boxes = document.querySelectorAll(`.kelas-checkbox[data-tingkat="${tingkat}"]`);
        const checkedInGroup = [...boxes].filter(b => b.checked).length;
        toggle.checked = checkedInGroup === boxes.length && boxes.length > 0;
        toggle.indeterminate = checkedInGroup > 0 && checkedInGroup < boxes.length;
    });
}

function toggleKelasTingkat(toggle) {
    const tingkat = toggle.dataset.tingkat;
    document.querySelectorAll(`.kelas-checkbox[data-tingkat="${tingkat}"]`).forEach(cb => cb.checked = toggle.checked);
    updateKelasCount();
}

updateKelasCount();
</script>
@endpush
