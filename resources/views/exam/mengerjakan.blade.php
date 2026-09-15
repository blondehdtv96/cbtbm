<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $ujian->nama_ujian }} - {{ app_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0f2f5 0%, #e8ecf4 100%);
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -webkit-touch-callout: none;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Essay answer image upload */
        .essay-image-answer {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed var(--border-color);
        }
        .essay-image-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        .essay-image-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: 1.5px dashed var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            transition: var(--transition);
        }
        .essay-image-upload-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        .essay-image-preview {
            display: inline-block;
            max-width: 100%;
        }
        .essay-image-preview img {
            display: block;
            max-width: 100%;
            max-height: 280px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            object-fit: contain;
        }
        .essay-image-remove {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 7px 14px;
            border: none;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.08);
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .essay-image-remove:hover {
            background: rgba(220, 38, 38, 0.14);
        }
        .essay-image-status {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        .essay-image-status.uploading { color: var(--text-secondary); }
        .essay-image-status.success { color: #16a34a; }
        .essay-image-status.error { color: #dc2626; }

        /* Exam shell: flex column sized to the real viewport, so the body
           never relies on a hardcoded header-height subtraction (which broke
           on mobile once the header shrank via media queries below). */
        .exam-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }
        .exam-shell .exam-header {
            flex-shrink: 0;
            padding-top: calc(16px + env(safe-area-inset-top));
        }
        .exam-shell .exam-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        /* Mobile sidebar toggle */
        .mobile-sidebar-toggle {
            display: none;
        }
        /* Bottom-sheet backdrop, shown only while the mobile soal navigator is expanded */
        .mobile-sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 100;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .mobile-sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }
        @media (max-width: 767.98px) {
            .exam-shell .exam-header {
                padding-top: calc(10px + env(safe-area-inset-top)) !important;
            }
            .exam-header {
                padding: 10px 14px !important;
            }
            .exam-header .logo-icon {
                width: 32px !important;
                height: 32px !important;
                border-radius: 8px !important;
                font-size: 14px !important;
            }
            .exam-header-info {
                font-size: 13px !important;
            }
            .exam-header-info div:first-child {
                font-size: 13px !important;
                max-width: 140px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .exam-header-info div:last-child {
                font-size: 10px !important;
            }
            .exam-timer {
                padding: 7px 12px !important;
                font-size: 14px !important;
                border-radius: 10px !important;
                gap: 6px !important;
            }
            .exam-body {
                padding: 12px !important;
                gap: 12px !important;
            }
            .question-card {
                padding: 18px !important;
                border-radius: 14px !important;
            }
            .question-number {
                width: 30px !important;
                height: 30px !important;
                font-size: 12px !important;
                border-radius: 8px !important;
                margin-bottom: 10px !important;
            }
            .question-text {
                font-size: 14px !important;
                margin-bottom: 14px !important;
            }
            .option-pill {
                padding: 11px 14px !important;
                font-size: 13px !important;
                border-radius: 12px !important;
                margin-bottom: 8px !important;
                gap: 10px !important;
            }
            .option-pill .option-label {
                width: 28px !important;
                height: 28px !important;
                font-size: 12px !important;
                border-radius: 8px !important;
            }
            .btn-ios {
                padding: 9px 14px !important;
                font-size: 12px !important;
            }
            .essay-image-upload-btn {
                width: 100%;
                justify-content: center;
                padding: 12px !important;
            }
            .essay-image-preview img {
                max-height: 200px;
            }
            .exam-sidebar {
                padding: 10px 12px calc(10px + env(safe-area-inset-bottom)) !important;
                border-radius: 16px 16px 0 0 !important;
                z-index: 101 !important;
            }
            .mobile-sidebar-toggle {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                padding: 16px 0 6px;
                cursor: pointer;
                background: none;
                border: none;
                width: 100%;
                color: var(--text-primary);
            }
            .mobile-sheet-handle {
                position: absolute;
                top: 6px;
                left: 50%;
                transform: translateX(-50%);
                width: 36px;
                height: 4px;
                border-radius: 2px;
                background: var(--border-color);
            }
            /* Minimized by default (max-height: 0); expands smoothly instead of
               snapping with display:none/block, and scrolls internally so a long
               soal list can't push past the viewport. */
            .sidebar-content-mobile {
                max-height: 0;
                overflow: hidden;
                padding-top: 0;
                transition: max-height 0.28s ease;
            }
            .sidebar-content-mobile.show {
                max-height: min(50vh, 360px);
                overflow-y: auto;
                padding-top: 10px;
            }
            .soal-nav-item {
                font-size: 11px !important;
                border-radius: 8px !important;
            }
            .exam-questions {
                padding-bottom: 100px !important;
            }
            .progress-ios {
                height: 6px !important;
            }
        }

        @media (max-width: 380px) {
            .exam-header-info div:first-child {
                max-width: 100px !important;
            }
            .exam-timer {
                padding: 6px 10px !important;
                font-size: 12px !important;
            }
            .question-card {
                padding: 14px !important;
            }
            .option-pill {
                padding: 10px 12px !important;
                font-size: 12.5px !important;
            }
            .soal-nav-grid {
                grid-template-columns: repeat(6, 1fr) !important;
            }
            .mobile-sidebar-toggle .btn-label {
                display: none;
            }
        }

        /* Review All Questions Modal */
        .review-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 10001;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .review-modal-overlay.show { display: flex; }
        .review-modal {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .review-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .review-modal-header h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .review-modal-close {
            background: var(--bg-secondary);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .review-modal-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 16px 20px;
        }
        .review-stat {
            text-align: center;
            padding: 10px;
            border-radius: 12px;
            background: var(--bg-secondary);
        }
        .review-stat span {
            display: block;
            font-size: 20px;
            font-weight: 800;
        }
        .review-stat label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }
        .review-stat.answered span { color: #10b981; }
        .review-stat.unanswered span { color: #64748b; }
        .review-stat.doubt span { color: #f59e0b; }
        .review-modal-tabs {
            display: flex;
            gap: 6px;
            padding: 0 20px 12px;
            overflow-x: auto;
        }
        .review-tab {
            border: 1px solid var(--border-color);
            background: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            color: var(--text-secondary);
        }
        .review-tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .review-modal-grid {
            flex: 1;
            overflow-y: auto;
            padding: 4px 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .review-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: transform 0.15s;
        }
        .review-item:hover { transform: translateX(2px); }
        .review-item-number {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            flex-shrink: 0;
        }
        .review-item.answered .review-item-number { background: linear-gradient(135deg, #22c55e, #10b981); }
        .review-item.doubt .review-item-number { background: linear-gradient(135deg, #f59e0b, #f97316); }
        .review-item.unanswered .review-item-number { background: #94a3b8; }
        .review-item-status {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .review-empty {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
            font-size: 13px;
        }
        .review-modal-footer {
            padding: 14px 20px 20px;
            border-top: 1px solid var(--border-color);
        }
        @media (max-width: 480px) {
            .review-modal { max-height: 90vh; }
            .review-modal-stats { gap: 6px; padding: 12px 14px; }
            .review-modal-tabs { padding: 0 14px 10px; }
            .review-modal-grid { padding: 4px 14px 10px; }
        }

        /* Image viewer: question/option images open at their real resolution and
           can be zoomed independently without changing browser viewport size. */
        .current-image-button {
            display: none; align-items: center; gap: 7px; min-height: 36px; padding: 8px 12px;
            border: 1px solid rgba(37, 99, 235, .18); border-radius: 11px;
            color: var(--primary); background: rgba(37, 99, 235, .07);
            font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
        }
        .current-image-button.is-visible { display: inline-flex; }
        .current-image-button:hover { color: #fff; background: var(--primary); }
        .exam-image-frame {
            position: relative; display: block; width: fit-content; max-width: 100%; margin: 0 0 16px;
            padding: 0; overflow: hidden; border: 1px solid var(--border-color); border-radius: 13px;
            background: #f8fafc; cursor: zoom-in; box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
        }
        .exam-image-frame.option-image { margin-bottom: 9px; border-radius: 11px; }
        .exam-image-frame img { display: block; max-width: 100%; height: auto; border-radius: inherit; }
        .exam-image-hint {
            position: absolute; right: 8px; bottom: 8px; display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 10px; border-radius: 9px; color: #fff; background: rgba(15, 23, 42, .78);
            font-size: 10px; font-weight: 700; pointer-events: none; backdrop-filter: blur(5px);
        }
        .image-viewer {
            display: none; position: fixed; inset: 0; z-index: 10500; grid-template-rows: auto minmax(0, 1fr) auto;
            color: #fff; background: rgba(2, 6, 23, .97); user-select: none;
        }
        .image-viewer.is-visible { display: grid; animation: imageViewerFade .18s ease-out; }
        .image-viewer__header {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.1); background: rgba(15,23,42,.92);
        }
        .image-viewer__title { min-width: 0; }
        .image-viewer__title strong { display: block; overflow: hidden; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; }
        .image-viewer__title small { display: block; margin-top: 2px; color: #94a3b8; font-size: 10px; }
        .image-viewer__tools { display: flex; align-items: center; gap: 6px; }
        .image-viewer__tools button {
            display: inline-flex; align-items: center; justify-content: center; height: 36px; min-width: 36px;
            padding: 0 10px; border: 1px solid rgba(255,255,255,.14); border-radius: 10px;
            color: #e2e8f0; background: rgba(255,255,255,.08); font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .image-viewer__tools button:hover:not(:disabled) { color: #fff; background: rgba(255,255,255,.16); }
        .image-viewer__tools button:disabled { opacity: .35; cursor: not-allowed; }
        .image-viewer__tools .image-viewer__close { margin-left: 4px; color: #fecaca; background: rgba(220,38,38,.16); }
        .image-viewer__level { min-width: 52px; color: #fff; font-size: 12px; font-weight: 800; text-align: center; }
        .image-viewer__viewport {
            position: relative; min-height: 0; overflow: auto; overscroll-behavior: contain;
            cursor: grab; scrollbar-color: #64748b #0f172a; touch-action: pan-x pan-y;
        }
        .image-viewer__viewport.is-dragging { cursor: grabbing; }
        .image-viewer__canvas { position: relative; min-width: 100%; min-height: 100%; }
        .image-viewer__image {
            position: absolute; display: block; max-width: none; height: auto;
            border-radius: 4px; box-shadow: 0 18px 60px rgba(0,0,0,.5); -webkit-user-drag: none;
        }
        .image-viewer__footer {
            padding: 9px 14px calc(9px + env(safe-area-inset-bottom)); border-top: 1px solid rgba(255,255,255,.08);
            color: #94a3b8; background: rgba(15,23,42,.92); font-size: 10px; text-align: center;
        }
        @keyframes imageViewerFade { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) {
            .current-image-button { width: 36px; padding: 0; justify-content: center; }
            .current-image-button span { display: none; }
            .exam-image-hint span { display: none; }
            .image-viewer__header { align-items: flex-start; flex-direction: column; padding: 10px 12px; }
            .image-viewer__title { width: 100%; padding-right: 42px; }
            .image-viewer__tools { width: 100%; justify-content: center; }
            .image-viewer__tools button { flex: 1; max-width: 52px; }
            .image-viewer__tools .image-viewer__fit { max-width: 78px; }
            .image-viewer__tools .image-viewer__close { position: absolute; top: 9px; right: 10px; width: 36px; }
        }
    </style>
</head>
<body class="exam-fullscreen">
    <!-- Exam Shell (flex column: header + scrollable body, sized to real viewport) -->
    <div class="exam-shell">
    <!-- Exam Header -->
    <div class="exam-header">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="logo-icon" style="width: 38px; height: 38px; border-radius: 10px; font-size: 16px;">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="exam-header-info">
                <div style="font-weight: 700; font-size: 15px;">{{ $ujian->nama_ujian }}</div>
                <div style="font-size: 12px; color: var(--text-secondary);">{{ $ujian->mapel->nama_mapel ?? '' }} • {{ $ujian->jumlah_soal }} Soal</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="d-none d-md-flex align-items-center gap-2" style="font-size: 13px; color: var(--text-secondary);">
                <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 8px; font-size: 11px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                {{ auth()->user()->name }}
            </div>

            <button type="button" class="current-image-button" id="currentImageButton" onclick="openCurrentQuestionImage()" aria-label="Perbesar gambar pada soal ini">
                <i class="bi bi-zoom-in"></i><span>Perbesar Gambar</span>
            </button>

            <div class="exam-timer" id="examTimer">
                <i class="bi bi-clock-fill"></i>
                <span id="timerDisplay">--:--:--</span>
            </div>
        </div>
    </div>

    <!-- Exam Body -->
    <div class="exam-body">
        <!-- Questions Area -->
        <div class="exam-questions" id="questionsArea" style="overflow-y: auto; padding-bottom: 40px;">
            <!-- Progress Bar -->
            <div class="d-flex align-items-center justify-content-between mb-2" style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">
                <span>Progres Pengerjaan</span>
                <span id="progressLabel">0%</span>
            </div>
            <div class="progress-ios mb-4">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>

            @foreach($soals as $index => $soal)
            <div class="question-card" id="soal-{{ $index }}" style="{{ $index > 0 ? 'display:none;' : '' }}">
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

                <!-- Navigation -->
                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-ios btn-ios-light" onclick="goToSoal({{ $index - 1 }})" {{ $index === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline">Sebelumnya</span>
                    </button>
                    @if($index === $soals->count() - 1)
                        <button class="btn btn-ios btn-ios-success" onclick="confirmSubmit()">
                            <i class="bi bi-check-circle-fill"></i> Selesai
                        </button>
                    @else
                        <button class="btn btn-ios btn-ios-primary" onclick="goToSoal({{ $index + 1 }})">
                            <span class="d-none d-sm-inline">Selanjutnya</span> <i class="bi bi-chevron-right"></i>
                        </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Mobile bottom-sheet backdrop (dims screen when soal navigator is expanded) -->
        <div class="mobile-sidebar-backdrop d-md-none" id="sidebarBackdrop" onclick="closeMobileSidebar()"></div>

        <!-- Sidebar Navigation -->
        <div class="exam-sidebar">
            <div style="position: sticky; top: 0;">
                <!-- Mobile Toggle Button (tap to minimize/expand the soal navigator) -->
                <button class="mobile-sidebar-toggle" onclick="toggleMobileSidebar()" aria-expanded="false" aria-controls="sidebarContent">
                    <div class="mobile-sheet-handle d-md-none"></div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-weight: 700; font-size: 13px;" id="mobileProgress">0/{{ $soals->count() }} dijawab</span>
                        <span id="mobileDoubtBadge" style="display:none; font-weight: 700; font-size: 11px; color: #b45309; background: rgba(245,158,11,0.15); padding: 3px 8px; border-radius: 10px;">
                            <i class="bi bi-flag-fill"></i> <span id="mobileDoubtCount">0</span> ragu
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-ios btn-ios-light btn-ios-sm" onclick="event.stopPropagation(); openReviewModal();" style="padding: 6px 12px !important;" title="Lihat Semua Soal">
                            <i class="bi bi-grid-3x3-gap-fill"></i> <span class="btn-label">Lihat Semua</span>
                        </button>
                        <button class="btn btn-ios btn-ios-success btn-ios-sm" onclick="event.stopPropagation(); confirmSubmit();" style="padding: 6px 12px !important;" title="Kumpulkan Jawaban">
                            <i class="bi bi-send-fill"></i> <span class="btn-label">Kumpulkan</span>
                        </button>
                        <i class="bi bi-chevron-down" id="sidebarToggleIcon" style="transition: transform 0.3s; flex-shrink: 0;"></i>
                    </div>
                </button>

                <!-- Sidebar Content (toggleable on mobile) -->
                <div class="sidebar-content-mobile" id="sidebarContent">
                    <div class="soal-nav-grid">
                        @foreach($soals as $index => $soal)
                        <div class="soal-nav-item {{ $index === 0 ? 'active' : '' }} {{ isset($jawabans[$soal->id]) && $jawabans[$soal->id] ? 'answered' : '' }} {{ in_array($soal->id, $raguRagu) ? 'doubt' : '' }}"
                             id="nav-{{ $index }}"
                             onclick="goToSoal({{ $index }})">
                            {{ $index + 1 }}
                        </div>
                        @endforeach
                    </div>

                    <div class="soal-nav-legend">
                        <div class="legend-item"><div class="legend-dot unanswered"></div> Belum</div>
                        <div class="legend-item"><div class="legend-dot answered"></div> Dijawab</div>
                        <div class="legend-item"><div class="legend-dot doubt"></div> Ragu</div>
                    </div>
                </div>

                <!-- Desktop only: progress + submit -->
                <div class="d-none d-md-block">
                    <div class="soal-nav-grid">
                        @foreach($soals as $index => $soal)
                        <div class="soal-nav-item {{ $index === 0 ? 'active' : '' }} {{ isset($jawabans[$soal->id]) && $jawabans[$soal->id] ? 'answered' : '' }} {{ in_array($soal->id, $raguRagu) ? 'doubt' : '' }}"
                             id="nav-desktop-{{ $index }}"
                             onclick="goToSoal({{ $index }})">
                            {{ $index + 1 }}
                        </div>
                        @endforeach
                    </div>

                    <div class="soal-nav-legend">
                        <div class="legend-item"><div class="legend-dot unanswered"></div> Belum</div>
                        <div class="legend-item"><div class="legend-dot answered"></div> Dijawab</div>
                        <div class="legend-item"><div class="legend-dot doubt"></div> Ragu</div>
                    </div>

                    <div style="margin-top: 16px;">
                        <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: var(--border-radius); padding: 18px; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);">
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.85); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">
                                <i class="bi bi-bar-chart-fill"></i> Progres
                            </div>
                            <div style="font-size: 30px; font-weight: 800; color: #fff; line-height: 1;" id="answeredCount">0</div>
                            <div style="font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 4px;">dari {{ $soals->count() }} soal dijawab</div>
                        </div>
                    </div>

                    <div id="doubtWarningBox" style="display:none; margin-top: 10px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 12px 14px; align-items: center; gap: 8px;">
                        <i class="bi bi-flag-fill" style="color:#f59e0b;"></i>
                        <span style="font-size: 12px; font-weight: 600; color: #b45309;"><span id="doubtCount">0</span> soal masih ditandai ragu-ragu</span>
                    </div>

                    <button class="btn btn-ios btn-ios-light w-100 mt-3" onclick="openReviewModal()" style="padding: 14px;">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Lihat Semua Soal
                    </button>

                    <button class="btn btn-ios btn-ios-success w-100 mt-2" onclick="confirmSubmit()" style="padding: 14px;">
                        <i class="bi bi-send-fill"></i> Kumpulkan Jawaban
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- /.exam-shell -->

    <!-- Review All Questions Modal -->
    <div class="review-modal-overlay" id="reviewModalOverlay" onclick="if(event.target===this) closeReviewModal();">
        <div class="review-modal">
            <div class="review-modal-header">
                <h3><i class="bi bi-grid-3x3-gap-fill"></i> Status Semua Soal</h3>
                <button class="review-modal-close" onclick="closeReviewModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="review-modal-stats">
                <div class="review-stat answered">
                    <span id="reviewAnsweredCount">0</span>
                    <label>Dijawab</label>
                </div>
                <div class="review-stat doubt">
                    <span id="reviewDoubtCount">0</span>
                    <label>Ragu-ragu</label>
                </div>
                <div class="review-stat unanswered">
                    <span id="reviewUnansweredCount">0</span>
                    <label>Belum</label>
                </div>
            </div>
            <div class="review-modal-tabs">
                <button class="review-tab active" data-filter="all" onclick="filterReview('all', this)">Semua</button>
                <button class="review-tab" data-filter="unanswered" onclick="filterReview('unanswered', this)">Belum Dijawab</button>
                <button class="review-tab" data-filter="doubt" onclick="filterReview('doubt', this)">Ragu-ragu</button>
                <button class="review-tab" data-filter="answered" onclick="filterReview('answered', this)">Dijawab</button>
            </div>
            <div class="review-modal-grid" id="reviewModalGrid"></div>
            <div class="review-modal-footer">
                <button class="btn btn-ios btn-ios-success w-100" onclick="confirmSubmit()">
                    <i class="bi bi-send-fill"></i> Kumpulkan Jawaban
                </button>
            </div>
        </div>
    </div>

    <!-- Full-screen image viewer for question and option images -->
    <div class="image-viewer" id="imageViewer" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="imageViewerTitle">
        <div class="image-viewer__header">
            <div class="image-viewer__title">
                <strong id="imageViewerTitle">Gambar Soal</strong>
                <small>Gunakan tombol zoom, scroll, atau geser gambar untuk melihat detail.</small>
            </div>
            <div class="image-viewer__tools">
                <button type="button" id="imageZoomOut" onclick="adjustImageZoom(-1)" aria-label="Perkecil gambar"><i class="bi bi-dash-lg"></i></button>
                <span class="image-viewer__level" id="imageZoomLevel">100%</span>
                <button type="button" id="imageZoomIn" onclick="adjustImageZoom(1)" aria-label="Perbesar gambar"><i class="bi bi-plus-lg"></i></button>
                <button type="button" class="image-viewer__fit" onclick="fitImageViewer()" title="Sesuaikan dengan layar"><i class="bi bi-aspect-ratio"></i> Fit</button>
                <button type="button" class="image-viewer__close" onclick="closeImageViewer()" aria-label="Tutup gambar"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="image-viewer__viewport" id="imageViewerViewport">
            <div class="image-viewer__canvas" id="imageViewerCanvas">
                <img class="image-viewer__image" id="imageViewerImage" src="" alt="Gambar soal yang diperbesar" draggable="false">
            </div>
        </div>
        <div class="image-viewer__footer"><i class="bi bi-lightbulb me-1"></i> Tekan +/− untuk zoom, tahan dan geser untuk berpindah area, atau tekan Esc untuk menutup.</div>
    </div>

    <x-app-popup :flash="false" />

    <!-- Submit Form -->
    <form id="submitForm" method="POST" action="{{ route('exam.submit', $ujian) }}" style="display: none;">
        @csrf
    </form>

    <!-- Anti-Cheat Form (real form POST for reliable logout + logging) -->
    <form id="antiCheatForm" method="POST" action="{{ route('exam.anti-cheat', $ujian) }}" style="display: none;">
        @csrf
        <input type="hidden" id="violationTypeInput" name="violation_type" value="tab_switch">
        <input type="hidden" id="violationDetailInput" name="detail" value="Siswa berpindah tab atau membuka aplikasi/browser lain">
    </form>

    <script>
        // =============================================
        // SCRIPT VERSION: 3.0 - ANTI-CHEAT ENABLED
        // =============================================
        console.log('%c=== CBT EXAM SYSTEM v3.0 ===', 'color: #2563eb; font-weight: bold; font-size: 14px;');
        console.log('%cURL FIX: Using relative paths', 'color: #16a34a; font-weight: bold;');
        console.log('%cAnti-cheat: ENABLED', 'color: #dc2626; font-weight: bold;');
        console.log('%c=====================================', 'color: #2563eb; font-weight: bold;');
        
        // State
        let currentSoal = 0;
        const totalSoal = {{ $soals->count() }};
        let sisaWaktu = {{ $sisaWaktu }};
        let answers = @json($jawabans);
        let answerFiles = @json($jawabanFiles);
        let raguMap = @json(array_flip($raguRagu));

        // A soal counts as answered whether it has typed text or an uploaded image
        function hasAnswer(soalId) {
            const v = answers[soalId];
            return (v !== undefined && v !== null && v !== '') || !!answerFiles[soalId];
        }
        @php
            $soalListForJs = $soals->values()->map(function ($soal, $index) {
                return [
                    'index' => $index,
                    'id' => $soal->id,
                    'preview' => \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 60),
                ];
            });
        @endphp
        const soalList = @json($soalListForJs);
        const ujianId = {{ $ujian->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let saveTimeout;
        let cheatDetected = false;
        const antiCheatEnabled = @json($antiCheatEnabled);
        const maxTabSwitch = {{ max(1, $maxTabSwitch) }}; // from admin setting "Maksimal Pindah Tab"

        // Popup global juga menjadi guard anti-cheat agar dialog aplikasi
        // tidak pernah dianggap sebagai perpindahan tab/browser.

        // Dedicated image viewer. It scales the image from its natural pixel
        // dimensions (up to 400%) instead of scaling the page/viewport, so
        // image details are actually enlarged without triggering anti-cheat.
        const imageViewer = document.getElementById('imageViewer');
        const imageViewerViewport = document.getElementById('imageViewerViewport');
        const imageViewerCanvas = document.getElementById('imageViewerCanvas');
        const imageViewerImage = document.getElementById('imageViewerImage');
        const imageViewerTitle = document.getElementById('imageViewerTitle');
        const imageZoomLevel = document.getElementById('imageZoomLevel');
        const imageZoomOut = document.getElementById('imageZoomOut');
        const imageZoomIn = document.getElementById('imageZoomIn');
        const currentImageButton = document.getElementById('currentImageButton');
        const minImageZoom = 0.1;
        const maxImageZoom = 4;
        let imageZoom = 1;
        let imageViewerOpen = false;
        let imageViewerTrigger = null;
        let imageDragState = null;

        function renderImageZoom(centerImage = false) {
            if (!imageViewerImage.naturalWidth || !imageViewerImage.naturalHeight) return;

            const oldWidth = imageViewerImage.offsetWidth || 1;
            const oldHeight = imageViewerImage.offsetHeight || 1;
            const oldLeft = imageViewerImage.offsetLeft || 0;
            const oldTop = imageViewerImage.offsetTop || 0;
            const relativeX = (imageViewerViewport.scrollLeft + imageViewerViewport.clientWidth / 2 - oldLeft) / oldWidth;
            const relativeY = (imageViewerViewport.scrollTop + imageViewerViewport.clientHeight / 2 - oldTop) / oldHeight;

            const width = Math.max(1, Math.round(imageViewerImage.naturalWidth * imageZoom));
            const height = Math.max(1, Math.round(imageViewerImage.naturalHeight * imageZoom));
            const canvasWidth = Math.max(imageViewerViewport.clientWidth, width + 48);
            const canvasHeight = Math.max(imageViewerViewport.clientHeight, height + 48);
            const left = Math.max(24, (canvasWidth - width) / 2);
            const top = Math.max(24, (canvasHeight - height) / 2);

            imageViewerCanvas.style.width = canvasWidth + 'px';
            imageViewerCanvas.style.height = canvasHeight + 'px';
            imageViewerImage.style.width = width + 'px';
            imageViewerImage.style.left = left + 'px';
            imageViewerImage.style.top = top + 'px';
            imageZoomLevel.textContent = Math.round(imageZoom * 100) + '%';
            imageZoomOut.disabled = imageZoom <= minImageZoom + 0.001;
            imageZoomIn.disabled = imageZoom >= maxImageZoom - 0.001;

            requestAnimationFrame(() => {
                if (centerImage) {
                    imageViewerViewport.scrollLeft = Math.max(0, (canvasWidth - imageViewerViewport.clientWidth) / 2);
                    imageViewerViewport.scrollTop = Math.max(0, (canvasHeight - imageViewerViewport.clientHeight) / 2);
                } else {
                    imageViewerViewport.scrollLeft = Math.max(0, left + relativeX * width - imageViewerViewport.clientWidth / 2);
                    imageViewerViewport.scrollTop = Math.max(0, top + relativeY * height - imageViewerViewport.clientHeight / 2);
                }
            });
        }

        function fitImageViewer() {
            if (!imageViewerImage.naturalWidth || !imageViewerImage.naturalHeight) return;
            const availableWidth = Math.max(100, imageViewerViewport.clientWidth - 48);
            const availableHeight = Math.max(100, imageViewerViewport.clientHeight - 48);
            imageZoom = Math.min(1, availableWidth / imageViewerImage.naturalWidth, availableHeight / imageViewerImage.naturalHeight);
            imageZoom = Math.max(minImageZoom, imageZoom);
            renderImageZoom(true);
        }

        function adjustImageZoom(direction) {
            if (!imageViewerOpen) return;
            const factor = direction > 0 ? 1.25 : 0.8;
            imageZoom = Math.min(maxImageZoom, Math.max(minImageZoom, imageZoom * factor));
            renderImageZoom(false);
        }

        function openImageViewer(sourceImage, title = 'Gambar Soal') {
            if (!sourceImage || !sourceImage.src) return;

            imageViewerTrigger = document.activeElement;
            imageViewerTitle.textContent = title;
            imageViewerImage.alt = sourceImage.alt || title;
            imageViewerImage.src = sourceImage.currentSrc || sourceImage.src;
            imageViewer.classList.add('is-visible');
            imageViewer.setAttribute('aria-hidden', 'false');
            imageViewerOpen = true;

            const initialize = () => {
                fitImageViewer();
                document.querySelector('.image-viewer__close')?.focus({ preventScroll: true });
            };
            if (imageViewerImage.complete && imageViewerImage.naturalWidth) {
                initialize();
            } else {
                imageViewerImage.addEventListener('load', initialize, { once: true });
            }
        }

        function closeImageViewer() {
            if (!imageViewerOpen) return;
            imageViewer.classList.remove('is-visible');
            imageViewer.setAttribute('aria-hidden', 'true');
            imageViewerOpen = false;
            imageDragState = null;
            imageViewerViewport.classList.remove('is-dragging');
            imageViewerTrigger?.focus?.({ preventScroll: true });
        }

        function openCurrentQuestionImage() {
            const image = document.querySelector(`#soal-${currentSoal} [data-exam-image] img`);
            const frame = image?.closest('[data-exam-image]');
            if (image) openImageViewer(image, frame?.dataset.viewerTitle || `Gambar Soal Nomor ${currentSoal + 1}`);
        }

        function updateCurrentImageButton() {
            const images = document.querySelectorAll(`#soal-${currentSoal} [data-exam-image]`);
            currentImageButton.classList.toggle('is-visible', images.length > 0);
            currentImageButton.title = images.length > 1
                ? `Ada ${images.length} gambar pada soal ini. Klik untuk membuka gambar pertama.`
                : 'Perbesar gambar pada soal ini';
        }

        imageViewerViewport.addEventListener('pointerdown', event => {
            if (event.pointerType !== 'mouse' || event.button !== 0) return;
            imageDragState = {
                x: event.clientX,
                y: event.clientY,
                left: imageViewerViewport.scrollLeft,
                top: imageViewerViewport.scrollTop,
            };
            imageViewerViewport.classList.add('is-dragging');
            imageViewerViewport.setPointerCapture(event.pointerId);
        });
        imageViewerViewport.addEventListener('pointermove', event => {
            if (!imageDragState) return;
            imageViewerViewport.scrollLeft = imageDragState.left - (event.clientX - imageDragState.x);
            imageViewerViewport.scrollTop = imageDragState.top - (event.clientY - imageDragState.y);
        });
        imageViewerViewport.addEventListener('pointerup', event => {
            imageDragState = null;
            imageViewerViewport.classList.remove('is-dragging');
            if (imageViewerViewport.hasPointerCapture(event.pointerId)) imageViewerViewport.releasePointerCapture(event.pointerId);
        });
        imageViewerViewport.addEventListener('wheel', event => {
            if (!event.ctrlKey) return;
            event.preventDefault();
            adjustImageZoom(event.deltaY < 0 ? 1 : -1);
        }, { passive: false });
        document.addEventListener('keydown', event => {
            if (!imageViewerOpen) return;
            if (event.key === 'Escape') closeImageViewer();
            if (event.key === '+' || event.key === '=') adjustImageZoom(1);
            if (event.key === '-') adjustImageZoom(-1);
            if (event.key === '0') fitImageViewer();
        });
        window.addEventListener('resize', () => {
            if (imageViewerOpen) fitImageViewer();
        });

        updateCurrentImageButton();

        // Timer
        async function updateTimer() {
            if (sisaWaktu <= 0) {
                // Waktu habis - simpan semua jawaban dulu
                await saveAllAnswers();
                setTimeout(() => {
                    document.getElementById('submitForm').submit();
                }, 500);
                return;
            }

            const hours = Math.floor(sisaWaktu / 3600);
            const minutes = Math.floor((sisaWaktu % 3600) / 60);
            const seconds = sisaWaktu % 60;

            const display = `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
            document.getElementById('timerDisplay').textContent = display;

            const timer = document.getElementById('examTimer');
            if (sisaWaktu <= 300) {
                timer.classList.remove('warning');
                timer.style.animation = 'pulse 1s infinite';
            } else if (sisaWaktu <= 600) {
                timer.classList.add('warning');
            }

            sisaWaktu--;
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Navigation
        function goToSoal(index) {
            if (index < 0 || index >= totalSoal) return;

            document.querySelectorAll('.question-card').forEach(el => el.style.display = 'none');
            document.getElementById('soal-' + index).style.display = 'block';

            document.querySelectorAll('.soal-nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById('nav-' + index).classList.add('active');
            // Desktop nav
            const desktopNav = document.getElementById('nav-desktop-' + index);
            if (desktopNav) desktopNav.classList.add('active');

            currentSoal = index;
            updateProgress();
            updateCurrentImageButton();

            document.getElementById('questionsArea').scrollTop = 0;

            // Auto-minimize mobile bottom sheet after selecting a question
            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }
        }

        // Select Option
        function selectOption(index, soalId, label, el) {
            console.log(`[SELECT] Soal ${soalId}: ${label}`);
            
            const parent = el.closest('.question-card');
            parent.querySelectorAll('.option-pill').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');

            answers[soalId] = label;
            
            // Save immediately
            saveAnswer(soalId, label)
                .then(() => {
                    console.log(`[SELECT SUCCESS] Soal ${soalId} saved`);
                })
                .catch(err => {
                    console.error(`[SELECT ERROR] Soal ${soalId}:`, err);
                    showAlert(`Jawaban soal nomor ${index + 1} gagal disimpan!\n\nSilakan pilih jawaban lagi atau hubungi pengawas.`, { title: 'Gagal Disimpan', type: 'danger' });
                });
            
            updateNavState(index, soalId);
            updateProgress();
        }

        // Save Essay
        function saveEssay(index, soalId, value) {
            answers[soalId] = value;
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveAnswer(soalId, value);
                updateNavState(index, soalId);
                updateProgress();
            }, 1000);
        }

        // Upload an image as the answer to an essay question
        function uploadEssayImage(index, soalId, input) {
            const file = input.files && input.files[0];
            if (!file) return;

            const statusEl = document.getElementById('essayImageStatus-' + index);
            statusEl.className = 'essay-image-status uploading';
            statusEl.textContent = 'Mengunggah gambar...';

            const formData = new FormData();
            formData.append('bank_soal_id', soalId);
            formData.append('jawaban_file', file);

            const url = `/exam/${ujianId}/save-jawaban-file`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.error || `HTTP ${response.status}`);
                return data;
            })
            .then(data => {
                console.log(`[UPLOAD] ✓ Gambar jawaban soal ${soalId} tersimpan`);
                statusEl.className = 'essay-image-status success';
                statusEl.textContent = 'Gambar tersimpan.';

                document.getElementById('essayImagePreview-' + index).src = data.data.url;
                document.getElementById('essayImagePreviewWrap-' + index).classList.remove('d-none');
                document.getElementById('essayImageUploadBtn-' + index).classList.add('d-none');

                answerFiles[soalId] = data.data.jawaban_file;
                updateNavState(index, soalId);
                updateProgress();
            })
            .catch(err => {
                console.error('[UPLOAD] ✗ Error:', err);
                statusEl.className = 'essay-image-status error';
                statusEl.textContent = 'Gagal mengunggah gambar. Coba lagi.';
                showAlert(`Gagal mengunggah gambar jawaban soal nomor ${index + 1}.\n\n${err.message}`, { title: 'Gagal Mengunggah', type: 'danger' });
            })
            .finally(() => {
                input.value = '';
            });
        }

        // Remove the uploaded answer image (e.g. student wants to retake the photo)
        async function removeEssayImage(index, soalId) {
            if (!(await showConfirm('Hapus gambar jawaban ini?', { title: 'Hapus Gambar', type: 'danger', okText: 'Hapus' }))) return;

            const url = `/exam/${ujianId}/save-jawaban-file`;

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ bank_soal_id: soalId }),
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(() => {
                delete answerFiles[soalId];
                document.getElementById('essayImagePreviewWrap-' + index).classList.add('d-none');
                document.getElementById('essayImageUploadBtn-' + index).classList.remove('d-none');
                document.getElementById('essayImageStatus-' + index).textContent = '';
                updateNavState(index, soalId);
                updateProgress();
            })
            .catch(err => {
                console.error('[REMOVE IMAGE] ✗ Error:', err);
                showAlert('Gagal menghapus gambar. Silakan coba lagi.', { title: 'Gagal Menghapus', type: 'danger' });
            });
        }

        // Toggle Ragu
        function toggleRagu(index, soalId, checked) {
            if (checked) {
                raguMap[soalId] = true;
            } else {
                delete raguMap[soalId];
            }
            saveAnswer(soalId, answers[soalId] || null, checked);
            updateNavState(index, soalId);
        }

        // Save via AJAX
        function saveAnswer(soalId, jawaban, isRagu = null) {
            const data = {
                bank_soal_id: soalId,
                jawaban: jawaban,
            };
            if (isRagu !== null) data.is_ragu = isRagu ? 1 : 0;

            console.log('[SAVE] Preparing to save:', data);

            // Use relative URL to avoid APP_URL mismatch
            const url = `/exam/${ujianId}/save-jawaban`;
            console.log('[SAVE] URL:', url);
            console.log('[SAVE] CSRF Token:', csrfToken ? 'Present' : 'MISSING!');
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => {
                console.log('[SAVE] Response status:', response.status, response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[SAVE] ✓ Success:', soalId, data);
                
                // Verify data was saved
                if (data.success && data.data) {
                    console.log('[SAVE] ✓ Verified - jawaban_dipilih:', data.data.jawaban_dipilih);
                } else {
                    console.warn('[SAVE] ⚠ Success but no data returned');
                }
                
                return data;
            })
            .catch(err => {
                console.error('[SAVE] ✗ Error for soal', soalId, ':', err);
                console.error('[SAVE] ✗ Error details:', {
                    message: err.message,
                    stack: err.stack,
                    data: data
                });
                
                // Retry once
                console.log('[SAVE] Retrying...');
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Retry failed: HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('[SAVE] ✓ Retry successful:', soalId);
                    return data;
                })
                .catch(err2 => {
                    console.error('[SAVE] ✗ Retry failed:', err2);
                    showAlert(`Gagal menyimpan jawaban!\n\nSoal ID: ${soalId}\nJawaban: ${jawaban}\n\nError: ${err2.message}\n\nSilakan screenshot ini dan hubungi pengawas!`, { title: 'Gagal Menyimpan', type: 'danger' });
                    throw err2;
                });
            });
        }

        // Update nav state
        function updateNavState(index, soalId) {
            // Mobile nav
            const nav = document.getElementById('nav-' + index);
            nav.classList.remove('answered', 'doubt');
            if (raguMap[soalId]) {
                nav.classList.add('doubt');
            } else if (hasAnswer(soalId)) {
                nav.classList.add('answered');
            }

            // Desktop nav
            const navDesktop = document.getElementById('nav-desktop-' + index);
            if (navDesktop) {
                navDesktop.classList.remove('answered', 'doubt');
                if (raguMap[soalId]) {
                    navDesktop.classList.add('doubt');
                } else if (hasAnswer(soalId)) {
                    navDesktop.classList.add('answered');
                }
            }
        }

        // Update progress
        function updateProgress() {
            const answered = soalList.filter(s => hasAnswer(s.id)).length;
            const countEl = document.getElementById('answeredCount');
            if (countEl) countEl.textContent = answered;
            const percent = Math.round((answered / totalSoal) * 100);
            document.getElementById('progressBar').style.width = percent + '%';
            const progressLabel = document.getElementById('progressLabel');
            if (progressLabel) progressLabel.textContent = percent + '%';

            // Mobile progress text
            const mobileProgress = document.getElementById('mobileProgress');
            if (mobileProgress) mobileProgress.textContent = `${answered}/${totalSoal} dijawab`;

            // Doubt (ragu-ragu) indicator
            const doubtCount = Object.keys(raguMap).length;

            const doubtBox = document.getElementById('doubtWarningBox');
            if (doubtBox) {
                doubtBox.style.display = doubtCount > 0 ? 'flex' : 'none';
                const doubtCountEl = document.getElementById('doubtCount');
                if (doubtCountEl) doubtCountEl.textContent = doubtCount;
            }

            const mobileDoubtBadge = document.getElementById('mobileDoubtBadge');
            if (mobileDoubtBadge) {
                mobileDoubtBadge.style.display = doubtCount > 0 ? 'inline-flex' : 'none';
                const mobileDoubtCountEl = document.getElementById('mobileDoubtCount');
                if (mobileDoubtCountEl) mobileDoubtCountEl.textContent = doubtCount;
            }
        }

        // ===== Review All Questions Modal =====
        let reviewFilter = 'all';

        function getSoalStatus(soalId) {
            if (raguMap[soalId]) return 'doubt';
            if (hasAnswer(soalId)) return 'answered';
            return 'unanswered';
        }

        function openReviewModal() {
            renderReviewModal();
            document.getElementById('reviewModalOverlay').classList.add('show');
        }

        function closeReviewModal() {
            document.getElementById('reviewModalOverlay').classList.remove('show');
        }

        function filterReview(filter, el) {
            reviewFilter = filter;
            document.querySelectorAll('.review-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            renderReviewModal();
        }

        function reviewGoToSoal(index) {
            closeReviewModal();
            goToSoal(index);
        }

        const statusLabel = {
            answered: 'Dijawab',
            doubt: 'Ragu-ragu',
            unanswered: 'Belum dijawab',
        };
        const statusIcon = {
            answered: 'bi-check-lg',
            doubt: 'bi-flag-fill',
            unanswered: 'bi-dash-lg',
        };

        function renderReviewModal() {
            let answeredTotal = 0, doubtTotal = 0, unansweredTotal = 0;

            soalList.forEach(s => {
                const status = getSoalStatus(s.id);
                if (status === 'answered') answeredTotal++;
                else if (status === 'doubt') doubtTotal++;
                else unansweredTotal++;
            });

            document.getElementById('reviewAnsweredCount').textContent = answeredTotal;
            document.getElementById('reviewDoubtCount').textContent = doubtTotal;
            document.getElementById('reviewUnansweredCount').textContent = unansweredTotal;

            const grid = document.getElementById('reviewModalGrid');
            grid.innerHTML = '';

            const filtered = soalList.filter(s => {
                if (reviewFilter === 'all') return true;
                return getSoalStatus(s.id) === reviewFilter;
            });

            if (filtered.length === 0) {
                grid.innerHTML = '<div class="review-empty">Tidak ada soal pada kategori ini.</div>';
                return;
            }

            filtered.forEach(s => {
                const status = getSoalStatus(s.id);
                const item = document.createElement('div');
                item.className = 'review-item ' + status;
                item.onclick = () => reviewGoToSoal(s.index);
                item.innerHTML = `
                    <div class="review-item-number">${s.index + 1}</div>
                    <div class="flex-grow-1">
                        <div class="review-item-status"><i class="bi ${statusIcon[status]}"></i> ${statusLabel[status]}</div>
                        <div style="font-size:12px;color:var(--text-muted);">${s.preview}</div>
                    </div>
                    <i class="bi bi-chevron-right" style="color:var(--text-muted);"></i>
                `;
                grid.appendChild(item);
            });
        }

        // Confirm Submit
        async function confirmSubmit() {
            const answered = soalList.filter(s => hasAnswer(s.id)).length;
            const unanswered = totalSoal - answered;
            const doubtCount = Object.keys(raguMap).length;

            let msg = `Anda telah menjawab ${answered} dari ${totalSoal} soal.`;
            if (unanswered > 0) {
                msg += `\n\n⚠️ Masih ada ${unanswered} soal yang belum dijawab!`;
            }
            if (doubtCount > 0) {
                msg += `\n\n🚩 Ada ${doubtCount} soal yang masih ditandai ragu-ragu. Silakan periksa kembali sebelum mengumpulkan.`;
            }
            msg += '\n\nYakin ingin mengumpulkan jawaban?';

            if (await showConfirm(msg, { title: 'Kumpulkan Jawaban', type: unanswered > 0 ? 'warning' : 'info', okText: 'Ya, Kumpulkan' })) {
                // Pastikan semua jawaban tersimpan sebelum submit
                await saveAllAnswers();
                // Tunggu sebentar untuk memastikan request selesai
                setTimeout(() => {
                    document.getElementById('submitForm').submit();
                }, 500);
            }
        }

        // Fungsi untuk menyimpan semua jawaban sebelum submit
        async function saveAllAnswers() {
            console.log('=== SAVING ALL ANSWERS ===');
            console.log('Total answers in memory:', Object.keys(answers).length);
            console.log('Answers object:', answers);
            
            // Use relative URL to avoid APP_URL mismatch
            const url = `/exam/${ujianId}/save-jawaban`;
            const savePromises = [];
            let savedCount = 0;
            
            // Simpan semua jawaban yang ada
            for (const [soalId, jawaban] of Object.entries(answers)) {
                // Simpan semua jawaban, termasuk yang kosong untuk memastikan record ada
                const data = {
                    bank_soal_id: parseInt(soalId),
                    jawaban: jawaban || '', // Kirim string kosong jika null
                };
                
                // Cek apakah soal ini ditandai ragu-ragu
                if (raguMap[soalId]) {
                    data.is_ragu = 1;
                }
                
                console.log(`Saving soal ${soalId}:`, data);
                
                const promise = fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    savedCount++;
                    console.log(`✓ Saved ${savedCount}/${Object.keys(answers).length}:`, soalId);
                    return result;
                })
                .catch(err => {
                    console.error(`✗ Failed to save soal ${soalId}:`, err);
                    throw err;
                });
                
                savePromises.push(promise);
            }
            
            // Tunggu semua request selesai
            try {
                await Promise.all(savePromises);
                console.log(`✓✓✓ ALL ${savedCount} ANSWERS SAVED SUCCESSFULLY ✓✓✓`);
            } catch (err) {
                console.error('✗✗✗ ERROR SAVING ANSWERS:', err);
                showAlert('Beberapa jawaban gagal disimpan! Silakan coba submit lagi.', { title: 'Gagal Menyimpan', type: 'danger' });
                throw err;
            }
        }

        // Toggle mobile sidebar (bottom sheet minimize/expand)
        function toggleMobileSidebar() {
            const content = document.getElementById('sidebarContent');
            const isOpen = !content.classList.contains('show');
            setMobileSidebarOpen(isOpen);
        }

        function closeMobileSidebar() {
            setMobileSidebarOpen(false);
        }

        function setMobileSidebarOpen(open) {
            const content = document.getElementById('sidebarContent');
            const icon = document.getElementById('sidebarToggleIcon');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.querySelector('.mobile-sidebar-toggle');
            content.classList.toggle('show', open);
            icon.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
            if (backdrop) backdrop.classList.toggle('show', open);
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        // =============================================
        // ANTI-CHEAT SYSTEM - ENABLED
        // =============================================
        
        console.log(antiCheatEnabled
            ? '%c⚠️ ANTI-CHEAT SYSTEM ACTIVE' : '%cAnti-cheat system disabled by admin setting',
            'color: #dc2626; font-weight: bold; font-size: 12px;');

        if (antiCheatEnabled) {
            // Prevent right-click
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            // Prevent text selection
            document.addEventListener('selectstart', function(e) {
                e.preventDefault();
                return false;
            });

            // Prevent copy
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                return false;
            });

            // Prevent cut
            document.addEventListener('cut', function(e) {
                e.preventDefault();
                return false;
            });

            // Prevent keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
                if (e.keyCode === 123 || // F12
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                    (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                    e.preventDefault();
                    return false;
                }

                // Prevent Ctrl+C, Ctrl+X, Ctrl+A
                if (e.ctrlKey && (e.keyCode === 67 || e.keyCode === 88 || e.keyCode === 65)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Pindah tab/browser dihitung setelah grace period untuk menghindari
        // false positive dari keyboard mobile, autofill, atau dialog sistem singkat.
        const tabSwitchStorageKey = `exam:${ujianId}:attempt:{{ $peserta->waktu_mulai->timestamp }}:tab-switch-count`;
        function readTabSwitchCount() {
            try {
                const stored = Number.parseInt(sessionStorage.getItem(tabSwitchStorageKey) || '0', 10);
                return Number.isFinite(stored) && stored > 0 ? stored : 0;
            } catch (error) {
                return 0;
            }
        }
        function saveTabSwitchCount(value) {
            try { sessionStorage.setItem(tabSwitchStorageKey, String(value)); } catch (error) {}
        }
        let tabSwitchCount = readTabSwitchCount();

        const hiddenGraceMs = 1500;
        let hiddenTimer = null;
        let devToolsCheck = null;
        let violationAutoSubmitTimer = null;
        let violationSubmitting = false;

        function submitAntiCheatViolation(type, detail) {
            if (violationSubmitting) return;
            violationSubmitting = true;
            clearTimeout(hiddenTimer);
            clearTimeout(violationAutoSubmitTimer);
            clearInterval(devToolsCheck);

            document.getElementById('violationTypeInput').value = type;
            document.getElementById('violationDetailInput').value = detail;
            document.getElementById('antiCheatForm').submit();
        }

        function registerTabSwitchViolation(type = 'tab_switch', detail = 'Siswa berpindah tab atau membuka aplikasi/browser lain') {
            if (cheatDetected || violationSubmitting) return;

            tabSwitchCount++;
            saveTabSwitchCount(tabSwitchCount);
            console.warn(`⚠️ Anti-cheat detected! Count: ${tabSwitchCount}/${maxTabSwitch}`);

            if (tabSwitchCount >= maxTabSwitch) {
                cheatDetected = true;
                clearTimeout(hiddenTimer);
                clearInterval(devToolsCheck);

                const finalMessage = type === 'devtools'
                    ? 'Developer Tools terdeteksi saat ujian berlangsung.'
                    : 'Anda terdeteksi meninggalkan tab atau browser ujian.';

                showAlert(`${finalMessage}\n\nBatas pelanggaran (${maxTabSwitch} kali) telah tercapai. Jawaban akan dikumpulkan otomatis dan akun akan dikeluarkan.`, {
                    title: 'Ujian Dihentikan oleh Anti-Cheat',
                    type: 'danger',
                    okText: 'Kumpulkan & Keluar',
                }).then(() => submitAntiCheatViolation(type, detail));

                violationAutoSubmitTimer = setTimeout(
                    () => submitAntiCheatViolation(type, detail),
                    10000
                );
                return;
            }

            const remaining = maxTabSwitch - tabSwitchCount;
            const detectedMessage = type === 'devtools'
                ? 'Sistem mendeteksi Developer Tools terbuka.'
                : 'Sistem mendeteksi Anda meninggalkan tab atau browser ujian.';

            showAlert(`${detectedMessage}\n\nPelanggaran: ${tabSwitchCount} dari ${maxTabSwitch}.\nSisa toleransi: ${remaining} kali. Setelah batas tercapai, ujian akan dikumpulkan otomatis.`, {
                title: type === 'devtools' ? 'Developer Tools Terdeteksi' : 'Pindah Tab / Browser Terdeteksi',
                type: 'danger',
                okText: 'Saya Mengerti',
            });
        }

        if (antiCheatEnabled) {
            function isAwayFromExam() {
                return document.hidden || !document.hasFocus();
            }

            function handleAwayCheck() {
                if (cheatDetected || violationSubmitting) return;

                if (isAwayFromExam()) {
                    if (window.AppPopup?.isBlockingVisibility()) return;

                    clearTimeout(hiddenTimer);
                    hiddenTimer = setTimeout(function() {
                        if (isAwayFromExam() && !window.AppPopup?.isBlockingVisibility()) {
                            registerTabSwitchViolation();
                        }
                    }, hiddenGraceMs);
                } else {
                    clearTimeout(hiddenTimer);
                    hiddenTimer = null;
                }
            }

            document.addEventListener('visibilitychange', handleAwayCheck);
            window.addEventListener('blur', handleAwayCheck);
            window.addEventListener('focus', handleAwayCheck);
            document.addEventListener('app-popup:closed', () => setTimeout(handleAwayCheck, 650));

            let devToolsWarned = false;
            devToolsCheck = setInterval(function() {
                if (cheatDetected || violationSubmitting || window.AppPopup?.isOpen()) return;

                const threshold = 160;
                const isOpen = window.outerWidth - window.innerWidth > threshold ||
                    window.outerHeight - window.innerHeight > threshold;

                if (isOpen && !devToolsWarned) {
                    devToolsWarned = true;
                    registerTabSwitchViolation('devtools', 'Siswa membuka Developer Tools selama ujian');
                } else if (!isOpen) {
                    devToolsWarned = false;
                }
            }, 1000);
        }

        window.addEventListener('beforeunload', function() {
            clearTimeout(hiddenTimer);
            clearTimeout(violationAutoSubmitTimer);
            clearInterval(devToolsCheck);
        });

        // Init
        updateProgress();

        // Init nav states from existing data
        @foreach($soals as $index => $soal)
            updateNavState({{ $index }}, {{ $soal->id }});
        @endforeach

        // Show sidebar content desktop by default (CSS handles mobile hide)
        if (window.innerWidth > 768) {
            // Desktop: hide mobile-specific elements
            const mobileToggle = document.querySelector('.mobile-sidebar-toggle');
            if (mobileToggle) mobileToggle.style.display = 'none';
            const mobileContent = document.getElementById('sidebarContent');
            if (mobileContent) mobileContent.style.display = 'none';
        }
    </script>
</body>
</html>
