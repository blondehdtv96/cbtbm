@extends('layouts.app')
@section('title', 'Detail Jawaban Siswa')
@section('page-title', 'Jawaban: ' . $peserta->siswa->nama)

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('ujian.hasil', $ujian->id) }}" class="btn btn-ios btn-ios-light">
            <i class="bi bi-arrow-left"></i> Kembali ke Hasil
        </a>
        <div class="badge-ios {{ $peserta->nilai >= 75 ? 'success' : ($peserta->nilai >= 50 ? 'warning' : 'danger') }}" style="font-size: 16px;">
            Nilai Akhir: {{ $peserta->nilai }}
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="card-ios mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Nama Ujian</h6>
                    <p class="fw-bold mb-3">{{ $ujian->nama_ujian }} ({{ $ujian->mapel->nama_mapel ?? '-' }})</p>
                    
                    <h6 class="text-muted mb-1">Peserta</h6>
                    <p class="fw-bold mb-0">{{ $peserta->siswa->nama }} ({{ $peserta->siswa->nis }}) - {{ $peserta->siswa->kelas->nama_kelas ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Waktu Pengerjaan</h6>
                    <p class="fw-bold mb-3">
                        {{ $peserta->waktu_mulai ? $peserta->waktu_mulai->format('d M Y H:i:s') : '-' }} s/d 
                        {{ $peserta->waktu_selesai ? $peserta->waktu_selesai->format('H:i:s') : '-' }}
                        @if($peserta->waktu_mulai && $peserta->waktu_selesai)
                            <span class="text-muted fw-normal">({{ $peserta->waktu_mulai->diffForHumans($peserta->waktu_selesai, true) }})</span>
                        @endif
                    </p>
                    
                    <h6 class="text-muted mb-1">Status</h6>
                    <span class="badge-ios {{ $peserta->status == 'selesai' ? 'success' : 'warning' }}">{{ ucfirst($peserta->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('ujian.peserta.nilai', [$ujian->id, $peserta->id]) }}" method="POST">
        @csrf
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Daftar Jawaban</h5>
            <button type="submit" class="btn btn-ios btn-ios-primary">
                <i class="bi bi-save"></i> Simpan Nilai Manual
            </button>
        </div>

        @foreach($jawabans as $index => $jawaban)
            @php
                $soal = $jawaban->bankSoal;
                if (!$soal) continue;
            @endphp
            <div class="card-ios mb-4 border-start border-4 {{ $jawaban->nilai == $soal->bobot_nilai ? 'border-success' : ($jawaban->nilai > 0 ? 'border-warning' : 'border-danger') }}">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                    <span class="fw-bold">Soal No. {{ $index + 1 }}</span>
                    <span class="badge bg-secondary">Tipe: {{ strtoupper($soal->tipe_soal) }}</span>
                </div>
                <div class="card-body">
                    <!-- Pertanyaan -->
                    <div class="mb-3">
                        <div class="form-label text-muted">Pertanyaan:</div>
                        <div class="p-3 bg-light rounded" style="font-size: 15px;">
                            {!! nl2br(e($soal->pertanyaan)) !!}
                        </div>
                        @if($soal->gambar_soal)
                            <div class="mt-2 text-center">
                                <img src="{{ asset('storage/' . $soal->gambar_soal) }}" alt="Gambar Soal" style="max-height: 200px; border-radius: 8px;">
                            </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Jawaban Siswa -->
                            <div class="mb-3">
                                <div class="form-label text-muted">Jawaban Siswa:</div>
                                @if($soal->tipe_soal === 'essay')
                                    @php
                                        $adaTeks = $jawaban->jawaban_dipilih && trim($jawaban->jawaban_dipilih) !== '';
                                        $adaGambar = !empty($jawaban->jawaban_file);
                                    @endphp
                                    @if($adaTeks || $adaGambar)
                                        @if($adaTeks)
                                            <div class="p-3 rounded border bg-white" style="min-height: 80px; font-size: 15px;">
                                                {!! nl2br(e($jawaban->jawaban_dipilih)) !!}
                                            </div>
                                        @endif
                                        @if($adaGambar)
                                            <div class="{{ $adaTeks ? 'mt-2' : '' }} text-center">
                                                <a href="{{ asset('storage/' . $jawaban->jawaban_file) }}" target="_blank" rel="noopener">
                                                    <img src="{{ asset('storage/' . $jawaban->jawaban_file) }}" alt="Gambar Jawaban" style="max-width: 100%; max-height: 350px; border-radius: 8px; border: 1px solid #dee2e6;">
                                                </a>
                                                <div class="form-text">Klik gambar untuk memperbesar</div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="p-3 rounded border bg-light text-muted" style="min-height: 80px; font-size: 15px;">
                                            <em>Tidak ada jawaban</em>
                                        </div>
                                    @endif
                                @else
                                    @php
                                        // Cari opsi yang dipilih
                                        $opsiDipilih = $soal->opsiJawabans->where('opsi_label', $jawaban->jawaban_dipilih)->first();
                                        // Cari opsi yang benar
                                        $opsiBenar = $soal->opsiJawabans->where('is_correct', true)->first();
                                        // Cek apakah benar-benar tidak menjawab
                                        $tidakMenjawab = !$jawaban->jawaban_dipilih || trim($jawaban->jawaban_dipilih) === '';
                                    @endphp
                                    
                                    @if($tidakMenjawab)
                                        <div class="p-3 rounded border bg-light text-muted">
                                            <i class="bi bi-x-circle"></i> <em>Tidak menjawab</em>
                                        </div>
                                    @else
                                        <div class="p-3 rounded border {{ $jawaban->is_correct ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                                            <strong>Opsi: {{ $jawaban->jawaban_dipilih }}</strong><br>
                                            {{ $opsiDipilih ? $opsiDipilih->isi_opsi : '[Opsi tidak ditemukan]' }}
                                        </div>
                                    @endif
                                    
                                    @if(!$tidakMenjawab && !$jawaban->is_correct && $opsiBenar)
                                        <div class="mt-2 text-success small fw-bold">
                                            <i class="bi bi-check-circle"></i> Jawaban Benar: Opsi {{ $opsiBenar->opsi_label }} ({{ $opsiBenar->isi_opsi }})
                                        </div>
                                    @elseif($tidakMenjawab && $opsiBenar)
                                        <div class="mt-2 text-info small fw-bold">
                                            <i class="bi bi-info-circle"></i> Jawaban Benar: Opsi {{ $opsiBenar->opsi_label }} ({{ $opsiBenar->isi_opsi }})
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Input Nilai -->
                            <div class="mb-3 p-3 bg-light rounded border">
                                <label class="form-label fw-bold">Nilai (Maks: {{ $soal->bobot_nilai }})</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="nilai[{{ $jawaban->id }}]" 
                                           class="form-control" 
                                           value="{{ $jawaban->nilai }}" 
                                           min="0" 
                                           max="{{ $soal->bobot_nilai }}" 
                                           step="0.01" 
                                           required>
                                    <span class="input-group-text">/ {{ $soal->bobot_nilai }}</span>
                                </div>
                                @if($soal->tipe_soal === 'essay')
                                    <div class="form-text text-primary"><i class="bi bi-info-circle"></i> Berikan nilai manual untuk essay ini.</div>
                                @else
                                    <div class="form-text text-muted">Abaikan jika tidak ingin mengubah nilai PG.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-ios btn-ios-primary btn-lg px-5">
                <i class="bi bi-save"></i> Simpan Nilai
            </button>
        </div>
    </form>
</div>
@endsection
