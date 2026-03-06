<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ app_name() }}</title>
    <meta name="description" content="Login ke sistem {{ app_name() }} - {{ setting('app_tagline', 'Sistem Ujian Online') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ==========================================
           Login Page Layout
           ========================================== */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            background: #f8fafc;
        }

        /* Left Panel - Branding */
        .login-branding {
            flex: 1;
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 50%, #6366f1 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .login-branding::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            top: -150px;
            right: -150px;
        }

        .login-branding::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            bottom: -80px;
            left: -80px;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 420px;
        }

        .branding-icon {
            width: 88px;
            height: 88px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .branding-icon i {
            font-size: 40px;
            color: white;
        }

        .branding-title {
            font-size: 36px;
            font-weight: 900;
            color: white;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }

        .branding-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .branding-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .branding-feature {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .branding-feature i {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            flex-shrink: 0;
        }

        .branding-feature span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        /* Right Panel - Form */
        .login-form-section {
            width: 520px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px;
            background: white;
        }

        .login-form-inner {
            width: 100%;
            max-width: 380px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-header-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .form-header-icon i {
            font-size: 22px;
            color: white;
        }

        .form-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }

        .form-header p {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 400;
        }

        /* Error / Info Messages */
        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #fecaca;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 12px;
            color: #2563eb;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Tab Switcher */
        .login-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 28px;
            background: #f1f5f9;
            border-radius: 14px;
            padding: 4px;
        }

        .login-tab {
            flex: 1;
            text-align: center;
            padding: 11px 16px;
            cursor: pointer;
            border-radius: 11px;
            font-weight: 600;
            font-size: 13px;
            color: #94a3b8;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .login-tab.active {
            background: white;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .login-tab:hover:not(.active) {
            color: #64748b;
        }

        .login-tab i {
            font-size: 15px;
        }

        /* Form Panels */
        .login-form-panel {
            display: none;
        }

        .login-form-panel.active {
            display: block;
            animation: fadeSlide 0.25s ease;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
            letter-spacing: -0.01em;
        }

        .form-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input::placeholder {
            color: #cbd5e1;
            font-weight: 400;
        }

        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: white;
        }

        /* Password field with toggle */
        .input-password-wrap {
            position: relative;
        }

        .input-password-wrap .form-input {
            padding-right: 44px;
        }

        .btn-toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 16px;
            padding: 4px;
            transition: color 0.2s;
        }

        .btn-toggle-password:hover {
            color: #64748b;
        }

        /* Remember me */
        .form-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            cursor: pointer;
        }

        .form-remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 5px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .form-remember span {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #6366f1);
            color: white;
            border: none;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #cbd5e1;
        }

        /* ==========================================
           Responsive - Mobile
           ========================================== */
        @media (max-width: 991.98px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-branding {
                padding: 36px 28px 28px;
                min-height: auto;
            }

            .branding-icon {
                width: 64px;
                height: 64px;
                border-radius: 18px;
                margin-bottom: 18px;
            }

            .branding-icon i {
                font-size: 28px;
            }

            .branding-title {
                font-size: 26px;
                margin-bottom: 4px;
            }

            .branding-subtitle {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .branding-features {
                display: none;
            }

            .login-form-section {
                width: 100%;
                padding: 28px 24px 36px;
                border-radius: 24px 24px 0 0;
                margin-top: -20px;
                position: relative;
                z-index: 1;
            }
        }

        @media (max-width: 480px) {
            .login-form-section {
                padding: 24px 20px 32px;
            }

            .branding-title {
                font-size: 22px;
            }

            .branding-subtitle {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        {{-- Left Panel: Branding --}}
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-icon">
                    @if(school_logo())
                        <img src="{{ school_logo() }}" alt="Logo" style="width: 80px; height: 80px; object-fit: contain;">
                    @else
                        <i class="bi bi-mortarboard-fill"></i>
                    @endif
                </div>
                <h1 class="branding-title">{{ app_name() }}</h1>
                <p class="branding-subtitle">{{ setting('app_description', 'Sistem Ujian Online Modern untuk Sekolah Menengah Kejuruan') }}</p>

                <div class="branding-features">
                    <div class="branding-feature">
                        <i class="bi bi-shield-check"></i>
                        <span>Ujian aman dengan anti-cheat system</span>
                    </div>
                    <div class="branding-feature">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Hasil & analisis nilai secara real-time</span>
                    </div>
                    <div class="branding-feature">
                        <i class="bi bi-phone"></i>
                        <span>Akses dari perangkat manapun</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Form --}}
        <div class="login-form-section">
            <div class="login-form-inner">
                <div class="form-header">
                    <div class="form-header-icon d-none d-lg-none">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h2>Selamat Datang</h2>
                    <p>Masuk ke akun Anda untuk melanjutkan</p>
                </div>

                @if($errors->any())
                    <div class="alert-error">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        {{ session('info') }}
                    </div>
                @endif

                {{-- Tab Switcher --}}
                <div class="login-tabs">
                    <div class="login-tab active" onclick="switchTab('siswa')" id="tab-siswa">
                        <i class="bi bi-person-fill"></i> Siswa
                    </div>
                    <div class="login-tab" onclick="switchTab('staff')" id="tab-staff">
                        <i class="bi bi-shield-lock-fill"></i> Guru / Admin
                    </div>
                </div>

                {{-- Panel Siswa (NISN Login) --}}
                <div class="login-form-panel active" id="panel-siswa">
                    <form method="POST" action="{{ route('login.process') }}">
                        @csrf
                        <input type="hidden" name="login_type" value="nisn">
                        <div class="form-group">
                            <label class="form-label" for="nisn">NISN</label>
                            <input type="text" class="form-input" id="nisn" name="nisn"
                                   value="{{ old('nisn') }}"
                                   placeholder="Masukkan NISN Anda" inputmode="numeric"
                                   pattern="[0-9]*" maxlength="20" autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password_siswa">Password</label>
                            <div class="input-password-wrap">
                                <input type="password" class="form-input" id="password_siswa" name="password"
                                       placeholder="Masukkan password dari admin" autocomplete="current-password">
                                <button type="button" class="btn-toggle-password" onclick="togglePassword('password_siswa', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <label class="form-remember">
                            <input type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>
                        <button type="submit" class="btn-login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Masuk Sebagai Siswa
                        </button>
                    </form>
                </div>

                {{-- Panel Staff (Email Login) --}}
                <div class="login-form-panel" id="panel-staff">
                    <form method="POST" action="{{ route('login.process') }}">
                        @csrf
                        <input type="hidden" name="login_type" value="email">
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-input" id="email" name="email"
                                   value="{{ old('email') }}"
                                   placeholder="Masukkan email Anda" autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password_staff">Password</label>
                            <div class="input-password-wrap">
                                <input type="password" class="form-input" id="password_staff" name="password"
                                       placeholder="Masukkan password" autocomplete="current-password">
                                <button type="button" class="btn-toggle-password" onclick="togglePassword('password_staff', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <label class="form-remember">
                            <input type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>
                        <button type="submit" class="btn-login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Masuk
                        </button>
                    </form>
                </div>

                <div class="login-footer">
                    © {{ date('Y') }} {{ app_name() }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');

            document.querySelectorAll('.login-form-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + tab).classList.add('active');

            setTimeout(() => {
                const panel = document.getElementById('panel-' + tab);
                const firstInput = panel.querySelector('input[type="text"], input[type="email"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        @if(old('login_type') === 'email' || $errors->has('email'))
            switchTab('staff');
        @endif
    </script>
</body>
</html>
