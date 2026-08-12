<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judul }} - {{ $ujian->nama_ujian }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.4;
            margin: 0;
            padding: 24px;
        }
        .header {
            text-align: center;
            border: 2px solid #1e3a8a;
            padding: 10px 16px;
            margin-bottom: 18px;
        }
        .header h2 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-style: italic;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 19px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
        }
        .info-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 0;
            font-weight: bold;
        }
        .info-table td.label {
            width: 150px;
        }
        .info-table td.sep {
            width: 12px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
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
            margin-top: 40px;
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
            @page { margin: 1.5cm; size: landscape; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>{{ $judul }}</h2>
        <h1>{{ $namaSekolah }}</h1>
        <p>TAHUN AJARAN {{ $tahunAjaran }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">KELAS</td>
            <td class="sep">:</td>
            <td>{{ $kelasName }}</td>
        </tr>
        <tr>
            <td class="label">MATA PELAJARAN</td>
            <td class="sep">:</td>
            <td>{{ $ujian->mapel->nama_mapel ?? '-' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="18%">Nomor Ujian</th>
                <th width="37%">Nama Peserta</th>
                <th width="13%">Menjawab</th>
                <th width="13%">Benar</th>
                <th width="14%">Total Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($peserta as $i => $p)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ $p->siswa->nisn ?? $p->siswa->nis ?? '-' }}</td>
                <td>{{ $p->siswa->nama ?? '-' }}</td>
                <td class="center">{{ $p->menjawab_count }}</td>
                <td class="center">{{ $p->benar_count }}</td>
                <td class="center"><strong>{{ $p->nilai }}</strong></td>
            </tr>
            @endforeach

            @if($peserta->isEmpty())
            <tr>
                <td colspan="6" class="center">Tidak ada peserta yang menyelesaikan ujian ini.</td>
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
