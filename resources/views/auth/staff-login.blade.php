<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Staff - {{ app_name() }}</title>
    <meta name="description" content="Login Guru & Admin ke sistem {{ app_name() }}">
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
            background: #ffffff;
        }

        /* Right Panel - Branding (form comes first on this page, on purpose) */
        .login-branding {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            overflow: hidden;
            order: 2;
            background: linear-gradient(135deg, #05122b 0%, #0b2e63 30%, #123a76 55%, #1d4ed8 85%, #2563eb 100%);
            background-size: 220% 220%;
            animation: gradientShift 18s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 40%; }
            50%  { background-position: 100% 60%; }
            100% { background-position: 0% 40%; }
        }

        .login-branding .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(4px);
            pointer-events: none;
        }

        .blob-1 {
            width: 460px;
            height: 460px;
            background: rgba(255, 255, 255, 0.05);
            top: -140px;
            left: -120px;
            animation: floatBlob1 15s ease-in-out infinite;
        }

        .blob-2 {
            width: 320px;
            height: 320px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -90px;
            right: -80px;
            animation: floatBlob2 19s ease-in-out infinite;
        }

        .blob-3 {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.045);
            top: 40%;
            right: 10%;
            animation: floatBlob3 12s ease-in-out infinite;
        }

        @keyframes floatBlob1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(32px, 28px) scale(1.1); }
        }

        @keyframes floatBlob2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(-28px, -24px) scale(1.08); }
        }

        @keyframes floatBlob3 {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(-16px, 20px); }
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
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            animation: iconFloat 5s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }

        .branding-icon i {
            font-size: 38px;
            color: white;
        }

        .branding-title {
            font-size: 32px;
            font-weight: 900;
            color: white;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }

        .branding-subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.75);
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
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .branding-feature i {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.95);
            flex-shrink: 0;
        }

        .branding-feature span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 500;
        }

        /* Left Panel - Form */
        .login-form-section {
            width: 520px;
            order: 1;
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

        .form-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            margin-bottom: 18px;
        }

        .form-badge i {
            font-size: 13px;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: #0b1e3d;
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }

        .form-header p {
            font-size: 14px;
            color: #64748b;
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
            color: #1d4ed8;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
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
            color: #0b1e3d;
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
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
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
            color: #2563eb;
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
            background: linear-gradient(135deg, #123a76, #1d4ed8 55%, #2563eb);
            background-size: 160% 160%;
            color: white;
            border: none;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background-position 0.4s ease;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(29, 78, 216, 0.32);
            background-position: 100% 0;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(29, 78, 216, 0.2);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #cbd5e1;
        }

        /* Respect reduced-motion preference */
        @media (prefers-reduced-motion: reduce) {
            .login-branding,
            .blob-1, .blob-2, .blob-3,
            .branding-icon,
            .btn-login {
                animation: none !important;
                transition: none !important;
            }
        }

        /* ==========================================
           Responsive - Mobile
           ========================================== */
        @media (max-width: 991.98px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-branding {
                order: 1;
                padding: 36px 28px 28px;
                min-height: auto;
            }

            .login-form-section {
                order: 2;
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
        {{-- Form Panel --}}
        <div class="login-form-section">
            <div class="login-form-inner">
                <div class="form-header">
                    <span class="form-badge"><i class="bi bi-shield-lock-fill"></i> Portal Staff</span>
                    <h2>Login Guru & Admin</h2>
                    <p>Masuk dengan email dan password akun Anda</p>
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

                <form method="POST" action="{{ route('staff.login.process') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-input" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="Masukkan email Anda" autocomplete="username" autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-password-wrap">
                            <input type="password" class="form-input" id="password" name="password"
                                   placeholder="Masukkan password" autocomplete="current-password">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword('password', this)">
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

                <div class="login-footer">
                    © {{ date('Y') }} {{ app_name() }}. All rights reserved.
                </div>
            </div>
        </div>

        {{-- Branding Panel --}}
        <div class="login-branding">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="branding-content">
                <div class="branding-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1 class="branding-title">{{ app_name() }}</h1>
                <p class="branding-subtitle">Panel internal untuk Guru dan Admin — kelola ujian, bank soal, dan data siswa.</p>

                <div class="branding-features">
                    <div class="branding-feature">
                        <i class="bi bi-journal-check"></i>
                        <span>Kelola bank soal & ujian</span>
                    </div>
                    <div class="branding-feature">
                        <i class="bi bi-activity"></i>
                        <span>Monitoring ujian real-time</span>
                    </div>
                    <div class="branding-feature">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Rekap & analisis nilai otomatis</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
