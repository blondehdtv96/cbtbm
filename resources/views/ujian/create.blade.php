@extends('layouts.app')
@section('title', 'Buat Ujian')
@section('page-title', 'Buat Ujian Baru')

@push('styles')
<style>
    .exam-create-shell { max-width: 1180px; margin: 0 auto; }
    .exam-create-hero {
        position: relative; overflow: hidden; border-radius: 22px; padding: 28px 30px;
        margin-bottom: 20px; color: #fff;
        background: linear-gradient(125deg, #1d4ed8 0%, #2563eb 48%, #4f46e5 100%);
        box-shadow: 0 16px 35px rgba(37, 99, 235, .2);
    }
    .exam-create-hero::after {
        content: ''; position: absolute; width: 240px; height: 240px; right: -70px; top: -115px;
        border-radius: 50%; background: rgba(255,255,255,.11);
    }
    .exam-create-hero__content { position: relative; z-index: 1; }
    .exam-create-hero__eyebrow {
        display: inline-flex; align-items: center; gap: 7px; padding: 6px 11px; margin-bottom: 13px;
        border: 1px solid rgba(255,255,255,.2); border-radius: 999px; background: rgba(255,255,255,.12);
        font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    }
    .exam-create-hero h2 { margin: 0 0 7px; font-size: 25px; font-weight: 750; letter-spacing: -.03em; }
    .exam-create-hero p { max-width: 680px; margin: 0; color: rgba(255,255,255,.78); font-size: 13px; line-height: 1.65; }
    .exam-form-card { border: 0; border-radius: 22px; background: var(--bg-secondary); box-shadow: var(--shadow-lg); }
    .exam-form-card .card-body { padding: 24px; }
    .exam-section { padding: 22px; border: 1px solid var(--border-color); border-radius: 17px; background: #fff; }
    .exam-section + .exam-section { margin-top: 16px; }
    .exam-section__header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
    .exam-section__icon {
        display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; height: 38px;
        color: var(--primary); border-radius: 11px; background: rgba(37, 99, 235, .09); font-size: 17px;
    }
    .exam-section__header h5 { margin: 0 0 3px; color: var(--text-primary); font-size: 15px; font-weight: 750; }
    .exam-section__header p { margin: 0; color: var(--text-secondary); font-size: 12px; line-height: 1.5; }
    .exam-required { color: var(--danger); }
    .field-help { display: block; margin-top: 6px; color: var(--text-muted); font-size: 11px; }
    .field-error { display: block; margin-top: 5px; color: var(--danger); font-size: 11px; font-weight: 600; }
    .mapel-panel { padding: 16px; border: 1px solid rgba(37, 99, 235, .14); border-radius: 14px; background: rgba(37, 99, 235, .025); }
    .mapel-search { position: relative; margin-bottom: 10px; }
    .mapel-search > i { position: absolute; left: 14px; top: 50%; color: var(--text-muted); transform: translateY(-50%); }
    .mapel-search input { padding-left: 40px; padding-right: 42px; }
    .mapel-search button {
        display: none; position: absolute; right: 8px; top: 50%; width: 29px; height: 29px; padding: 0;
        border: 0; border-radius: 8px; color: var(--text-secondary); background: var(--bg-glass-dark); transform: translateY(-50%);
    }
    .mapel-search button.is-visible { display: inline-flex; align-items: center; justify-content: center; }
    .mapel-result-info { display: flex; justify-content: space-between; gap: 12px; margin-top: 7px; color: var(--text-muted); font-size: 11px; }
    .mapel-selected {
        display: none; align-items: center; gap: 12px; margin-top: 12px; padding: 12px 14px;
        border: 1px solid rgba(34, 197, 94, .18); border-radius: 12px; background: rgba(34, 197, 94, .06);
    }
    .mapel-selected.is-visible { display: flex; }
    .mapel-selected__icon {
        display: inline-flex; align-items: center; justify-content: center; flex: 0 0 36px; height: 36px;
        border-radius: 10px; color: #15803d; background: rgba(34, 197, 94, .13);
    }
    .mapel-selected strong { display: block; color: var(--text-primary); font-size: 13px; }
    .mapel-selected small { display: block; margin-top: 2px; color: var(--text-secondary); font-size: 11px; }
    .mapel-note {
        display: flex; align-items: flex-start; gap: 9px; margin-top: 12px; padding: 11px 13px;
        border-radius: 11px; color: #1e40af; background: rgba(59, 130, 246, .08); font-size: 11px; line-height: 1.55;
    }
    .setting-list { display: grid; gap: 8px; }
    .setting-item {
        display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 11px 13px;
        border: 1px solid var(--border-color); border-radius: 11px; cursor: pointer;
    }
    .setting-item strong { display: block; font-size: 12px; }
    .setting-item small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 10px; }
    .setting-item .form-check-input { width: 2.15em; height: 1.15em; margin: 0; cursor: pointer; }
    .exam-actions {
        position: sticky; bottom: 12px; z-index: 10; display: flex; align-items: center; justify-content: space-between;
        gap: 14px; margin-top: 18px; padding: 13px 15px; border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 15px; background: rgba(255,255,255,.94); box-shadow: 0 12px 30px rgba(15,23,42,.1); backdrop-filter: blur(12px);
    }
    .exam-actions__hint { color: var(--text-secondary); font-size: 11px; }
    @media (max-width: 767.98px) {
        .exam-create-hero { padding: 23px 20px; border-radius: 17px; }
        .exam-create-hero h2 { font-size: 21px; }
        .exam-form-card .card-body { padding: 14px; }
        .exam-section { padding: 17px 14px; }
        .exam-actions { align-items: stretch; flex-direction: column; bottom: 6px; }
        .exam-actions__hint { display: none; }
        .exam-actions .d-flex { width: 100%; }
        .exam-actions .btn { flex: 1; }
    }
</style>
@endpush

@section('content')
<div class="fade-in exam-create-shell">
    <div class="exam-create-hero">
        <div class="exam-create-hero__content">
            <div class="exam-create-hero__eyebrow"><i class="bi bi-shield-check"></i> Pengaturan Ujian</div>
            <h2>Susun ujian dengan lebih terstruktur</h2>
            <p>Lengkapi identitas, jadwal, bank soal, dan peserta. Ujian akan disimpan sebagai draft sehingga masih dapat diperiksa sebelum dipublikasikan.</p>
        </div>
    </div>

    <div class="card-ios exam-form-card">
        <div class="card-body">
            <form method="POST" action="{{ route('ujian.store') }}" id="examCreateForm">
                @csrf

                <section class="exam-section">
                    <div class="exam-section__header">
                        <span class="exam-section__icon"><i class="bi bi-file-earmark-text"></i></span>
                        <div><h5>Informasi utama</h5><p>Identitas ini membantu membedakan banyak ujian dalam jadwal yang sama.</p></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-8">
                            <label class="form-label-ios" for="namaUjian">Nama Ujian <span class="exam-required">*</span></label>
                            <input type="text" id="namaUjian" name="nama_ujian" class="form-control-ios w-100" value="{{ old('nama_ujian') }}" placeholder="Contoh: Penilaian Tengah Semester Ganjil" maxlength="255" required autofocus>
                            <span class="field-help">Gunakan nama periode yang konsisten agar mudah dicari.</span>
                            @error('nama_ujian')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label-ios" for="jenisUjian">Jenis Ujian <span class="exam-required">*</span></label>
                            <select name="jenis_ujian" id="jenisUjian" class="form-select-ios w-100" required>
                                @foreach(['harian' => 'Harian', 'uts' => 'UTS', 'uas' => 'UAS', 'praktik' => 'Praktik', 'tryout' => 'Tryout', 'anbk' => 'Simulasi ANBK', 'ukk' => 'UKK'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('jenis_ujian', 'harian') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('jenis_ujian')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label-ios" for="mapelSelect">Mata Pelajaran <span class="exam-required">*</span></label>
                            <div class="mapel-panel">
                                <div class="mapel-search">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="mapelSearch" class="form-control-ios w-100" placeholder="Cari kode, nama mapel, atau jurusan..." autocomplete="off">
                                    <button type="button" id="mapelSearchClear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button>
                                </div>

                                @php
                                    $mapelGroups = $mapels->groupBy(fn ($mapel) => $mapel->is_umum
                                        ? 'Mata Pelajaran Umum'
                                        : ($mapel->jurusan->nama_jurusan ?? 'Lintas Jurusan'));
                                @endphp
                                <select name="mapel_id" id="mapelSelect" class="form-select-ios w-100" required>
                                    <option value="">Pilih mata pelajaran yang akan diujikan</option>
                                    @foreach($mapelGroups as $groupName => $groupMapels)
                                        <optgroup label="{{ $groupName }}">
                                            @foreach($groupMapels as $mapel)
                                                @php
                                                    $kodeMapel = $mapel->kode_mapel ?: 'Tanpa kode';
                                                    $jurusanMapel = $mapel->is_umum ? 'Umum' : ($mapel->jurusan->nama_jurusan ?? 'Lintas Jurusan');
                                                @endphp
                                                <option value="{{ $mapel->id }}"
                                                    data-code="{{ $kodeMapel }}"
                                                    data-jurusan="{{ $jurusanMapel }}"
                                                    data-soal="{{ $mapel->soal_aktif_count }}"
                                                    data-search="{{ strtolower($kodeMapel.' '.$mapel->nama_mapel.' '.$jurusanMapel) }}"
                                                    {{ (string) old('mapel_id') === (string) $mapel->id ? 'selected' : '' }}>
                                                    {{ $kodeMapel }} — {{ $mapel->nama_mapel }} ({{ $mapel->soal_aktif_count }} soal aktif)
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>

                                <div class="mapel-result-info">
                                    <span id="mapelResultCount">{{ $mapels->count() }} mapel tersedia</span>
                                    <span>Dikelompokkan berdasarkan jurusan</span>
                                </div>
                                <div class="mapel-selected" id="mapelSelectedCard">
                                    <span class="mapel-selected__icon"><i class="bi bi-book-half"></i></span>
                                    <div><strong id="mapelSelectedName"></strong><small id="mapelSelectedMeta"></small></div>
                                </div>
                                <div class="mapel-note">
                                    <i class="bi bi-info-circle-fill mt-1"></i>
                                    <span><strong>Satu ujian untuk satu mapel.</strong> Jika banyak mapel dilaksanakan pada hari yang sama, buat ujian terpisah per mapel agar bank soal, peserta, token, dan hasil nilainya tetap spesifik.</span>
                                </div>
                            </div>
                            @error('mapel_id')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </section>

                <section class="exam-section">
                    <div class="exam-section__header">
                        <span class="exam-section__icon"><i class="bi bi-calendar-week"></i></span>
                        <div><h5>Jadwal dan penanggung jawab</h5><p>Tentukan sesi, rentang waktu akses, dan durasi pengerjaan.</p></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label-ios" for="sesiUjian">Sesi Ujian</label>
                            <select name="sesi_ujian_id" id="sesiUjian" class="form-select-ios w-100">
                                <option value="">Tanpa sesi khusus</option>
                                @foreach($sesiList as $sesi)
                                    <option value="{{ $sesi->id }}" {{ (string) old('sesi_ujian_id') === (string) $sesi->id ? 'selected' : '' }}>{{ $sesi->nama_sesi }} · {{ substr($sesi->jam_mulai, 0, 5) }}–{{ substr($sesi->jam_selesai, 0, 5) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!auth()->user()->isGuru())
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label-ios" for="guruPengampu">Guru Pengampu</label>
                            <select name="guru_id" id="guruPengampu" class="form-select-ios w-100">
                                <option value="">Pilih guru pengampu</option>
                                @foreach($guruList as $guru)
                                    <option value="{{ $guru->id }}" {{ (string) old('guru_id') === (string) $guru->id ? 'selected' : '' }}>{{ $guru->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label-ios" for="durasiMenit">Durasi <span class="exam-required">*</span></label>
                            <div class="input-group">
                                <input type="number" id="durasiMenit" name="durasi_menit" class="form-control-ios w-100" value="{{ old('durasi_menit', 60) }}" min="1" required>
                            </div>
                            <span class="field-help">Dalam menit</span>
                            @error('durasi_menit')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios" for="tanggalMulai">Tanggal Mulai <span class="exam-required">*</span></label>
                            <input type="datetime-local" id="tanggalMulai" name="tanggal_mulai" class="form-control-ios w-100" value="{{ old('tanggal_mulai') }}" required>
                            @error('tanggal_mulai')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios" for="tanggalSelesai">Tanggal Selesai <span class="exam-required">*</span></label>
                            <input type="datetime-local" id="tanggalSelesai" name="tanggal_selesai" class="form-control-ios w-100" value="{{ old('tanggal_selesai') }}" required>
                            @error('tanggal_selesai')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </section>

                <section class="exam-section">
                    <div class="exam-section__header">
                        <span class="exam-section__icon"><i class="bi bi-ui-checks-grid"></i></span>
                        <div><h5>Soal dan pengaturan hasil</h5><p>Pilih cara pengambilan soal serta informasi yang dapat dilihat peserta.</p></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-4">
                            <label class="form-label-ios" for="metodeSoalSelect">Metode Soal</label>
                            <select name="metode_soal" id="metodeSoalSelect" class="form-select-ios w-100">
                                <option value="random" {{ old('metode_soal', 'random') === 'random' ? 'selected' : '' }}>Acak dari bank soal</option>
                                <option value="manual" {{ old('metode_soal') === 'manual' ? 'selected' : '' }}>Pilih soal manual</option>
                            </select>
                        </div>
                        <div class="col-lg-3" id="jumlahSoalWrap">
                            <label class="form-label-ios" for="jumlahSoalInput">Jumlah Soal <span class="exam-required">*</span></label>
                            <input type="number" id="jumlahSoalInput" name="jumlah_soal" class="form-control-ios w-100" value="{{ old('jumlah_soal', 10) }}" min="1">
                            @error('jumlah_soal')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label-ios d-block">Pengaturan Peserta</label>
                            <div class="setting-list">
                                <label class="setting-item">
                                    <span><strong>Acak opsi jawaban</strong><small>Urutan pilihan berbeda untuk setiap peserta</small></span>
                                    <span class="form-check form-switch m-0"><input type="hidden" name="acak_opsi" value="0"><input class="form-check-input" type="checkbox" name="acak_opsi" value="1" {{ old('acak_opsi', 1) ? 'checked' : '' }}></span>
                                </label>
                                <label class="setting-item">
                                    <span><strong>Tampilkan nilai</strong><small>Nilai muncul setelah ujian selesai</small></span>
                                    <span class="form-check form-switch m-0"><input type="hidden" name="tampilkan_nilai" value="0"><input class="form-check-input" type="checkbox" name="tampilkan_nilai" value="1" {{ old('tampilkan_nilai', 1) ? 'checked' : '' }}></span>
                                </label>
                                <label class="setting-item">
                                    <span><strong>Tampilkan pembahasan</strong><small>Peserta dapat melihat pembahasan jawaban</small></span>
                                    <span class="form-check form-switch m-0"><input type="hidden" name="tampilkan_pembahasan" value="0"><input class="form-check-input" type="checkbox" name="tampilkan_pembahasan" value="1" {{ old('tampilkan_pembahasan', 0) ? 'checked' : '' }}></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    @include('ujian.partials.soal-picker')
                </section>

                <section class="exam-section">
                    <div class="exam-section__header">
                        <span class="exam-section__icon"><i class="bi bi-people"></i></span>
                        <div><h5>Peserta ujian</h5><p>Pilih satu atau beberapa kelas yang mengikuti mapel ini.</p></div>
                    </div>
                    @include('ujian.partials.kelas-picker', ['selectedKelas' => old('kelas_ids', [])])
                    @error('kelas_ids')<span class="field-error">{{ $message }}</span>@enderror
                </section>

                <section class="exam-section">
                    <div class="exam-section__header">
                        <span class="exam-section__icon"><i class="bi bi-card-text"></i></span>
                        <div><h5>Instruksi ujian</h5><p>Sampaikan aturan atau petunjuk khusus kepada peserta.</p></div>
                    </div>
                    <label class="form-label-ios" for="instruksiUjian">Petunjuk Pengerjaan</label>
                    <textarea name="instruksi" id="instruksiUjian" class="form-control-ios w-100" rows="4" placeholder="Contoh: Berdoa sebelum mulai, baca setiap soal dengan teliti, dan pastikan semua jawaban sudah tersimpan...">{{ old('instruksi') }}</textarea>
                </section>

                <div class="exam-actions">
                    <div class="exam-actions__hint"><i class="bi bi-lock me-1"></i> Ujian disimpan sebagai draft dan belum terlihat oleh peserta.</div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check2-circle"></i> Simpan Ujian</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('mapelSearch');
    const clearButton = document.getElementById('mapelSearchClear');
    const select = document.getElementById('mapelSelect');
    const resultCount = document.getElementById('mapelResultCount');
    const selectedCard = document.getElementById('mapelSelectedCard');
    const selectedName = document.getElementById('mapelSelectedName');
    const selectedMeta = document.getElementById('mapelSelectedMeta');
    if (!searchInput || !select) return;

    const options = [...select.querySelectorAll('option[data-search]')];
    const normalize = value => value.toLocaleLowerCase('id-ID').trim();

    function updateSelectedCard() {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            selectedCard.classList.remove('is-visible');
            return;
        }
        selectedName.textContent = option.textContent.replace(/\s*\(\d+ soal aktif\)\s*$/, '').trim();
        selectedMeta.textContent = `${option.dataset.jurusan} · ${option.dataset.soal} soal aktif tersedia`;
        selectedCard.classList.add('is-visible');
    }

    function filterMapels() {
        const keyword = normalize(searchInput.value);
        let visible = 0;
        options.forEach(option => {
            const matches = !keyword || normalize(option.dataset.search).includes(keyword);
            option.hidden = !matches;
            if (matches) visible++;
        });
        select.querySelectorAll('optgroup').forEach(group => {
            group.hidden = [...group.querySelectorAll('option')].every(option => option.hidden);
        });
        resultCount.textContent = keyword ? `${visible} mapel ditemukan` : `${options.length} mapel tersedia`;
        clearButton.classList.toggle('is-visible', keyword.length > 0);
    }

    searchInput.addEventListener('input', filterMapels);
    clearButton.addEventListener('click', function () {
        searchInput.value = '';
        filterMapels();
        searchInput.focus();
    });
    select.addEventListener('change', updateSelectedCard);

    filterMapels();
    updateSelectedCard();
})();
</script>
@endpush
