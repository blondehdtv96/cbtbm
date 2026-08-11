<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - {{ app_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background particles */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 20s infinite ease-in-out;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            animation: float 15s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }
        
        .container {
            max-width: 650px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .result-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 56px 48px;
            text-align: center;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.3);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        
        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #ec4899, #4f46e5);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .icon-wrapper {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 36px;
            box-shadow: 0 20px 50px rgba(79, 70, 229, 0.4);
            animation: bounce 1s ease-out, pulse 2s ease-in-out 1s infinite;
            position: relative;
        }
        
        .icon-wrapper::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            opacity: 0.3;
            filter: blur(20px);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            25% { transform: translateY(-20px); }
            50% { transform: translateY(-10px); }
            75% { transform: translateY(-15px); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }
        
        .icon-wrapper i {
            font-size: 64px;
            color: white;
            position: relative;
            z-index: 1;
            animation: iconRotate 0.6s ease-out 0.3s both;
        }
        
        @keyframes iconRotate {
            from { transform: rotate(-180deg) scale(0); }
            to { transform: rotate(0) scale(1); }
        }
        
        h1 {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #1e293b, #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }
        
        .exam-title {
            font-size: 15px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 48px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .score-section {
            margin-bottom: 48px;
            position: relative;
        }
        
        .score-display {
            font-size: 96px;
            font-weight: 900;
            background: linear-gradient(135deg, #4f46e5, #7c3aed, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 16px;
            animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.4s both;
            text-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.3) rotate(-10deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }
        
        .score-label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .message {
            font-size: 17px;
            color: #475569;
            margin-bottom: 48px;
            line-height: 1.7;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            padding: 28px 20px;
            border-radius: 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), rgba(124, 58, 237, 0.05));
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-color: #4f46e5;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-icon {
            font-size: 28px;
            margin-bottom: 12px;
            display: block;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 900;
            color: #1e293b;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }
        
        .stat-card.blue .stat-icon { color: #3b82f6; }
        .stat-card.green .stat-icon { color: #10b981; }
        .stat-card.purple .stat-icon { color: #8b5cf6; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 36px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            border: none;
            width: 100%;
            margin-bottom: 14px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn i, .btn span {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(79, 70, 229, 0.5);
        }
        
        .btn-secondary {
            background: white;
            color: #4f46e5;
            border: 2px solid #4f46e5;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.1);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
        }
        
        .pembahasan-section {
            margin-top: 40px;
            display: none;
        }
        
        .pembahasan-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 20px;
            text-align: left;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 2px solid #f1f5f9;
            animation: slideUp 0.5s ease-out;
            transition: all 0.3s;
        }
        
        .pembahasan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }
        
        .pembahasan-card.correct {
            border-left: 5px solid #10b981;
        }
        
        .pembahasan-card.wrong {
            border-left: 5px solid #ef4444;
        }
        
        .question-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }
        
        .question-number {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .badge.correct {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .badge.wrong {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .question-text {
            font-size: 15px;
            color: #334155;
            margin-bottom: 14px;
            line-height: 1.7;
            font-weight: 500;
        }
        
        .answer-info {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 14px;
            line-height: 1.6;
        }
        
        .answer-info strong {
            color: #1e293b;
            font-weight: 700;
        }
        
        @media (max-width: 640px) {
            body {
                padding: 16px;
            }
            
            .result-card {
                padding: 40px 28px;
                border-radius: 32px;
            }
            
            h1 {
                font-size: 32px;
            }
            
            .score-display {
                font-size: 72px;
            }
            
            .icon-wrapper {
                width: 110px;
                height: 110px;
            }
            
            .icon-wrapper i {
                font-size: 52px;
            }
            
            .stats-grid {
                gap: 14px;
            }
            
            .stat-card {
                padding: 22px 16px;
            }
            
            .stat-icon {
                font-size: 24px;
            }
            
            .stat-value {
                font-size: 30px;
            }
            
            .stat-label {
                font-size: 9px;
            }
            
            .btn {
                padding: 16px 28px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <div class="icon-wrapper">
                @if($ujian->tampilkan_nilai)
                    @if($peserta->nilai >= 75)
                        <i class="bi bi-trophy-fill"></i>
                    @elseif($peserta->nilai >= 50)
                        <i class="bi bi-emoji-smile-fill"></i>
                    @else
                        <i class="bi bi-emoji-frown-fill"></i>
                    @endif
                @else
                    <i class="bi bi-check-circle-fill"></i>
                @endif
            </div>

            <h1>Ujian Selesai!</h1>
            <p class="exam-title">{{ $ujian->nama_ujian }}</p>

            @if($ujian->tampilkan_nilai)
                <div class="score-section">
                    <div class="score-display">{{ number_format($peserta->nilai, 0) }}</div>
                    <p class="score-label">Nilai Anda</p>
                </div>
                
                <p class="message">
                    @if($peserta->nilai >= 75)
                        🎉 Luar biasa! Anda mendapat nilai yang sangat baik!
                    @elseif($peserta->nilai >= 50)
                        👍 Cukup baik. Terus tingkatkan belajar Anda!
                    @else
                        💪 Jangan menyerah! Terus belajar lebih giat!
                    @endif
                </p>
            @else
                <p class="message">
                    Nilai Anda akan diumumkan oleh guru.
                </p>
            @endif

            <div class="stats-grid">
                <div class="stat-card blue">
                    <i class="bi bi-file-text-fill stat-icon"></i>
                    <div class="stat-value">{{ $ujian->jumlah_soal }}</div>
                    <div class="stat-label">Total Soal</div>
                </div>
                <div class="stat-card green">
                    <i class="bi bi-check-circle-fill stat-icon"></i>
                    <div class="stat-value">
                        @php
                            $jawabans_count = \App\Models\JawabanSiswa::where('peserta_ujian_id', $peserta->id)
                                ->whereNotNull('jawaban_dipilih')
                                ->where('jawaban_dipilih', '!=', '')
                                ->count();
                        @endphp
                        {{ $jawabans_count }}
                    </div>
                    <div class="stat-label">Dijawab</div>
                </div>
                <div class="stat-card purple">
                    <i class="bi bi-clock-fill stat-icon"></i>
                    <div class="stat-value">
                        {{ $peserta->waktu_mulai && $peserta->waktu_selesai ? $peserta->waktu_mulai->diffInMinutes($peserta->waktu_selesai) : '—' }}
                    </div>
                    <div class="stat-label">Menit</div>
                </div>
            </div>

            @if($ujian->tampilkan_pembahasan && isset($jawabans) && $jawabans->count() > 0)
                <button class="btn btn-secondary" onclick="togglePembahasan()">
                    <i class="bi bi-book-fill"></i>
                    <span>Lihat Pembahasan</span>
                </button>
            @endif

            <a href="{{ route('siswa.dashboard') }}" class="btn btn-primary">
                <i class="bi bi-house-door-fill"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>

        @if($ujian->tampilkan_pembahasan && isset($jawabans) && $jawabans->count() > 0)
        <div class="pembahasan-section" id="pembahasanSection">
            @foreach($jawabans as $i => $jwb)
            <div class="pembahasan-card {{ $jwb->is_correct ? 'correct' : 'wrong' }}">
                <div class="question-header">
                    <div class="question-number">{{ $i + 1 }}</div>
                    <span class="badge {{ $jwb->is_correct ? 'correct' : 'wrong' }}">
                        {{ $jwb->is_correct ? 'Benar' : 'Salah' }}
                    </span>
                </div>
                
                <p class="question-text">{{ $jwb->bankSoal->pertanyaan ?? '' }}</p>
                
                <div class="answer-info">
                    <strong>Jawaban Anda:</strong> {{ $jwb->jawaban_dipilih ?? '-' }}<br>
                    <strong>Jawaban Benar:</strong> {{ $jwb->bankSoal->opsiJawabans->where('is_correct', true)->first()->opsi_label ?? '-' }}
                </div>
                
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <script>
        function togglePembahasan() {
            const section = document.getElementById('pembahasanSection');
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                section.style.display = 'none';
            }
        }
    </script>
</body>
</html>
