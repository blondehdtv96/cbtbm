@extends('layouts.app')
@section('title', 'Cek Soal — ' . $mapel->nama_mapel)
@section('page-title', 'Cek Soal: ' . $mapel->nama_mapel)
@section('page-subtitle', 'Tandai setiap soal benar atau salah, beri catatan bila ada kekeliruan')

@section('content')
<div class="fade-in">

    <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px; font-size:13px; color:#64748b;">
        <a href="{{ route('guru.review-soal.index') }}" style="color:#2563eb; font-weight:600; text-decoration:none;">
            <i class="bi bi-clipboard-check-fill me-1"></i>Cek Soal
        </a>
        <i class="bi bi-chevron-right" style="font-size:11px;"></i>
        <span style="font-weight:600; color:#0f172a;">{{ $mapel->kode_mapel }} — {{ $mapel->nama_mapel }}</span>
    </div>

    @unless($ready)
        <div class="alert alert-warning" style="border-radius:14px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Fitur cek soal belum aktif di server ini, jadi penilaian belum bisa disimpan. Minta admin menjalankan <code>php artisan migrate</code>.
        </div>
    @endunless

    {{-- Filter berdasarkan status cek --}}
    @php
        $filterCards = [
            'semua' => ['label' => 'Semua Soal',  'value' => $stats['total'], 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.08)', 'icon' => 'bi-collection-fill'],
            'belum' => ['label' => 'Belum Dicek', 'value' => $stats['belum'], 'color' => '#d97706', 'bg' => 'rgba(245,158,11,0.08)', 'icon' => 'bi-hourglass-split'],
            'benar' => ['label' => 'Benar',       'value' => $stats['benar'], 'color' => '#16a34a', 'bg' => 'rgba(34,197,94,0.08)',  'icon' => 'bi-check-circle-fill'],
            'salah' => ['label' => 'Salah',       'value' => $stats['salah'], 'color' => '#dc2626', 'bg' => 'rgba(239,68,68,0.08)',  'icon' => 'bi-x-circle-fill'],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach($filterCards as $key => $card)
            <div class="col-6 col-md-3">
                <a href="{{ route('guru.review-soal.show', array_filter(['mapel' => $mapel->id, 'filter' => $key === 'semua' ? null : $key, 'tipe_soal' => request('tipe_soal'), 'search' => request('search')])) }}"
                   style="display:block; text-decoration:none; background:{{ $card['bg'] }}; border:2px solid {{ $filter === $key ? $card['color'] : 'transparent' }}; border-radius:14px; padding:16px 20px;">
                    <div style="font-size:24px; font-weight:800; color:{{ $card['color'] }};"><i class="bi {{ $card['icon'] }}" style="font-size:18px; vertical-align:2px;"></i> {{ $card['value'] }}</div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">{{ $card['label'] }}</div>
                </a>
            </div>
        @endforeach
    </div>

    <form class="d-flex gap-2 flex-wrap mb-3" method="GET">
        @if($filter !== 'semua')<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <div style="position:relative;">
            <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
            <input type="text" name="search" class="form-control-ios" placeholder="Cari soal..." value="{{ request('search') }}" style="width:220px; padding-left:36px;">
        </div>
        <select name="tipe_soal" class="form-select-ios" style="width:150px;" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            <option value="pg" @selected(request('tipe_soal') == 'pg')>Pilihan Ganda</option>
            <option value="pg_kompleks" @selected(request('tipe_soal') == 'pg_kompleks')>PG Kompleks</option>
            <option value="essay" @selected(request('tipe_soal') == 'essay')>Essay</option>
            <option value="menjodohkan" @selected(request('tipe_soal') == 'menjodohkan')>Menjodohkan</option>
        </select>
        <button type="submit" class="btn btn-ios btn-ios-light"><i class="bi bi-search"></i></button>
        <a href="{{ route('guru.review-soal.index') }}" class="btn btn-ios btn-ios-light ms-auto"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </form>

    @forelse($soals as $soal)
        @php
            $review = $ready ? $soal->reviews->first() : null;
            $nomor = $soals->firstItem() + $loop->index;
            $hasError = $errors->any() && old('_soal_id') == $soal->id;
            $borderColor = $review ? ($review->status === 'benar' ? 'rgba(34,197,94,0.45)' : 'rgba(239,68,68,0.45)') : '#e2e8f0';
        @endphp
        <div class="card-ios mb-3" id="soal-{{ $soal->id }}" style="border:1px solid {{ $borderColor }}; scroll-margin-top:20px;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span style="font-weight:800; color:#0f172a;">Soal {{ $nomor }}</span>
                    <span class="badge-ios {{ $soal->tipe_soal === 'essay' ? 'info' : 'purple' }}">{{ strtoupper(str_replace('_', ' ', $soal->tipe_soal)) }}</span>
                    <span class="badge-ios primary">Bobot {{ $soal->bobot_nilai }}</span>
                    <small style="color:#94a3b8;">#{{ $soal->id }} · oleh {{ $soal->guru->nama ?? '-' }}</small>
                </div>
                @if($review)
                    <span class="badge-ios {{ $review->status === 'benar' ? 'success' : 'danger' }}">
                        <i class="bi {{ $review->status === 'benar' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                        Ditandai {{ strtoupper($review->status) }} · {{ $review->updated_at->diffForHumans() }}
                    </span>
                @else
                    <span class="badge-ios warning"><i class="bi bi-hourglass-split"></i> Belum dicek</span>
                @endif
            </div>
            <div class="card-body">
                <div style="font-size:15px; color:#0f172a; line-height:1.7; background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">
                    {!! nl2br(e($soal->pertanyaan)) !!}
                </div>

                @if($soal->gambar_soal)
                    <a href="{{ asset('storage/' . $soal->gambar_soal) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/' . $soal->gambar_soal) }}" alt="Gambar soal" style="max-width:100%; max-height:260px; border-radius:12px; border:1px solid #e2e8f0; margin-top:14px;">
                    </a>
                @endif

                @if($soal->opsiJawabans->isNotEmpty())
                    <div style="margin-top:16px;">
                        <div style="font-weight:700; font-size:13px; color:#334155; margin-bottom:10px;">
                            <i class="bi bi-list-check me-1" style="color:#6366f1;"></i>Opsi Jawaban <small style="color:#94a3b8; font-weight:500;">(hijau = kunci jawaban)</small>
                        </div>
                        @foreach($soal->opsiJawabans as $opsi)
                            <div style="display:flex; align-items:center; gap:12px; padding:10px 14px; margin-bottom:6px; border-radius:10px; background:{{ $opsi->is_correct ? 'rgba(34,197,94,0.08)' : '#f8fafc' }}; border:1px solid {{ $opsi->is_correct ? 'rgba(34,197,94,0.3)' : '#e2e8f0' }};">
                                <div style="width:30px; height:30px; border-radius:8px; background:{{ $opsi->is_correct ? 'linear-gradient(135deg,#22c55e,#10b981)' : '#e2e8f0' }}; color:{{ $opsi->is_correct ? 'white' : '#64748b' }}; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">{{ $opsi->opsi_label }}</div>
                                <div style="flex-grow:1; font-size:14px; color:{{ $opsi->is_correct ? '#166534' : '#334155' }}; font-weight:{{ $opsi->is_correct ? 600 : 400 }};">{{ $opsi->isi_opsi }}</div>
                                @if($opsi->gambar_opsi)
                                    <a href="{{ asset('storage/' . $opsi->gambar_opsi) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $opsi->gambar_opsi) }}" alt="Gambar opsi {{ $opsi->opsi_label }}" style="width:44px; height:44px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0;">
                                    </a>
                                @endif
                                @if($opsi->is_correct)
                                    <span class="badge-ios success" style="font-size:10px;"><i class="bi bi-key-fill"></i> Kunci</span>
                                @endif
                            </div>
                        @endforeach
                        @if(in_array($soal->tipe_soal, ['pg', 'pg_kompleks']) && $soal->opsiJawabans->where('is_correct', true)->isEmpty())
                            <div style="color:#dc2626; font-size:13px; font-weight:600; margin-top:6px;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Soal ini belum memiliki kunci jawaban.
                            </div>
                        @endif
                    </div>
                @elseif($soal->tipe_soal === 'essay')
                    <div style="margin-top:12px; font-size:13px; color:#64748b;"><i class="bi bi-info-circle me-1"></i>Soal essay — periksa kejelasan pertanyaannya.</div>
                @endif

                @if($ready)
                    <form method="POST" action="{{ route('guru.review-soal.store', $soal) }}" style="margin-top:18px; padding-top:16px; border-top:1px dashed #e2e8f0;">
                        @csrf
                        <input type="hidden" name="_soal_id" value="{{ $soal->id }}">
                        <label class="form-label-ios" for="catatan-{{ $soal->id }}">Catatan <small style="color:#94a3b8; font-weight:500;">(wajib jika soal salah)</small></label>
                        <textarea name="catatan" id="catatan-{{ $soal->id }}" rows="2" class="form-control-ios w-100 {{ $hasError ? 'is-invalid' : '' }}"
                            placeholder="Contoh: kunci jawaban seharusnya C, atau opsi B dan D sama.">{{ $hasError ? old('catatan') : ($review->catatan ?? '') }}</textarea>
                        @if($hasError)
                            <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $errors->first('catatan') ?: $errors->first('status') }}</div>
                        @endif
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="submit" name="status" value="benar" class="btn btn-ios btn-ios-success">
                                <i class="bi bi-check-lg me-1"></i> Soal Benar
                            </button>
                            <button type="submit" name="status" value="salah" class="btn btn-ios btn-ios-danger">
                                <i class="bi bi-x-lg me-1"></i> Soal Salah
                            </button>
                            @if($review)
                                <button type="submit" form="batal-{{ $soal->id }}" class="btn btn-ios btn-ios-light ms-auto">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan Penilaian
                                </button>
                            @endif
                        </div>
                    </form>
                    @if($review)
                        <form id="batal-{{ $soal->id }}" method="POST" action="{{ route('guru.review-soal.destroy', $soal) }}" style="display:none;">
                            @csrf @method('DELETE')
                        </form>
                    @endif
                @endif
            </div>
        </div>
    @empty
        <div class="card-ios">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-clipboard-check"></i>
                    <h5>Tidak ada soal</h5>
                    <p>{{ $filter === 'belum' ? 'Semua soal sudah Anda cek.' : 'Tidak ada soal yang cocok dengan filter ini.' }}</p>
                </div>
            </div>
        </div>
    @endforelse

    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $soals->links() }}</div>
</div>

@if(old('_soal_id'))
@push('scripts')
<script>
    document.getElementById('soal-{{ (int) old('_soal_id') }}')?.scrollIntoView({ block: 'start' });
    document.getElementById('catatan-{{ (int) old('_soal_id') }}')?.focus({ preventScroll: true });
</script>
@endpush
@endif
@endsection
