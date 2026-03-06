<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelanggaran Terdeteksi - {{ app_name() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .icon i {
            font-size: 48px;
            color: white;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .warning-box {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .warning-box h3 {
            font-size: 14px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-box ul {
            list-style: none;
            padding: 0;
        }
        
        .warning-box li {
            font-size: 14px;
            color: #991b1b;
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }
        
        .warning-box li:before {
            content: "•";
            position: absolute;
            left: 8px;
            font-weight: bold;
        }
        
        .info-box {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            width: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .countdown {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 16px;
        }
        
        .countdown strong {
            color: #dc2626;
            font-weight: 700;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .icon {
                width: 80px;
                height: 80px;
            }
            
            .icon i {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="bi bi-shield-x"></i>
        </div>
        
        <h1>⚠️ Pelanggaran Terdeteksi!</h1>
        <p class="subtitle">
            Sistem telah mendeteksi aktivitas yang melanggar aturan ujian. 
            Ujian Anda telah dikumpulkan secara otomatis.
        </p>
        
        <div class="warning-box">
            <h3>
                <i class="bi bi-exclamation-triangle-fill"></i>
                Pelanggaran yang Terdeteksi:
            </h3>
            <ul>
                <li>Meninggalkan halaman ujian (pindah tab/aplikasi)</li>
                <li>Membuka aplikasi lain selama ujian berlangsung</li>
                <li>Mencoba mengakses sumber eksternal</li>
            </ul>
        </div>
        
        <div class="info-box">
            <strong>Apa yang terjadi?</strong><br>
            • Jawaban Anda telah disimpan dan dikumpulkan otomatis<br>
            • Akun Anda telah di-logout dari sistem<br>
            • Pelanggaran ini telah dicatat dan dilaporkan ke pengawas<br>
            • Nilai akan diproses sesuai jawaban yang tersimpan
        </div>
        
        <a href="{{ route('login') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right"></i>
            Kembali ke Halaman Login
        </a>
        
        <div class="countdown">
            Anda akan dialihkan otomatis dalam <strong id="countdown">10</strong> detik
        </div>
    </div>
    
    <script>
        // Countdown redirect
        let seconds = 10;
        const countdownEl = document.getElementById('countdown');
        
        const interval = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
    </script>
</body>
</html>
