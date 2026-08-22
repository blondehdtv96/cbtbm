@extends('layouts.app')
@section('title', 'Edit Soal')
@section('page-title', 'Edit Soal')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-pencil-fill me-2"></i>Edit Soal #{{ $banksoal->id }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('banksoal.update', $banksoal) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-ios">Mata Pelajaran</label>
                                <select name="mapel_id" class="form-select-ios w-100" required>
                                    @foreach($mapels as $m)<option value="{{ $m->id }}" {{ $banksoal->mapel_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-ios">Tipe Soal</label>
                                <select name="tipe_soal" class="form-select-ios w-100" id="tipeSoal" onchange="toggleOptions()" required>
                                    <option value="pg" {{ $banksoal->tipe_soal == 'pg' ? 'selected' : '' }}>Pilihan Ganda</option>
                                    <option value="essay" {{ $banksoal->tipe_soal == 'essay' ? 'selected' : '' }}>Essay</option>
                                    <option value="pg_kompleks" {{ $banksoal->tipe_soal == 'pg_kompleks' ? 'selected' : '' }}>PG Kompleks</option>
                                    <option value="menjodohkan" {{ $banksoal->tipe_soal == 'menjodohkan' ? 'selected' : '' }}>Menjodohkan</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label-ios">Bobot</label><input type="number" name="bobot_nilai" class="form-control-ios w-100" value="{{ $banksoal->bobot_nilai }}" min="1"></div>
                            <div class="col-md-4"><label class="form-label-ios">Status</label><select name="status" class="form-select-ios w-100"><option value="aktif" {{ $banksoal->status == 'aktif' ? 'selected' : '' }}>Aktif</option><option value="nonaktif" {{ $banksoal->status == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option></select></div>
                            <div class="col-md-4"><label class="form-label-ios">Gambar</label><input type="file" name="gambar_soal" class="form-control-ios w-100" accept="image/*"></div>
                        </div>
                        <div class="mb-4"><label class="form-label-ios">Pertanyaan</label><textarea name="pertanyaan" class="form-control-ios w-100" rows="5" required>{{ $banksoal->pertanyaan }}</textarea></div>

                        <div id="pgOptions">
                            <label class="form-label-ios mb-3">Opsi Jawaban</label>
                            @php $existingOptions = $banksoal->opsiJawabans->keyBy('opsi_label'); @endphp
                            @foreach(['A','B','C','D','E'] as $i => $label)
                            <div class="d-flex align-items-center gap-3 mb-3" style="background: var(--bg-glass-dark); padding: 14px; border-radius: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">{{ $label }}</div>
                                <input type="hidden" name="opsi_label[]" value="{{ $label }}">
                                <input type="text" name="opsi_isi[]" class="form-control-ios flex-grow-1" value="{{ $existingOptions->get($label)->isi_opsi ?? '' }}">
                                @if($existingOptions->get($label)->gambar_opsi ?? null)
                                    <img src="{{ asset('storage/' . $existingOptions->get($label)->gambar_opsi) }}" alt="Gambar opsi {{ $label }}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 8px;">
                                @endif
                                <input type="hidden" name="opsi_gambar_existing[]" value="{{ $existingOptions->get($label)->gambar_opsi ?? '' }}">
                                <input type="file" name="opsi_gambar[]" class="form-control-ios" style="max-width: 200px;" accept="image/jpeg,image/png">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                    <input type="checkbox" name="opsi_correct[]" value="{{ $i }}" {{ ($existingOptions->get($label)->is_correct ?? false) ? 'checked' : '' }}> Benar
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Update</button>
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
