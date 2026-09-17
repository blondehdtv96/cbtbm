{{--
    Body of a single soal: everything that can change when an admin edits the
    question while the exam is running (type, text, image, options/essay
    box). Rendered both inline by exam.mengerjakan and standalone by
    ExamController@soalContent for the real-time refresh triggered by
    SoalUpdated. Excludes the outer .question-card wrapper and the
    prev/next navigation buttons, which don't depend on soal content.
--}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <div class="question-number">{{ $index + 1 }}</div>
        <span class="question-meta-chip d-none d-sm-inline-flex">
            <i class="bi {{ $soal->tipe_soal === 'essay' ? 'bi-pencil-square' : 'bi-list-check' }}"></i>
            {{ $soal->tipe_soal === 'essay' ? 'Esai' : 'Pilihan Ganda' }}
        </span>
    </div>
    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 5px 12px; background: rgba(245, 158, 11, 0.08); border-radius: 10px; font-size: 12px; font-weight: 600; color: #f59e0b;">
        <input type="checkbox" class="ragu-checkbox" data-index="{{ $index }}" data-soal-id="{{ $soal->id }}"
               {{ in_array($soal->id, $raguRagu) ? 'checked' : '' }}
               onchange="toggleRagu({{ $index }}, {{ $soal->id }}, this.checked)">
        <i class="bi bi-flag-fill"></i> <span class="d-none d-sm-inline">Ragu-ragu</span>
    </label>
</div>

<div class="question-text">
    {!! nl2br(e($soal->pertanyaan)) !!}
</div>

@if($soal->gambar_soal)
    <button type="button" class="exam-image-frame" data-exam-image data-viewer-title="Gambar Soal Nomor {{ $index + 1 }}"
            onclick="event.stopPropagation(); openImageViewer(this.querySelector('img'), this.dataset.viewerTitle)"
            aria-label="Perbesar gambar soal nomor {{ $index + 1 }}">
        <img src="{{ asset('storage/' . $soal->gambar_soal) }}" alt="Gambar soal nomor {{ $index + 1 }}" loading="eager">
        <span class="exam-image-hint"><i class="bi bi-arrows-fullscreen"></i><span>Klik untuk perbesar</span></span>
    </button>
@endif

@if($soal->tipe_soal === 'pg' || $soal->tipe_soal === 'pg_kompleks')
    @foreach($soal->opsiJawabans as $opsi)
    <div class="option-pill {{ ($jawabans[$soal->id] ?? '') === $opsi->opsi_label ? 'selected' : '' }}"
         onclick="selectOption({{ $index }}, {{ $soal->id }}, '{{ $opsi->opsi_label }}', this)"
         id="option-{{ $index }}-{{ $opsi->opsi_label }}">
        <div class="option-label">{{ $opsi->opsi_label }}</div>
        <div class="flex-grow-1">
            @if($opsi->gambar_opsi)
                <button type="button" class="exam-image-frame option-image" data-exam-image data-viewer-title="Gambar Opsi {{ $opsi->opsi_label }} · Soal {{ $index + 1 }}"
                        onclick="event.stopPropagation(); openImageViewer(this.querySelector('img'), this.dataset.viewerTitle)"
                        aria-label="Perbesar gambar opsi {{ $opsi->opsi_label }} pada soal nomor {{ $index + 1 }}">
                    <img src="{{ asset('storage/' . $opsi->gambar_opsi) }}" alt="Gambar opsi {{ $opsi->opsi_label }} soal nomor {{ $index + 1 }}" loading="eager">
                    <span class="exam-image-hint"><i class="bi bi-arrows-fullscreen"></i><span>Klik untuk perbesar</span></span>
                </button>
            @endif
            {{ $opsi->isi_opsi }}
        </div>
        <div class="option-check"><i class="bi bi-check-lg"></i></div>
    </div>
    @endforeach
@elseif($soal->tipe_soal === 'essay')
    <textarea class="form-control-ios w-100" rows="6"
              placeholder="Tulis jawaban Anda di sini..."
              oninput="saveEssay({{ $index }}, {{ $soal->id }}, this.value)"
              >{{ $jawabans[$soal->id] ?? '' }}</textarea>

    <div class="essay-image-answer" data-soal-id="{{ $soal->id }}">
        <div class="essay-image-label">
            <i class="bi bi-camera-fill"></i> Atau upload foto jawaban (opsional)
        </div>

        <div class="essay-image-preview {{ isset($jawabanFiles[$soal->id]) ? '' : 'd-none' }}" id="essayImagePreviewWrap-{{ $index }}">
            <img src="{{ isset($jawabanFiles[$soal->id]) ? asset('storage/' . $jawabanFiles[$soal->id]) : '' }}" id="essayImagePreview-{{ $index }}" alt="Gambar jawaban">
            <button type="button" class="essay-image-remove" onclick="removeEssayImage({{ $index }}, {{ $soal->id }})">
                <i class="bi bi-trash3-fill"></i> Hapus Gambar
            </button>
        </div>

        <label class="essay-image-upload-btn {{ isset($jawabanFiles[$soal->id]) ? 'd-none' : '' }}" id="essayImageUploadBtn-{{ $index }}">
            <i class="bi bi-cloud-arrow-up-fill"></i> Pilih / Ambil Foto
            <input type="file" accept="image/*" capture="environment" class="d-none" onchange="uploadEssayImage({{ $index }}, {{ $soal->id }}, this)">
        </label>

        <div class="essay-image-status" id="essayImageStatus-{{ $index }}"></div>
    </div>
@endif
