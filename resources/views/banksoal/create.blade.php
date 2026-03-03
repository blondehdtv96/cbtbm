@extends('layouts.app')
@section('title', 'Tambah Soal')
@section('page-title', 'Tambah Soal')
@section('page-subtitle', 'Tambahkan soal baru ke bank soal')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-plus-circle-fill me-2"></i>Form Tambah Soal</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('banksoal.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-ios">Mata Pelajaran *</label>
                                <select name="mapel_id" class="form-select-ios w-100" required>
                                    <option value="">Pilih Mapel</option>
                                    @foreach($mapels as $m)<option value="{{ $m->id }}" {{ old('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-ios">Tipe Soal *</label>
                                <select name="tipe_soal" class="form-select-ios w-100" id="tipeSoal" required onchange="toggleOptions()">
                                    <option value="pg" {{ old('tipe_soal') == 'pg' ? 'selected' : '' }}>Pilihan Ganda</option>
                                    <option value="essay" {{ old('tipe_soal') == 'essay' ? 'selected' : '' }}>Essay</option>
                                    <option value="pg_kompleks" {{ old('tipe_soal') == 'pg_kompleks' ? 'selected' : '' }}>PG Kompleks</option>
                                    <option value="menjodohkan" {{ old('tipe_soal') == 'menjodohkan' ? 'selected' : '' }}>Menjodohkan</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label-ios">Bobot Nilai</label>
                                <input type="number" name="bobot_nilai" class="form-control-ios w-100" value="{{ old('bobot_nilai', 1) }}" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-ios">Kategori</label>
                                <input type="text" name="kategori" class="form-control-ios w-100" value="{{ old('kategori') }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-ios">Tag</label>
                                <input type="text" name="tag" class="form-control-ios w-100" value="{{ old('tag') }}" placeholder="Opsional">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-ios">Gambar Soal</label>
                                <input type="file" name="gambar_soal" class="form-control-ios w-100" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-ios">Pertanyaan *</label>
                            <textarea name="pertanyaan" class="form-control-ios w-100" rows="5" required placeholder="Tulis pertanyaan di sini...">{{ old('pertanyaan') }}</textarea>
                        </div>

                        <!-- Options for PG -->
                        <div id="pgOptions">
                            <label class="form-label-ios mb-3">Opsi Jawaban</label>
                            @foreach(['A', 'B', 'C', 'D', 'E'] as $i => $label)
                            <div class="d-flex align-items-center gap-3 mb-3" style="background: var(--bg-glass-dark); padding: 14px; border-radius: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">{{ $label }}</div>
                                <input type="hidden" name="opsi_label[]" value="{{ $label }}">
                                <input type="text" name="opsi_isi[]" class="form-control-ios flex-grow-1" placeholder="Isi opsi {{ $label }}">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; white-space: nowrap; font-size: 13px; font-weight: 600;">
                                    <input type="checkbox" name="opsi_correct[]" value="{{ $i }}"> Benar
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <div class="mb-4">
                            <label class="form-label-ios">Pembahasan</label>
                            <textarea name="pembahasan" class="form-control-ios w-100" rows="3" placeholder="Opsional">{{ old('pembahasan') }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Simpan Soal</button>
                            <a href="{{ route('banksoal.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleOptions() {
    const tipe = document.getElementById('tipeSoal').value;
    document.getElementById('pgOptions').style.display = (tipe === 'pg' || tipe === 'pg_kompleks') ? 'block' : 'none';
}
toggleOptions();
</script>
@endpush
@endsection
