@extends('layouts.app')

@section('title', 'Kredensial Siswa')
@section('page-title', 'Kredensial Login Siswa')
@section('page-subtitle', 'Simpan atau cetak kredensial ini')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            {{-- Success Alert --}}
            <div style="background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(34, 197, 94, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="bi bi-check-circle-fill" style="color: #16a34a; font-size: 20px;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 14px; color: #15803d;">Akun Siswa Berhasil Dibuat!</div>
                    <div style="font-size: 12px; color: #166534;">Simpan atau cetak kredensial berikut sebelum meninggalkan halaman ini.</div>
                </div>
            </div>

            {{-- Credential Card --}}
            <div class="card-ios" id="credentialCard">
                <div class="card-body" style="padding: 32px;">
                    <div style="text-align: center; margin-bottom: 28px;">
                        <div class="user-avatar" style="width: 64px; height: 64px; border-radius: 18px; font-size: 24px; margin: 0 auto 12px;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <h5 style="font-weight: 800; margin: 0;">{{ $user->name }}</h5>
                        @if($siswa)
                            <p style="color: var(--text-secondary); font-size: 13px; margin: 4px 0 0;">
                                {{ $siswa->kelas->nama_kelas ?? '' }} • {{ $siswa->kelas->jurusan->nama_jurusan ?? '' }}
                            </p>
                        @endif
                    </div>

                    <div style="background: var(--bg-secondary); border-radius: 16px; padding: 24px; border: 2px dashed var(--border-color);">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500; width: 120px;">Nama</td>
                                <td style="padding: 8px 0; font-weight: 700; font-size: 14px;">{{ $user->name }}</td>
                            </tr>
                            @if($siswa)
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500;">NIS</td>
                                <td style="padding: 8px 0; font-weight: 700; font-size: 14px;">{{ $siswa->nis }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500;">NISN</td>
                                <td style="padding: 8px 0; font-weight: 700; font-size: 14px;">{{ $siswa->nisn }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500;">Kelas</td>
                                <td style="padding: 8px 0; font-weight: 700; font-size: 14px;">{{ $siswa->kelas->nama_kelas ?? '-' }}</td>
                            </tr>
                            @endif
                            <tr><td colspan="2" style="padding: 12px 0 4px;"><hr style="border-color: var(--border-color); margin: 0;"></td></tr>
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500;">Login NISN</td>
                                <td style="padding: 8px 0;">
                                    <span style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 16px; letter-spacing: 2px; font-family: monospace;">
                                        {{ $siswa->nisn ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-size: 13px; color: var(--text-secondary); font-weight: 500;">Password</td>
                                <td style="padding: 8px 0;">
                                    <span style="background: linear-gradient(135deg, #f59e0b, #ef4444); color: white; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 16px; letter-spacing: 3px; font-family: monospace;" id="passwordDisplay">
                                        {{ $generatedPassword }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    {{-- Warning --}}
                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 12px 16px; margin-top: 20px; display: flex; align-items: flex-start; gap: 10px;">
                        <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b; font-size: 16px; margin-top: 1px;"></i>
                        <div style="font-size: 12px; color: #92400e; font-weight: 500;">
                            <strong>Perhatian:</strong> Password ini hanya ditampilkan <strong>satu kali</strong>. Pastikan untuk menyimpan atau mencetak sebelum meninggalkan halaman ini.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-ios btn-ios-primary flex-grow-1" onclick="printCredential()">
                    <i class="bi bi-printer-fill"></i> Cetak
                </button>
                <button class="btn btn-ios btn-ios-light flex-grow-1" onclick="copyCredential()">
                    <i class="bi bi-clipboard"></i> Salin
                </button>
                <a href="{{ route('admin.siswa.create') }}" class="btn btn-ios btn-ios-success flex-grow-1">
                    <i class="bi bi-plus-lg"></i> Tambah Lagi
                </a>
            </div>
            <div class="mt-2">
                <a href="{{ route('admin.siswa.index') }}" class="btn btn-ios btn-ios-light w-100">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Siswa
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function printCredential() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html><head><title>Kredensial Siswa - {{ $user->name }}</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; padding: 40px; }
            h2 { text-align: center; margin-bottom: 8px; }
            p.sub { text-align: center; color: #666; font-size: 14px; margin-bottom: 32px; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 10px 8px; font-size: 14px; }
            .label { color: #666; width: 130px; }
            .value { font-weight: 700; }
            .highlight { background: #2563eb; color: white; padding: 6px 14px; border-radius: 6px; font-family: monospace; font-weight: 800; font-size: 16px; letter-spacing: 2px; display: inline-block; }
            .highlight-warn { background: #f59e0b; color: white; padding: 6px 14px; border-radius: 6px; font-family: monospace; font-weight: 800; font-size: 16px; letter-spacing: 3px; display: inline-block; }
            hr { border: 1px dashed #ddd; margin: 8px 0; }
            .footer { text-align: center; margin-top: 32px; font-size: 11px; color: #999; }
            .border-box { border: 2px dashed #ddd; border-radius: 12px; padding: 24px; }
        </style></head>
        <body>
            <h2>🎓 CBT SMK</h2>
            <p class="sub">Kredensial Login Siswa</p>
            <div class="border-box">
                <table>
                    <tr><td class="label">Nama</td><td class="value">{{ $user->name }}</td></tr>
                    <tr><td class="label">NIS</td><td class="value">{{ $siswa->nis ?? '-' }}</td></tr>
                    <tr><td class="label">NISN</td><td class="value">{{ $siswa->nisn ?? '-' }}</td></tr>
                    <tr><td class="label">Kelas</td><td class="value">{{ $siswa->kelas->nama_kelas ?? '-' }}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>
                    <tr><td class="label">Login NISN</td><td><span class="highlight">{{ $siswa->nisn ?? '-' }}</span></td></tr>
                    <tr><td class="label">Password</td><td><span class="highlight-warn">{{ $generatedPassword }}</span></td></tr>
                </table>
            </div>
            <p class="footer">
                Simpan kredensial ini dengan baik. Jangan bagikan password ke orang lain.<br>
                Dicetak pada: ${new Date().toLocaleString('id-ID')}<br>
                © CBT SMK - Sistem Ujian Online
            </p>
        </body></html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function copyCredential() {
    const text = `KREDENSIAL LOGIN SISWA\n` +
        `Nama: {{ $user->name }}\n` +
        `NIS: {{ $siswa->nis ?? '-' }}\n` +
        `NISN: {{ $siswa->nisn ?? '-' }}\n` +
        `Kelas: {{ $siswa->kelas->nama_kelas ?? '-' }}\n` +
        `---\n` +
        `Login NISN: {{ $siswa->nisn ?? '-' }}\n` +
        `Password: {{ $generatedPassword }}`;

    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Kredensial berhasil disalin!');
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✅ Kredensial berhasil disalin!');
    });
}
</script>
@endpush
@endsection
