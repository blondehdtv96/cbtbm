<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Peserta - {{ $ujian->nama_ujian }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        /* ============ NO-PRINT CONTROLS ============ */
        .print-controls {
            background: #1e293b;
            color: white;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 9999;
            gap: 12px;
        }
        .print-controls .ctrl-title {
            font-weight: 700;
            font-size: 15px;
        }
        .print-controls .ctrl-subtitle {
            font-size: 12px;
            color: #94a3b8;
        }
        .btn-print {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-print:hover { opacity: 0.9; }
        .btn-back {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }

        /* ============ PRINT AREA ============ */
        .print-area {
            padding: 20px;
        }

        /* ============ KARTU GRID ============ */
        .kartu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* ============ SINGLE KARTU ============ */
        .kartu {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .kartu-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .kartu-logo-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        .kartu-header-text {
            flex: 1;
        }
        .kartu-school-name {
            font-size: 11px;
            font-weight: 800;
            color: white;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .kartu-title {
            font-size: 9px;
            color: rgba(255,255,255,0.75);
            letter-spacing: 0.3px;
            text-transform: uppercase;
            margin-top: 1px;
        }
        .kartu-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kartu-body {
            padding: 14px 16px;
        }
        .kartu-siswa-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #f1f5f9;
        }
        .kartu-avatar {
            width: 52px;
            height: 64px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0;
            border: 2px solid #e2e8f0;
        }
        .kartu-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        .kartu-siswa-info {
            flex: 1;
            min-width: 0;
        }
        .siswa-nama {
            font-weight: 800;
            font-size: 14px;
            color: #1e293b;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .siswa-kelas {
            font-size: 11px;
            color: #4f46e5;
            font-weight: 600;
            background: #eef2ff;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .kartu-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        .kartu-info-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 7px 10px;
        }
        .info-label {
            font-size: 9px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
        }
        .kartu-info-full {
            background: #f8fafc;
            border-radius: 8px;
            padding: 7px 10px;
            margin-bottom: 10px;
        }

        .kartu-token-box {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .token-label {
            font-size: 9px;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .token-value {
            font-size: 22px;
            font-weight: 900;
            color: white;
            letter-spacing: 4px;
            font-feature-settings: "tnum";
        }
        .token-icon {
            font-size: 24px;
            color: rgba(255,255,255,0.3);
        }

        .kartu-footer {
            border-top: 1px dashed #e2e8f0;
            margin: 10px 0 0 0;
            padding: 8px 0 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kartu-no {
            font-size: 10px;
            color: #94a3b8;
        }
        .kartu-tanda-tangan {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
        .ttd-line {
            width: 80px;
            height: 30px;
            border-bottom: 1px solid #cbd5e1;
            margin: 0 auto 4px auto;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        /* ============ PRINT STYLES ============ */
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }
            body {
                background: white !important;
            }
            .print-controls {
                display: none !important;
            }
            .print-area {
                padding: 0;
            }
            .kartu-grid {
                max-width: 100%;
                gap: 8mm;
            }
            .kartu {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<!-- Controls (not printed) -->
<div class="print-controls">
    <div>
        <div class="ctrl-title">
            <i class="bi bi-printer-fill me-2"></i>Kartu Peserta Ujian
        </div>
        <div class="ctrl-subtitle">{{ $ujian->nama_ujian }} &bull; {{ $pesertaList->count() }} kartu</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="javascript:history.back()" class="btn-back">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button class="btn-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Cetak / Simpan PDF
        </button>
    </div>
</div>

<!-- Print Area -->
<div class="print-area">
    @if($pesertaList->isEmpty())
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size:3rem;color:#94a3b8;"></i>
            <h5 class="mt-3" style="color:#64748b;">Tidak ada peserta untuk dicetak.</h5>
            <p style="color:#94a3b8;font-size:13px;">Kembali dan pilih peserta terlebih dahulu.</p>
        </div>
    @else
    <div class="kartu-grid">
        @foreach($pesertaList as $i => $peserta)
        @php $siswa = $peserta->siswa; @endphp
        <div class="kartu">
            <!-- Header -->
            <div class="kartu-header">
                <div class="kartu-logo-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="kartu-header-text">
                    <div class="kartu-school-name">{{ $namaSekolah }}</div>
                    <div class="kartu-title">Kartu Peserta Ujian</div>
                </div>
                <div class="kartu-badge">{{ strtoupper($ujian->jenis_ujian) }}</div>
            </div>

            <!-- Body -->
            <div class="kartu-body">
                <!-- Siswa Info -->
                <div class="kartu-siswa-row">
                    <div class="kartu-avatar">
                        @if($siswa->foto && file_exists(public_path('storage/'.$siswa->foto)))
                            <img src="{{ asset('storage/'.$siswa->foto) }}" alt="{{ $siswa->nama }}">
                        @else
                            {{ strtoupper(substr($siswa->nama, 0, 2)) }}
                        @endif
                    </div>
                    <div class="kartu-siswa-info">
                        <div class="siswa-nama">{{ $siswa->nama }}</div>
                        <div class="siswa-kelas">{{ $siswa->kelas->nama_kelas ?? '-' }}</div>
                    </div>
                </div>

                <!-- Detail Grid -->
                <div class="kartu-info-grid">
                    <div class="kartu-info-item">
                        <div class="info-label">NIS</div>
                        <div class="info-value">{{ $siswa->nis ?? '-' }}</div>
                    </div>
                    <div class="kartu-info-item">
                        <div class="info-label">NISN</div>
                        <div class="info-value">{{ $siswa->nisn ?? '-' }}</div>
                    </div>
                    <div class="kartu-info-item">
                        <div class="info-label">Tanggal</div>
                        <div class="info-value">{{ $peserta->ujian->tanggal_mulai->format('d/m/Y') }}</div>
                    </div>
                    <div class="kartu-info-item">
                        <div class="info-label">Waktu</div>
                        <div class="info-value">
                            {{ $peserta->ujian->tanggal_mulai->format('H:i') }} –
                            {{ $peserta->ujian->tanggal_selesai->format('H:i') }}
                        </div>
                    </div>
                    <div class="kartu-info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value">{{ $siswa->user->nisn ?? $siswa->nisn ?? ($siswa->user->email ?? '-') }}</div>
                    </div>
                    <div class="kartu-info-item">
                        <div class="info-label">Password</div>
                        <div class="info-value">{{ $siswa->user->plain_password ?? $siswa->nisn ?? $siswa->nis ?? '-' }}</div>
                    </div>
                </div>

                <div class="kartu-info-full">
                    <div class="info-label">Mata Pelajaran</div>
                    <div class="info-value">{{ $ujian->mapel->nama_mapel ?? '-' }}</div>
                </div>

                <!-- Token Box -->
                <div class="kartu-token-box">
                    <div>
                        <div class="token-label">Token Ujian</div>
                        <div class="token-value">{{ $ujian->token }}</div>
                    </div>
                    <i class="bi bi-key-fill token-icon"></i>
                </div>

                <!-- Footer -->
                <div class="kartu-footer">
                    <div class="kartu-no">No. {{ str_pad($i + 1, 3, '0', STR_PAD_LEFT) }}</div>
                    <div class="kartu-tanda-tangan">
                        <div class="ttd-line"></div>
                        <div>Panitia</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

</body>
</html>
