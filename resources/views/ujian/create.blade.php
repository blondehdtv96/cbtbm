@extends('layouts.app')
@section('title', 'Buat Ujian')
@section('page-title', 'Buat Ujian Baru')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-calendar-plus-fill me-2"></i>Form Buat Ujian</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('ujian.store') }}">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label-ios">Nama Ujian *</label><input type="text" name="nama_ujian" class="form-control-ios w-100" value="{{ old('nama_ujian') }}" required></div>
                            <div class="col-md-3"><label class="form-label-ios">Jenis *</label>
                                <select name="jenis_ujian" class="form-select-ios w-100" required>
                                    <option value="harian">Harian</option><option value="uts">UTS</option><option value="uas">UAS</option>
                                    <option value="praktik">Praktik</option><option value="tryout">Tryout</option>
                                    <option value="anbk">Simulasi ANBK</option><option value="ukk">UKK</option>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label-ios">Mata Pelajaran *</label>
                                <select name="mapel_id" id="mapelSelect" class="form-select-ios w-100" required>
                                    <option value="">Pilih</option>@foreach($mapels as $m)<option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label-ios">Sesi Ujian</label>
                                <select name="sesi_ujian_id" class="form-select-ios w-100">
                                    <option value="">-- Tanpa Sesi --</option>
                                    @foreach($sesiList as $sesi)
                                        <option value="{{ $sesi->id }}" {{ old('sesi_ujian_id') == $sesi->id ? 'selected' : '' }}>
                                            {{ $sesi->nama_sesi }} ({{ substr($sesi->jam_mulai, 0, 5) }} – {{ substr($sesi->jam_selesai, 0, 5) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label-ios">Durasi (menit) *</label><input type="number" name="durasi_menit" class="form-control-ios w-100" value="{{ old('durasi_menit', 60) }}" min="1" required></div>
                            <div class="col-md-3" id="jumlahSoalWrap"><label class="form-label-ios">Jumlah Soal *</label><input type="number" id="jumlahSoalInput" name="jumlah_soal" class="form-control-ios w-100" value="{{ old('jumlah_soal', 10) }}" min="1"></div>
                            @if(!auth()->user()->isGuru())
                            <div class="col-md-4"><label class="form-label-ios">Guru Pengampu</label>
                                <select name="guru_id" class="form-select-ios w-100">
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($guruList as $g)
                                    <option value="{{ $g->id }}" {{ old('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-3"><label class="form-label-ios">Tanggal Mulai *</label><input type="datetime-local" name="tanggal_mulai" class="form-control-ios w-100" required></div>
                            <div class="col-md-3"><label class="form-label-ios">Tanggal Selesai *</label><input type="datetime-local" name="tanggal_selesai" class="form-control-ios w-100" required></div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label-ios">Metode Soal</label>
                                <select name="metode_soal" id="metodeSoalSelect" class="form-select-ios w-100"><option value="random">Random</option><option value="manual">Manual</option></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-ios d-block mb-2">Pengaturan</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-bottom: 8px;"><input type="checkbox" name="acak_opsi" value="1" checked> Acak opsi jawaban</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin-bottom: 8px;"><input type="checkbox" name="tampilkan_nilai" value="1" checked> Tampilkan nilai</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;"><input type="checkbox" name="tampilkan_pembahasan" value="1"> Tampilkan pembahasan</label>
                            </div>
                        </div>

                        @include('ujian.partials.soal-picker')

                        @include('ujian.partials.kelas-picker', ['selectedKelas' => []])

                        <div class="mb-4"><label class="form-label-ios">Instruksi Ujian</label><textarea name="instruksi" class="form-control-ios w-100" rows="3" placeholder="Petunjuk pengerjaan ujian...">{{ old('instruksi') }}</textarea></div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg"></i> Buat Ujian</button>
                            <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
