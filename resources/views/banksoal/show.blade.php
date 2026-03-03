@extends('layouts.app')
@section('title', 'Detail Soal')
@section('page-title', 'Detail Soal')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ios mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Detail Soal #{{ $banksoal->id }}</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('banksoal.edit', $banksoal) }}" class="btn btn-ios btn-ios-sm btn-ios-light"><i class="bi bi-pencil"></i> Edit</a>
                        <a href="{{ route('banksoal.index') }}" class="btn btn-ios btn-ios-sm btn-ios-light"><i class="bi bi-arrow-left"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <span class="badge-ios info">{{ $banksoal->mapel->nama_mapel ?? '-' }}</span>
                        <span class="badge-ios purple">{{ strtoupper($banksoal->tipe_soal) }}</span>
                        <span class="badge-ios {{ $banksoal->tingkat_kesulitan == 'mudah' ? 'success' : ($banksoal->tingkat_kesulitan == 'sedang' ? 'warning' : 'danger') }}">{{ ucfirst($banksoal->tingkat_kesulitan) }}</span>
                        <span class="badge-ios primary">Bobot: {{ $banksoal->bobot_nilai }}</span>
                    </div>

                    <div class="question-text mb-4">
                        {!! nl2br(e($banksoal->pertanyaan)) !!}
                    </div>

                    @if($banksoal->gambar_soal)
                        <img src="{{ asset('storage/' . $banksoal->gambar_soal) }}" alt="Gambar Soal" style="max-width: 400px; border-radius: 12px; margin-bottom: 20px;">
                    @endif

                    @if($banksoal->opsiJawabans->count() > 0)
                        <h6 style="font-weight: 700; margin-bottom: 12px;">Opsi Jawaban:</h6>
                        @foreach($banksoal->opsiJawabans as $opsi)
                            <div class="option-pill {{ $opsi->is_correct ? 'selected' : '' }}" style="cursor: default;">
                                <div class="option-label">{{ $opsi->opsi_label }}</div>
                                <div class="flex-grow-1">{{ $opsi->isi_opsi }}</div>
                                @if($opsi->is_correct)
                                    <span class="badge-ios success"><i class="bi bi-check-lg"></i> Benar</span>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    @if($banksoal->pembahasan)
                        <div style="margin-top: 20px; padding: 16px; background: rgba(37, 99, 235, 0.05); border-radius: 12px; border: 1px solid rgba(37, 99, 235, 0.1);">
                            <h6 style="font-weight: 700; color: var(--primary); margin-bottom: 8px;"><i class="bi bi-lightbulb-fill me-1"></i>Pembahasan:</h6>
                            <p style="font-size: 14px; margin: 0;">{{ $banksoal->pembahasan }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
