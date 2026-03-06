<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $ujian->nama_ujian }} - {{ app_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
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
                padding: 10px 12px !important;
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
    <div class="exam-body" style="height: calc(100vh - 70px); overflow-y: auto;">
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
                        <div>
                            <span class="badge-ios {{ $soal->tingkat_kesulitan == 'mudah' ? 'success' : ($soal->tingkat_kesulitan == 'sedang' ? 'warning' : 'danger') }}">
                                {{ ucfirst($soal->tingkat_kesulitan) }}
                            </span>
                        </div>
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
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-ios btn-ios-success btn-ios-sm" onclick="event.stopPropagation(); confirmSubmit();" style="padding: 6px 12px !important;">
                            <i class="bi bi-send-fill"></i> Kumpulkan
                        </button>
                        <i class="bi bi-chevron-up" id="sidebarToggleIcon" style="transition: transform 0.3s;"></i>
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

                    <button class="btn btn-ios btn-ios-success w-100 mt-3" onclick="confirmSubmit()" style="padding: 14px;">
                        <i class="bi bi-send-fill"></i> Kumpulkan Jawaban
                    </button>
                </div>
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
        const ujianId = {{ $ujian->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let saveTimeout;
        let cheatDetected = false;

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
                    alert(`PERINGATAN: Jawaban soal nomor ${index + 1} gagal disimpan!\n\nSilakan pilih jawaban lagi atau hubungi pengawas.`);
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
                    alert(`GAGAL MENYIMPAN JAWABAN!\n\nSoal ID: ${soalId}\nJawaban: ${jawaban}\n\nError: ${err2.message}\n\nSilakan screenshot ini dan hubungi pengawas!`);
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
        }

        // Confirm Submit
        async function confirmSubmit() {
            const answered = Object.values(answers).filter(v => v !== null && v !== '').length;
            const unanswered = totalSoal - answered;

            let msg = `Anda telah menjawab ${answered} dari ${totalSoal} soal.`;
            if (unanswered > 0) {
                msg += `\n\n⚠️ Masih ada ${unanswered} soal yang belum dijawab!`;
            }
            msg += '\n\nYakin ingin mengumpulkan jawaban?';

            if (confirm(msg)) {
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
                alert('PERINGATAN: Beberapa jawaban gagal disimpan! Silakan coba submit lagi.');
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
        let tabSwitchCount = 0;
        const maxTabSwitch = 2; // Allow 2 warnings before auto-submit

        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !cheatDetected) {
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
                    alert(`⚠️ PERINGATAN ${tabSwitchCount}/${maxTabSwitch}\n\nAnda terdeteksi meninggalkan halaman ujian!\n\nJika terdeteksi ${maxTabSwitch} kali, ujian akan otomatis dikumpulkan.`);
                }
            }
        });

        // Detect window blur (switching to another app)
        window.addEventListener('blur', function() {
            if (!cheatDetected && document.visibilityState === 'visible') {
                console.warn('⚠️ Window blur detected (Alt+Tab or other app)');
                // Optional: You can add warning here too
            }
        });

        // Prevent opening DevTools (additional check)
        const devToolsCheck = setInterval(function() {
            const threshold = 160;
            if (window.outerWidth - window.innerWidth > threshold || 
                window.outerHeight - window.innerHeight > threshold) {
                console.warn('⚠️ DevTools might be open');
                // Optional: Add action here
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
