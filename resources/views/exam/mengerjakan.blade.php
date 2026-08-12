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

        /* Watermark */
        .watermark-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            pointer-events: none;
            overflow: hidden;
        }
        .watermark-overlay .watermark-text {
            position: absolute;
            font-size: 16px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.04);
            white-space: nowrap;
            transform: rotate(-35deg);
            letter-spacing: 2px;
            font-family: 'Poppins', sans-serif;
        }

        /* Anti-cheat blur overlay */
        .cheat-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(220, 38, 38, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            text-align: center;
            padding: 40px;
        }
        .cheat-overlay.show {
            display: flex;
        }
        .cheat-overlay .cheat-icon {
            font-size: 80px;
            margin-bottom: 24px;
            animation: shakeIcon 0.5s ease-in-out;
        }
        .cheat-overlay h2 {
            font-weight: 800;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .cheat-overlay p {
            font-size: 15px;
            opacity: 0.9;
            max-width: 400px;
        }
        @keyframes shakeIcon {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
        }

        /* Mobile sidebar toggle */
        .mobile-sidebar-toggle {
            display: none;
        }
        @media (max-width: 768px) {
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
            .exam-sidebar {
                padding: 10px 12px calc(10px + env(safe-area-inset-bottom)) !important;
                border-radius: 16px 16px 0 0 !important;
            }
            .mobile-sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0;
                cursor: pointer;
                background: none;
                border: none;
                width: 100%;
                color: var(--text-primary);
            }
            .sidebar-content-mobile {
                display: none;
                padding-top: 10px;
            }
            .sidebar-content-mobile.show {
                display: block;
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
    </style>
</head>
<body class="exam-fullscreen">
    <!-- Watermark Overlay -->
    <div class="watermark-overlay" id="watermarkOverlay"></div>

    <!-- Cheat Detection Overlay -->
    <div class="cheat-overlay" id="cheatOverlay">
        <div class="cheat-icon"><i class="bi bi-shield-x"></i></div>
        <h2>⚠️ Kecurangan Terdeteksi!</h2>
        <p>Anda terdeteksi meninggalkan halaman ujian. Ujian akan di-submit otomatis dan akun Anda akan di-logout.</p>
        <button onclick="document.getElementById('antiCheatForm').submit();" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 24px; padding: 14px 32px; background: white; color: #dc2626; font-weight: 700; font-size: 15px; border-radius: 14px; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;">
            <i class="bi bi-box-arrow-right"></i> Ke Halaman Login
        </button>
        <p style="font-size: 12px; opacity: 0.6; margin-top: 16px;">Mengalihkan otomatis...</p>
    </div>

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

            <div class="exam-timer" id="examTimer">
                <i class="bi bi-clock-fill"></i>
                <span id="timerDisplay">--:--:--</span>
            </div>
        </div>
    </div>

    <!-- Exam Body -->
    <div class="exam-body" style="height: calc(100vh - 70px); height: calc(100dvh - 70px); overflow-y: auto;">
        <!-- Questions Area -->
        <div class="exam-questions" id="questionsArea" style="overflow-y: auto; padding-bottom: 40px;">
            <!-- Progress Bar -->
            <div class="progress-ios mb-4">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>

            @foreach($soals as $index => $soal)
            <div class="question-card" id="soal-{{ $index }}" style="{{ $index > 0 ? 'display:none;' : '' }}">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        <div class="question-number">{{ $index + 1 }}</div>
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
                    <img src="{{ asset('storage/' . $soal->gambar_soal) }}" alt="Gambar" style="max-width: 100%; border-radius: 12px; margin-bottom: 16px;">
                @endif

                @if($soal->tipe_soal === 'pg' || $soal->tipe_soal === 'pg_kompleks')
                    @foreach($soal->opsiJawabans as $opsi)
                    <div class="option-pill {{ ($jawabans[$soal->id] ?? '') === $opsi->opsi_label ? 'selected' : '' }}"
                         onclick="selectOption({{ $index }}, {{ $soal->id }}, '{{ $opsi->opsi_label }}', this)"
                         id="option-{{ $index }}-{{ $opsi->opsi_label }}">
                        <div class="option-label">{{ $opsi->opsi_label }}</div>
                        <div class="flex-grow-1">{{ $opsi->isi_opsi }}</div>
                    </div>
                    @endforeach
                @elseif($soal->tipe_soal === 'essay')
                    <textarea class="form-control-ios w-100" rows="6"
                              placeholder="Tulis jawaban Anda di sini..."
                              oninput="saveEssay({{ $index }}, {{ $soal->id }}, this.value)"
                              >{{ $jawabans[$soal->id] ?? '' }}</textarea>
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

        <!-- Sidebar Navigation -->
        <div class="exam-sidebar">
            <div style="position: sticky; top: 0;">
                <!-- Mobile Toggle Button -->
                <button class="mobile-sidebar-toggle" onclick="toggleMobileSidebar()">
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-weight: 700; font-size: 13px;" id="mobileProgress">0/{{ $soals->count() }} dijawab</span>
                        <span id="mobileDoubtBadge" style="display:none; font-weight: 700; font-size: 11px; color: #b45309; background: rgba(245,158,11,0.15); padding: 3px 8px; border-radius: 10px;">
                            <i class="bi bi-flag-fill"></i> <span id="mobileDoubtCount">0</span> ragu
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-ios btn-ios-light btn-ios-sm" onclick="event.stopPropagation(); openReviewModal();" style="padding: 6px 12px !important;">
                            <i class="bi bi-grid-3x3-gap-fill"></i> Lihat Semua
                        </button>
                        <button class="btn btn-ios btn-ios-success btn-ios-sm" onclick="event.stopPropagation(); confirmSubmit();" style="padding: 6px 12px !important;">
                            <i class="bi bi-send-fill"></i> Kumpulkan
                        </button>
                        <i class="bi bi-chevron-down" id="sidebarToggleIcon" style="transition: transform 0.3s;"></i>
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
                        <div style="background: var(--bg-secondary); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color);">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">PROGRESS</div>
                            <div style="font-size: 24px; font-weight: 800;" id="answeredCount">0</div>
                            <div style="font-size: 12px; color: var(--text-muted);">dari {{ $soals->count() }} soal dijawab</div>
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

    <!-- Submit Form -->
    <form id="submitForm" method="POST" action="{{ route('exam.submit', $ujian) }}" style="display: none;">
        @csrf
    </form>

    <!-- Anti-Cheat Form (real form POST for reliable logout + logging) -->
    <form id="antiCheatForm" method="POST" action="{{ route('exam.anti-cheat', $ujian) }}" style="display: none;">
        @csrf
        <input type="hidden" name="violation_type" value="tab_switch">
        <input type="hidden" name="detail" value="Siswa berpindah tab atau membuka home screen">
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
        let raguMap = @json(array_flip($raguRagu));
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

        // Native alert()/confirm() dialogs can themselves flip
        // document.hidden on some mobile browsers (Android Chrome in
        // particular). appDialogOpen tells the anti-cheat visibility
        // handler "this hidden state is our own dialog, not a real
        // tab/app switch" so it doesn't count as a violation.
        let appDialogOpen = false;
        function showAlert(message) {
            appDialogOpen = true;
            window.alert(message);
            setTimeout(() => { appDialogOpen = false; }, 500);
        }
        function showConfirm(message) {
            appDialogOpen = true;
            const result = window.confirm(message);
            setTimeout(() => { appDialogOpen = false; }, 500);
            return result;
        }

        // Generate Watermark
        function generateWatermark() {
            const overlay = document.getElementById('watermarkOverlay');
            const name = "{{ auth()->user()->name }}";
            const containerW = window.innerWidth;
            const containerH = window.innerHeight;

            for (let y = -100; y < containerH + 200; y += 120) {
                for (let x = -200; x < containerW + 400; x += 280) {
                    const el = document.createElement('div');
                    el.className = 'watermark-text';
                    el.textContent = name;
                    el.style.left = x + 'px';
                    el.style.top = y + 'px';
                    overlay.appendChild(el);
                }
            }
        }
        generateWatermark();

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

            document.getElementById('questionsArea').scrollTop = 0;

            // Auto collapse mobile sidebar after selecting
            if (window.innerWidth <= 768) {
                const content = document.getElementById('sidebarContent');
                const icon = document.getElementById('sidebarToggleIcon');
                content.classList.remove('show');
                icon.style.transform = 'rotate(0deg)';
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
                    showAlert(`PERINGATAN: Jawaban soal nomor ${index + 1} gagal disimpan!\n\nSilakan pilih jawaban lagi atau hubungi pengawas.`);
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
                    showAlert(`GAGAL MENYIMPAN JAWABAN!\n\nSoal ID: ${soalId}\nJawaban: ${jawaban}\n\nError: ${err2.message}\n\nSilakan screenshot ini dan hubungi pengawas!`);
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
            } else if (answers[soalId]) {
                nav.classList.add('answered');
            }

            // Desktop nav
            const navDesktop = document.getElementById('nav-desktop-' + index);
            if (navDesktop) {
                navDesktop.classList.remove('answered', 'doubt');
                if (raguMap[soalId]) {
                    navDesktop.classList.add('doubt');
                } else if (answers[soalId]) {
                    navDesktop.classList.add('answered');
                }
            }
        }

        // Update progress
        function updateProgress() {
            const answered = Object.values(answers).filter(v => v !== null && v !== '').length;
            const countEl = document.getElementById('answeredCount');
            if (countEl) countEl.textContent = answered;
            document.getElementById('progressBar').style.width = ((answered / totalSoal) * 100) + '%';

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
            if (answers[soalId]) return 'answered';
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
            const answered = Object.values(answers).filter(v => v !== null && v !== '').length;
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

            if (showConfirm(msg)) {
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
                showAlert('PERINGATAN: Beberapa jawaban gagal disimpan! Silakan coba submit lagi.');
                throw err;
            }
        }

        // Toggle mobile sidebar
        function toggleMobileSidebar() {
            const content = document.getElementById('sidebarContent');
            const icon = document.getElementById('sidebarToggleIcon');
            content.classList.toggle('show');
            icon.style.transform = content.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        // =============================================
        // ANTI-CHEAT SYSTEM - ENABLED
        // =============================================
        
        console.log('%c⚠️ ANTI-CHEAT SYSTEM ACTIVE', 'color: #dc2626; font-weight: bold; font-size: 12px;');
        
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

        // Tab switch detection
        //
        // document.hidden can flip true→false for well under a second on
        // mobile browsers for reasons that have nothing to do with actually
        // leaving the exam: our own alert()/confirm() dialogs (see
        // appDialogOpen above), the on-screen keyboard, a notification
        // shade peek, autofill prompts, etc. Counting every blip as a
        // violation false-positives on students who never left the page —
        // e.g. it fired while a student was just opening the review modal
        // to check answers before submitting.
        //
        // A genuine tab/app switch keeps the page hidden for at least a
        // couple of seconds (human reaction time to switch back), so we
        // require the hidden state to persist past a grace period before
        // it counts. Short blips are ignored entirely.
        let tabSwitchCount = 0;
        const maxTabSwitch = 2; // Allow 2 warnings before auto-submit
        const hiddenGraceMs = 1500;
        let hiddenTimer = null;

        function registerTabSwitchViolation() {
            tabSwitchCount++;
            console.warn(`⚠️ Tab switch detected! Count: ${tabSwitchCount}/${maxTabSwitch}`);

            if (tabSwitchCount >= maxTabSwitch) {
                cheatDetected = true;
                document.getElementById('cheatOverlay').classList.add('show');

                // Auto submit after 10 seconds
                setTimeout(function() {
                    document.getElementById('antiCheatForm').submit();
                }, 10000);
            } else {
                showAlert(`⚠️ PERINGATAN ${tabSwitchCount}/${maxTabSwitch}\n\nAnda terdeteksi meninggalkan halaman ujian!\n\nJika terdeteksi ${maxTabSwitch} kali, ujian akan otomatis dikumpulkan.`);
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (cheatDetected) return;

            if (document.hidden) {
                if (appDialogOpen) return; // our own dialog, not a real switch

                clearTimeout(hiddenTimer);
                hiddenTimer = setTimeout(function() {
                    if (document.hidden && !appDialogOpen) {
                        registerTabSwitchViolation();
                    }
                }, hiddenGraceMs);
            } else {
                clearTimeout(hiddenTimer);
                hiddenTimer = null;
            }
        });

        // Detect DevTools (desktop only — outerWidth/innerWidth stay equal
        // on mobile browsers, so this heuristic is a no-op there). Reuses
        // the same tab-switch violation counter/flow instead of just
        // logging, and only counts once per open (devToolsWarned) so the
        // 1s poll doesn't spam multiple violations while it stays open.
        let devToolsWarned = false;
        const devToolsCheck = setInterval(function() {
            if (cheatDetected) return;

            const threshold = 160;
            const isOpen = window.outerWidth - window.innerWidth > threshold ||
                window.outerHeight - window.innerHeight > threshold;

            if (isOpen && !devToolsWarned) {
                devToolsWarned = true;
                console.warn('⚠️ DevTools detected');
                registerTabSwitchViolation();
            } else if (!isOpen) {
                devToolsWarned = false;
            }
        }, 1000);

        // Cleanup interval on page unload
        window.addEventListener('beforeunload', function() {
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
