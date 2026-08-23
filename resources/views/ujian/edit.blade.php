@extends('layouts.app')
@section('title', 'Edit Ujian')
@section('page-title', 'Edit Ujian')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-pencil-fill me-2"></i>Edit: {{ $ujian->nama_ujian }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('ujian.update', $ujian) }}">
                        @csrf @method('PUT')
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label-ios">Nama Ujian</label><input type="text" name="nama_ujian" class="form-control-ios w-100" value="{{ $ujian->nama_ujian }}" required></div>
                            <div class="col-md-3"><label class="form-label-ios">Jenis</label>
                                <select name="jenis_ujian" class="form-select-ios w-100" required>
                                    @foreach(['harian','uts','uas','praktik','tryout','anbk','ukk'] as $j)
                                    <option value="{{ $j }}" {{ $ujian->jenis_ujian == $j ? 'selected' : '' }}>{{ ucfirst($j) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label-ios">Mapel</label>
                                <select name="mapel_id" id="mapelSelect" class="form-select-ios w-100" required>
                                    @foreach($mapels as $m)<option value="{{ $m->id }}" {{ $ujian->mapel_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><label class="form-label-ios">Sesi Ujian</label>
                                <select name="sesi_ujian_id" class="form-select-ios w-100">
                                    <option value="">-- Tanpa Sesi --</option>
                                    @foreach($sesiList as $sesi)
                                        <option value="{{ $sesi->id }}" {{ $ujian->sesi_ujian_id == $sesi->id ? 'selected' : '' }}>
                                            {{ $sesi->nama_sesi }} ({{ substr($sesi->jam_mulai, 0, 5) }} – {{ substr($sesi->jam_selesai, 0, 5) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label-ios">Durasi</label><input type="number" name="durasi_menit" class="form-control-ios w-100" value="{{ $ujian->durasi_menit }}" required></div>
                            <div class="col-md-3" id="jumlahSoalWrap"><label class="form-label-ios">Jumlah Soal</label><input type="number" id="jumlahSoalInput" name="jumlah_soal" class="form-control-ios w-100" value="{{ $ujian->jumlah_soal }}"></div>
                            @if(!auth()->user()->isGuru())
                            <div class="col-md-3"><label class="form-label-ios">Guru Pengampu</label>
                                <select name="guru_id" class="form-select-ios w-100">
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($guruList as $g)
                                    <option value="{{ $g->id }}" {{ $ujian->guru_id == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label-ios">Metode Soal</label>
                                <select name="metode_soal" id="metodeSoalSelect" class="form-select-ios w-100" {{ $adaPesertaMulai ? 'disabled' : '' }}>
                                    <option value="random" {{ $ujian->metode_soal == 'random' ? 'selected' : '' }}>Random</option>
                                    <option value="manual" {{ $ujian->metode_soal == 'manual' ? 'selected' : '' }}>Manual</option>
                                </select>
                                @if($adaPesertaMulai)
                                    <input type="hidden" name="metode_soal" value="{{ $ujian->metode_soal }}">
                                    <small class="text-muted">Terkunci — sudah ada peserta mengerjakan.</small>
                                @endif
                            </div>
                            @if($ujian->metode_soal === 'random' && !$adaPesertaMulai)
                            <div class="col-md-4">
                                <label class="form-label-ios d-block mb-2">&nbsp;</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                                    <input type="checkbox" name="acak_ulang" value="1"> Acak ulang soal (ganti soal yang sudah terpasang dengan pilihan acak baru)
                                </label>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <label class="form-label-ios d-block mb-2">Pengaturan</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-bottom: 8px;"><input type="checkbox" name="acak_opsi" value="1" {{ $ujian->acak_opsi ? 'checked' : '' }}> Acak opsi jawaban</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-bottom: 8px;"><input type="checkbox" name="tampilkan_nilai" value="1" {{ $ujian->tampilkan_nilai ? 'checked' : '' }}> Tampilkan nilai</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;"><input type="checkbox" name="tampilkan_pembahasan" value="1" {{ $ujian->tampilkan_pembahasan ? 'checked' : '' }}> Tampilkan pembahasan</label>
                            </div>
                        </div>

                        @include('ujian.partials.soal-picker')
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><label class="form-label-ios">Mulai</label><input type="datetime-local" name="tanggal_mulai" class="form-control-ios w-100" value="{{ $ujian->tanggal_mulai->format('Y-m-d\TH:i') }}" required></div>
                            <div class="col-md-3"><label class="form-label-ios">Selesai</label><input type="datetime-local" name="tanggal_selesai" class="form-control-ios w-100" value="{{ $ujian->tanggal_selesai->format('Y-m-d\TH:i') }}" required></div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><label class="form-label-ios">Status</label>
                                <select name="status" class="form-select-ios w-100">
                                    <option value="draft" {{ $ujian->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="publish" {{ $ujian->status == 'publish' ? 'selected' : '' }}>Publish</option>
                                    <option value="selesai" {{ $ujian->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                </select>
                            </div>
                        </div>
                        @include('ujian.partials.kelas-picker', ['selectedKelas' => $ujian->kelasList->pluck('id')->toArray()])
                        <div class="mb-4"><label class="form-label-ios">Instruksi</label><textarea name="instruksi" class="form-control-ios w-100" rows="3">{{ $ujian->instruksi }}</textarea></div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Update</button>
                            <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
