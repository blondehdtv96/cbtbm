<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Peserta – {{ $kelas->nama_kelas }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', 'Georgia', serif;
            background: #e2e8f0;
            color: #000;
        }

        /* === CONTROLS (no print) === */
        .print-controls {
            background: #1e293b;
            color: white;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 9999;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        .ctrl-title { font-weight: 700; font-size: 15px; }
        .ctrl-sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }
        .ctrl-right { display: flex; gap: 10px; align-items: center; }
        .btn-print {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white; border: none; border-radius: 10px;
            padding: 10px 22px; font-weight: 700; font-size: 14px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
        }
        .btn-print:hover { opacity: 0.88; }
        .btn-back {
            background: rgba(255,255,255,0.1); color: white;
            border: 1.5px solid rgba(255,255,255,0.2); border-radius: 10px;
            padding: 10px 18px; font-weight: 600; font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
        }
        .btn-back:hover { background: rgba(255,255,255,0.18); color: white; }

        /* === PRINT AREA === */
        .print-area { padding: 20px; }

        /* === KARTU GRID (2 kolom) === */
        .kartu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* === KARTU FORMAT SCREENSHOT === */
        .kartu {
            border: 1.5px solid #000;
            background: white;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Header */
        .kartu-header {
            border-bottom: 1.5px solid #000;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .kartu-logo {
            width: 46px;
            height: 46px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kartu-logo i { font-size: 24px; color: #475569; }
        .kartu-header-text {
            flex: 1;
            text-align: center;
        }
        .kartu-title {
            font-weight: 700;
            font-size: 12px;
            color: #991b1b;
        }
        .kartu-school {
            font-weight: 700;
            font-size: 12px;
            color: #000;
        }
        .kartu-tahun {
            font-size: 11px;
            color: #000;
        }

        /* Body */
        .kartu-body {
            padding: 8px 12px 10px;
        }
        .kartu-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kartu-table td {
            padding: 2.5px 0;
            font-size: 12px;
            vertical-align: top;
        }
        .kartu-table .lbl {
            width: 100px;
            font-weight: 700;
        }
        .kartu-table .sep {
            width: 10px;
            text-align: center;
        }
        .kartu-table .val {
            font-weight: 400;
        }

        /* Footer */
        .kartu-footer {
            display: flex;
            padding: 6px 12px 10px;
            align-items: flex-end;
            gap: 10px;
        }
        .kartu-foto {
            width: 65px;
            height: 75px;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .kartu-foto i { font-size: 30px; color: #94a3b8; }
        .kartu-foto img { width: 100%; height: 100%; object-fit: cover; }
        .kartu-ttd {
            flex: 1;
            text-align: center;
            padding-bottom: 4px;
        }
        .kartu-ttd .ttd-school { font-weight: 700; font-size: 11px; }
        .kartu-ttd .ttd-label { font-size: 11px; margin-top: 24px; }
        .kartu-ttd .ttd-name { font-weight: 700; font-size: 11px; }

        /* === PRINT === */
        @media print {
            @page { size: A4; margin: 8mm; }
            body { background: white !important; }
            .print-controls { display: none !important; }
            .print-area { padding: 0; }
            .kartu-grid { max-width: 100%; gap: 4mm; }
            .kartu { border: 1.5px solid #000 !important; }
        }
    </style>
</head>
<body>

<!-- Controls -->
<div class="print-controls">
    <div>
        <div class="ctrl-title"><i class="bi bi-credit-card-2-front-fill" style="margin-right:8px;"></i>Kartu Peserta</div>
        <div class="ctrl-sub">{{ $kelas->nama_kelas }} @if($kelas->jurusan) &bull; {{ $kelas->jurusan->nama_jurusan }} @endif &bull; {{ $siswas->count() }} siswa</div>
    </div>
    <div class="ctrl-right">
        <a href="javascript:history.back()" class="btn-back"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button class="btn-print" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak / Simpan PDF</button>
    </div>
</div>

<!-- Print Area -->
<div class="print-area">
@if($siswas->isEmpty())
    <div style="text-align:center;padding:80px 20px;">
        <i class="bi bi-people" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 style="color:#64748b;margin-top:12px;">Tidak ada siswa di kelas ini.</h5>
    </div>
@else
    <div class="kartu-grid">
    @foreach($siswas as $i => $siswa)
        @php
            $jurusanKode = $siswa->kelas->jurusan->kode_jurusan ?? '';
            $namaKelas = $siswa->kelas->nama_kelas ?? '';
            $username = $siswa->user->nisn ?? $siswa->nisn ?? ($siswa->user->email ?? '-');
            
            // Get actual plain_password if available, otherwise fallback to nisn/nis
            $password = $siswa->user->plain_password ?? $siswa->nisn ?? $siswa->nis ?? '-';
            
            // Sesi-Ruang from request or settings
            $sesiRuangVal = $sesiRuang ?: '-';
        @endphp
        <div class="kartu">
            {{-- Header --}}
            <div class="kartu-header">
                <div class="kartu-logo">
                    @if($kartuSettings['logo'])
                        <img src="{{ asset('storage/'.$kartuSettings['logo']) }}" style="width:100%;height:100%;object-fit:contain;">
                    @else
                        <i class="bi bi-mortarboard-fill"></i>
                    @endif
                </div>
                <div class="kartu-header-text">
                    <div class="kartu-title">{{ $kartuSettings['judul'] }}</div>
                    <div class="kartu-school">{{ $kartuSettings['nama_sekolah'] }}</div>
                    <div class="kartu-tahun">Tahun Pelajaran {{ $kartuSettings['tahun_pelajaran'] }}</div>
                </div>
            </div>

            {{-- Body --}}
            <div class="kartu-body">
                <table class="kartu-table">
                    <tr>
                        <td class="lbl">Nama Peserta</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $siswa->nama }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Jurusan/Kelas</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $jurusanKode }} / {{ $namaKelas }}</td>
                    </tr>
                    @if($kartuSettings['show_sesi'] == '1')
                    <tr>
                        <td class="lbl">Sesi - Ruang</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $sesiRuangVal }}</td>
                    </tr>
                    @endif
                    @if($kartuSettings['show_username'] == '1')
                    <tr>
                        <td class="lbl">Username</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $username }}</td>
                    </tr>
                    @endif
                    @if($kartuSettings['show_password'] == '1')
                    <tr>
                        <td class="lbl">Password</td>
                        <td class="sep">:</td>
                        <td class="val">{{ $password }}</td>
                    </tr>
                    @endif
                </table>

                {{-- Footer area: Foto + TTD --}}
                @if($kartuSettings['show_foto'] == '1' || $kartuSettings['show_ttd'] == '1')
                <div class="kartu-footer" style="margin-top:8px;">
                    @if($kartuSettings['show_foto'] == '1')
                    <div class="kartu-foto">
                        @if($siswa->foto && file_exists(public_path('storage/'.$siswa->foto)))
                            <img src="{{ asset('storage/'.$siswa->foto) }}" alt="">
                        @else
                            <i class="bi bi-person-fill"></i>
                        @endif
                    </div>
                    @endif
                    @if($kartuSettings['show_ttd'] == '1')
                    <div class="kartu-ttd">
                        <div class="ttd-school">{{ $kartuSettings['nama_sekolah'] }}</div>
                        <div class="ttd-label">Ttd ,</div>
                        <div class="ttd-name">{{ $kartuSettings['nama_ttd'] ?: '___________________' }}</div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    @endforeach
    </div>
@endif
</div>

</body>
</html>
