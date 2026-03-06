@extends('layouts.app')

@section('title', 'Hasil Import Siswa')
@section('page-title', 'Hasil Import Siswa')
@section('page-subtitle', 'Import berhasil — simpan kredensial berikut')

@section('content')
<div class="fade-in">
    {{-- Success Banner --}}
    <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(16, 185, 129, 0.08)); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 16px; padding: 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #22c55e, #10b981); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="bi bi-check-circle-fill" style="color: white; font-size: 28px;"></i>
        </div>
        <div>
            <div style="font-weight: 800; font-size: 18px; color: #15803d;">Import Berhasil! 🎉</div>
            <div style="font-size: 14px; color: #166534; margin-top: 4px;">
                <strong>{{ $successCount }}</strong> akun siswa berhasil dibuat. Simpan atau download kredensial berikut sebelum meninggalkan halaman ini.
            </div>
        </div>
    </div>

    {{-- Warning --}}
    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 14px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
        <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b; font-size: 20px;"></i>
        <div style="font-size: 13px; color: #92400e; font-weight: 500;">
            <strong>Perhatian:</strong> Password hanya ditampilkan <strong>satu kali</strong>. Pastikan untuk <strong>download file Excel</strong> atau <strong>cetak</strong> data ini. Jika lupa, Anda bisa reset password per siswa dari halaman edit user.
        </div>
    </div>

    {{-- Action Buttons Top --}}
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.import-siswa.download-credentials') }}" class="btn btn-ios btn-ios-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Download Excel Kredensial
        </a>
        <button class="btn btn-ios btn-ios-primary" onclick="printCredentials()">
            <i class="bi bi-printer-fill"></i> Cetak Kredensial
        </button>
        <button class="btn btn-ios btn-ios-light" onclick="copyAllCredentials()">
            <i class="bi bi-clipboard"></i> Salin Semua
        </button>
    </div>

    {{-- Credentials Table --}}
    <div class="card-ios" id="credentialTable">
        <div class="card-header">
            <i class="bi bi-key-fill me-2"></i>Kredensial Login Siswa ({{ $successCount }} akun)
        </div>
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table-ios" style="min-width: 700px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Lengkap</th>
                        <th>NISN (Login)</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credentials as $idx => $cred)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td style="font-weight: 600;">{{ $cred['nama'] }}</td>
                        <td>
                            <span style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 4px 12px; border-radius: 8px; font-weight: 700; font-size: 13px; font-family: monospace; letter-spacing: 1px;">
                                {{ $cred['nisn'] }}
                            </span>
                        </td>
                        <td><code>{{ $cred['nis'] }}</code></td>
                        <td>
                            <span class="badge-ios primary">{{ $cred['kelas'] }}</span>
                        </td>
                        <td>
                            <span style="background: linear-gradient(135deg, #f59e0b, #ef4444); color: white; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 14px; font-family: monospace; letter-spacing: 2px;">
                                {{ $cred['password'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Actions --}}
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <a href="{{ route('admin.import-siswa.index') }}" class="btn btn-ios btn-ios-success">
            <i class="bi bi-plus-lg"></i> Import Siswa Lagi
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ios btn-ios-light">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar User
        </a>
    </div>
</div>

@push('scripts')
<script>
function printCredentials() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html><head><title>Kredensial Import Siswa</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; padding: 30px; font-size: 12px; }
            h2 { text-align: center; margin-bottom: 4px; }
            p.sub { text-align: center; color: #666; font-size: 12px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #2563eb; color: white; padding: 10px 8px; font-size: 11px; text-transform: uppercase; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
            .nisn { background: #2563eb; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-family: monospace; letter-spacing: 1px; }
            .password { background: #f59e0b; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-family: monospace; letter-spacing: 2px; }
            .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; }
            .warning { background: #fef3cd; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; font-size: 11px; color: #856404; text-align: center; margin-top: 16px; }
        </style></head>
        <body>
            <h2>🎓 {{ app_name() }}</h2>
            <p class="sub">Kredensial Login Siswa — Hasil Import ${new Date().toLocaleDateString('id-ID')}</p>
            <table>
                <thead>
                    <tr><th>No</th><th>Nama</th><th>NISN (Login)</th><th>NIS</th><th>Kelas</th><th>Password</th></tr>
                </thead>
                <tbody>
                    @foreach($credentials as $idx => $cred)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $cred['nama'] }}</strong></td>
                        <td><span class="nisn">{{ $cred['nisn'] }}</span></td>
                        <td>{{ $cred['nis'] }}</td>
                        <td>{{ $cred['kelas'] }}</td>
                        <td><span class="password">{{ $cred['password'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="warning">⚠️ RAHASIA — Jangan bagikan password ke orang yang tidak berhak. Simpan dengan aman!</div>
            <p class="footer">Dicetak pada: ${new Date().toLocaleString('id-ID')} • © {{ app_name() }} - {{ setting('app_tagline', 'Sistem Ujian Online') }}</p>
        </body></html>
    `);
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 300);
}

function copyAllCredentials() {
    let text = 'KREDENSIAL LOGIN SISWA - HASIL IMPORT\n';
    text += '=' .repeat(50) + '\n\n';

    @foreach($credentials as $idx => $cred)
    text += '{{ $idx + 1 }}. {{ $cred['nama'] }}\n';
    text += '   NISN (Login): {{ $cred['nisn'] }}\n';
    text += '   NIS: {{ $cred['nis'] }}\n';
    text += '   Kelas: {{ $cred['kelas'] }}\n';
    text += '   Password: {{ $cred['password'] }}\n\n';
    @endforeach

    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Semua kredensial berhasil disalin ke clipboard!');
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✅ Semua kredensial berhasil disalin!');
    });
}
</script>
@endpush
@endsection
