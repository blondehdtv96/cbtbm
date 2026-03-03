@extends('layouts.app')

@section('title', 'Import Siswa')
@section('page-title', 'Import Siswa')
@section('page-subtitle', 'Upload data siswa secara massal via file Excel')

@section('content')
<div class="fade-in">
    <div class="row g-4">
        {{-- Upload Card --}}
        <div class="col-lg-7">
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload File Excel</span>
                    <a href="{{ route('admin.import-siswa.template') }}" class="btn btn-ios btn-ios-sm btn-ios-success">
                        <i class="bi bi-download"></i> Download Template
                    </a>
                </div>
                <div class="card-body">
                    {{-- Info Box --}}
                    <div style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.06), rgba(99, 102, 241, 0.06)); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 14px; padding: 18px 22px; margin-bottom: 24px;">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37, 99, 235, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="bi bi-lightbulb-fill" style="color: #2563eb; font-size: 18px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: #1e3a5f; margin-bottom: 6px;">Panduan Import</div>
                                <ul style="font-size: 12px; color: #334155; margin: 0; padding-left: 16px; line-height: 1.8;">
                                    <li>Download template terlebih dahulu, lalu isi data sesuai format</li>
                                    <li>Kolom wajib: <strong>NISN</strong>, <strong>NIS</strong>, <strong>Nama Lengkap</strong>, <strong>Kelas</strong></li>
                                    <li><strong>Password otomatis</strong> di-generate oleh sistem</li>
                                    <li>Siswa login menggunakan <strong>NISN + Password</strong></li>
                                    <li>Format file: <strong>.xlsx</strong> atau <strong>.xls</strong> (maks. 5MB)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Upload Form --}}
                    <form method="POST" action="{{ route('admin.import-siswa.preview') }}" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        {{-- Drag & Drop Area --}}
                        <div id="dropZone" style="border: 2px dashed rgba(37, 99, 235, 0.25); border-radius: 16px; padding: 40px 24px; text-align: center; transition: all 0.3s ease; cursor: pointer; background: rgba(37, 99, 235, 0.02);" onclick="document.getElementById('fileInput').click()">
                            <div id="dropIcon" style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(99, 102, 241, 0.1)); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="bi bi-file-earmark-spreadsheet" style="font-size: 28px; color: #2563eb;"></i>
                            </div>
                            <div id="dropText" style="font-weight: 600; font-size: 15px; color: #1e293b; margin-bottom: 6px;">
                                Drag & drop file Excel di sini
                            </div>
                            <div style="font-size: 13px; color: #64748b;">
                                atau <span style="color: #2563eb; font-weight: 600; text-decoration: underline;">klik untuk memilih file</span>
                            </div>
                            <div id="fileName" style="display: none; margin-top: 14px; padding: 10px 16px; background: rgba(34, 197, 94, 0.08); border-radius: 10px; font-size: 13px; color: #15803d; font-weight: 600;">
                            </div>
                            <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" style="display: none;" onchange="handleFileSelect(event)">
                        </div>

                        <button type="submit" id="uploadBtn" class="btn btn-ios btn-ios-primary w-100 mt-3" disabled style="padding: 14px; font-size: 15px;">
                            <i class="bi bi-eye-fill me-2"></i> Preview & Validasi Data
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-5">
            {{-- Stats Quick Card --}}
            <div class="card-ios mb-3">
                <div class="card-header">
                    <i class="bi bi-bar-chart-fill me-2"></i>Statistik Siswa
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div style="background: rgba(37, 99, 235, 0.06); border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 800; color: var(--primary);">{{ \App\Models\Siswa::count() }}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 500;">Total Siswa</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background: rgba(34, 197, 94, 0.06); border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 800; color: var(--success);">{{ \App\Models\Kelas::where('is_active', true)->count() }}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 500;">Kelas Aktif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kelas List --}}
            <div class="card-ios mb-3">
                <div class="card-header">
                    <i class="bi bi-door-open-fill me-2"></i>Daftar Kelas Tersedia
                </div>
                <div class="card-body p-0" style="max-height: 260px; overflow-y: auto;">
                    <table class="table-ios" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kelas</th>
                                <th>Jurusan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kelasList as $kelas)
                            <tr>
                                <td><code>{{ $kelas->id }}</code></td>
                                <td style="font-weight: 600;">{{ $kelas->nama_kelas }}</td>
                                <td>{{ $kelas->jurusan->nama_jurusan ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Imports --}}
            @if($recentImports->count() > 0)
            <div class="card-ios">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Import
                </div>
                <div class="card-body p-0">
                    @foreach($recentImports as $log)
                    <div style="padding: 12px 18px; border-bottom: 1px solid var(--border-color); font-size: 13px;">
                        <div style="font-weight: 600;">{{ $log->description }}</div>
                        <div style="color: var(--text-secondary); font-size: 11px; margin-top: 2px;">
                            {{ $log->created_at->diffForHumans() }} • {{ $log->user->name ?? 'System' }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

// Drag & Drop events
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#2563eb';
        dropZone.style.background = 'rgba(37, 99, 235, 0.06)';
    });
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'rgba(37, 99, 235, 0.25)';
        dropZone.style.background = 'rgba(37, 99, 235, 0.02)';
    });
});

dropZone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect({ target: fileInput });
    }
});

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].includes(ext)) {
            const sizeKb = (file.size / 1024).toFixed(1);
            document.getElementById('fileName').style.display = 'block';
            document.getElementById('fileName').innerHTML =
                `<i class="bi bi-file-earmark-check-fill me-1"></i> ${file.name} <span style="color: #64748b; font-weight: 400;">(${sizeKb} KB)</span>`;
            document.getElementById('uploadBtn').disabled = false;
            document.getElementById('dropIcon').innerHTML = '<i class="bi bi-check-circle-fill" style="font-size: 28px; color: #22c55e;"></i>';
            document.getElementById('dropText').textContent = 'File siap diupload';
            document.getElementById('dropText').style.color = '#15803d';
        } else {
            alert('Format file harus .xlsx atau .xls');
            fileInput.value = '';
        }
    }
}
</script>
@endpush
@endsection
