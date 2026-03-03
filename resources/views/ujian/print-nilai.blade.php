<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nilai Ujian - {{ $ujian->nama_ujian }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 3px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .data-table td.center {
            text-align: center;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
        .footer-ttd {
            display: inline-block;
            text-align: center;
        }
        .footer-ttd p.name {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }
        @media print {
            body { padding: 0; }
            @page { margin: 1.5cm; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>HASIL UJIAN</h2>
        <p>Aplikasi Ujian Berbasis Komputer</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="150"><strong>Nama Ujian</strong></td>
            <td width="10">:</td>
            <td>{{ strtoupper($ujian->nama_ujian) }}</td>
            <td width="150"><strong>Mata Pelajaran</strong></td>
            <td width="10">:</td>
            <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td>
            <td>:</td>
            <td>{{ $kelasName }}</td>
            <td><strong>Tanggal Cetak</strong></td>
            <td>:</td>
            <td>{{ now()->format('d F Y H:i') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Siswa</th>
                <th width="15%">NIS/NISN</th>
                <th width="15%">Kelas</th>
                <th width="15%">Waktu Selesai</th>
                <th width="10%">Benar</th>
                <th width="10%">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($peserta as $i => $p)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $p->siswa->nama ?? '-' }}</td>
                <td class="center">{{ $p->siswa->nis ?? '-' }}</td>
                <td class="center">{{ $p->siswa->kelas->nama_kelas ?? '-' }}</td>
                <td class="center">{{ $p->waktu_selesai ? $p->waktu_selesai->format('d/m/Y H:i') : '-' }}</td>
                <td class="center">{{ $p->benar_count }}</td>
                <td class="center"><strong>{{ $p->nilai }}</strong></td>
            </tr>
            @endforeach
            
            @if($peserta->isEmpty())
            <tr>
                <td colspan="7" class="center">Tidak ada data siswa untuk kelas ini.</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-ttd">
            <p>Admin / Guru Mata Pelajaran</p>
            <p class="name">{{ auth()->user()->name }}</p>
        </div>
    </div>

</body>
</html>
