@extends('layouts.app')
@section('title', 'Hasil Ujian')
@section('page-title', 'Hasil: ' . $ujian->nama_ujian)

@section('content')
<div class="fade-in">
    <a href="{{ route('ujian.index') }}" class="btn btn-ios btn-ios-light mb-4"><i class="bi bi-arrow-left"></i> Kembali</a>

        <div class="col-sm-3">
            <div class="stat-card primary">
                <div class="stat-value">{{ $peserta->count() }}</div>
                <div class="stat-label">Total Peserta Selesai</div>
            </div>
        </div>
        <div class="col-sm-offset-1 col-sm-8 d-flex align-items-center justify-content-end gap-2">
            <!-- Filter Form -->
            <form action="{{ route('ujian.hasil', $ujian) }}" method="GET" class="d-flex gap-2">
                <select name="kelas_id" class="form-select-ios" onchange="this.form.submit()">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </form>
            
            <!-- Export Button -->
            <a href="{{ route('ujian.cetak-nilai', ['ujian' => $ujian->id, 'kelas_id' => request('kelas_id')]) }}" class="btn btn-ios btn-ios-success d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel-fill"></i> Excel
            </a>

            <!-- Print Button -->
            <a href="{{ route('ujian.print-nilai', ['ujian' => $ujian->id, 'kelas_id' => request('kelas_id')]) }}" target="_blank" class="btn btn-ios btn-ios-primary d-flex align-items-center gap-2">
                <i class="bi bi-printer-fill"></i> Print
            </a>

            <!-- Dropdown: Lembar Nilai Resmi (kop sekolah) -->
            <div class="dropdown">
                <button class="btn btn-ios btn-ios-light d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-ruled-fill"></i> Lembar Nilai Resmi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('ujian.nilai-resmi.excel', ['ujian' => $ujian->id, 'kelas_id' => request('kelas_id')]) }}">
                            <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('ujian.nilai-resmi.print', ['ujian' => $ujian->id, 'kelas_id' => request('kelas_id')]) }}" target="_blank">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF / Print
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>

    <div class="card-ios">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-trophy-fill me-2"></i>Ranking Peserta</span>
        </div>
        <div class="card-body p-0">
            <table class="table-ios">
                <thead><tr><th>Rank</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Nilai</th><th>Waktu</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($peserta as $i => $p)
                    <tr>
                        <td>
                            @if($i < 3)
                                <span style="font-size: 20px;">{{ ['🥇','🥈','🥉'][$i] }}</span>
                            @else
                                <span style="font-weight: 700;">{{ $i + 1 }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $p->siswa->nama ?? '-' }}</strong></td>
                        <td>{{ $p->siswa->nis ?? '-' }}</td>
                        <td>{{ $p->siswa->kelas->nama_kelas ?? '-' }}</td>
                        <td><span class="badge-ios {{ $p->nilai >= 75 ? 'success' : ($p->nilai >= 50 ? 'warning' : 'danger') }}" style="font-size: 14px; font-weight: 700;">{{ $p->nilai }}</span></td>
                        <td><small class="text-muted">{{ $p->waktu_mulai && $p->waktu_selesai ? $p->waktu_mulai->diffForHumans($p->waktu_selesai, true) : '-' }}</small></td>
                        <td>
                            <a href="{{ route('ujian.peserta.jawaban', ['ujian' => $ujian->id, 'peserta' => $p->id]) }}" class="btn btn-ios btn-ios-light btn-ios-sm" title="Lihat Jawaban">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
