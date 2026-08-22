@extends('layouts.app')

@section('title', 'Import Bank Soal')
@section('page-title', 'Import Bank Soal')
@section('page-subtitle', 'Upload soal secara massal via file Excel')

@section('content')
<div class="fade-in">
    <div class="row g-4">
        {{-- Upload Card --}}
        <div class="col-lg-7">
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload File Soal</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.soal-gambar.index') }}" class="btn btn-ios btn-ios-sm btn-ios-light">
                            <i class="bi bi-images"></i> Pustaka Gambar Soal
                        </a>
                        <a href="{{ route('admin.import-banksoal.template') }}" class="btn btn-ios btn-ios-sm btn-ios-success">
                            <i class="bi bi-download"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Info Box --}}
                    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.06), rgba(139, 92, 246, 0.06)); border: 1px solid rgba(99, 102, 241, 0.12); border-radius: 14px; padding: 18px 22px; margin-bottom: 24px;">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99, 102, 241, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="bi bi-lightbulb-fill" style="color: #6366f1; font-size: 18px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: #1e3a5f; margin-bottom: 6px;">Panduan Import Soal</div>
                                <ul style="font-size: 12px; color: #334155; margin: 0; padding-left: 16px; line-height: 1.8;">
                                    <li>Download template terlebih dahulu, lalu isi data sesuai format</li>
                                    <li>Template memiliki <strong>2 sheet</strong>: Soal Pilihan Ganda & Soal Essay</li>
                                    <li>Kolom wajib PG: <strong>Kode Mapel</strong>, <strong>Pertanyaan</strong>, <strong>Opsi A-B</strong>, <strong>Jawaban Benar</strong></li>
                                    <li>Kolom wajib Essay: <strong>Kode Mapel</strong>, <strong>Pertanyaan</strong></li>
                                    <li>Soal akan dikaitkan ke <strong>guru yang sedang login</strong></li>
                                    <li>Format file: <strong>.xlsx</strong> atau <strong>.xls</strong> (maks. 5MB)</li>
                                    <li>Ingin melampirkan gambar? Upload dulu file-nya ke <strong>Pustaka Gambar Soal</strong>, baru ketik <strong>nama filenya</strong> (mis. <code>soal1.jpg</code>) di kolom Gambar Soal / Gambar Opsi A-E — <strong>jangan tempel/paste gambar langsung ke sel Excel</strong>, itu tidak akan terbaca</li>
                                    <li>Kalau soal/opsi hanya berupa gambar tanpa teks, kolom Pertanyaan/Opsi boleh dikosongkan asal kolom Gambar-nya diisi</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Upload Form --}}
                    <form method="POST" action="{{ route('admin.import-banksoal.preview') }}" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        {{-- Drag & Drop Area --}}
                        <div id="dropZone" style="border: 2px dashed rgba(99, 102, 241, 0.25); border-radius: 16px; padding: 40px 24px; text-align: center; transition: all 0.3s ease; cursor: pointer; background: rgba(99, 102, 241, 0.02);" onclick="document.getElementById('fileInput').click()">
                            <div id="dropIcon" style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="bi bi-file-earmark-spreadsheet" style="font-size: 28px; color: #6366f1;"></i>
                            </div>
                            <div id="dropText" style="font-weight: 600; font-size: 15px; color: #1e293b; margin-bottom: 6px;">
                                Drag & drop file Excel di sini
                            </div>
                            <div style="font-size: 13px; color: #64748b;">
                                atau <span style="color: #6366f1; font-weight: 600; text-decoration: underline;">klik untuk memilih file</span>
                            </div>
                            <div id="fileName" style="display: none; margin-top: 14px; padding: 10px 16px; background: rgba(34, 197, 94, 0.08); border-radius: 10px; font-size: 13px; color: #15803d; font-weight: 600;">
                            </div>
                            <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" style="display: none;" onchange="handleFileSelect(event)">
                        </div>

                        <button type="submit" id="uploadBtn" class="btn btn-ios btn-ios-primary w-100 mt-3" disabled style="padding: 14px; font-size: 15px;">
                            <i class="bi bi-eye-fill me-2"></i> Preview & Validasi Soal
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-5">
            {{-- Stats --}}
            <div class="card-ios mb-3">
                <div class="card-header">
                    <i class="bi bi-bar-chart-fill me-2"></i>Statistik Bank Soal
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div style="background: rgba(99, 102, 241, 0.06); border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 800; color: #6366f1;">{{ \App\Models\BankSoal::count() }}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 500;">Total Soal</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background: rgba(34, 197, 94, 0.06); border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 800; color: var(--success);">{{ \App\Models\Mapel::where('is_active', true)->count() }}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 500;">Mapel Aktif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Available Mapels --}}
            <div class="card-ios mb-3">
                <div class="card-header">
                    <i class="bi bi-book-fill me-2"></i>Daftar Kode Mapel
                </div>
                <div class="card-body p-0" style="max-height: 220px; overflow-y: auto;">
                    <table class="table-ios" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Mapel</th>
                                <th>Tipe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mapels as $mapel)
                            <tr>
                                <td><code style="font-weight: 700; color: #6366f1;">{{ $mapel->kode_mapel }}</code></td>
                                <td style="font-weight: 600;">{{ $mapel->nama_mapel }}</td>
                                <td>
                                    @if($mapel->is_umum)
                                        <span class="badge-ios info" style="font-size: 10px;">Umum</span>
                                    @else
                                        <span class="badge-ios purple" style="font-size: 10px;">Kejuruan</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Format Info --}}
            <div class="card-ios mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle-fill me-2"></i>Format Kolom
                </div>
                <div class="card-body" style="font-size: 12px; line-height: 1.8;">
                    <div class="mb-2">
                        <strong style="color: #6366f1;">Tipe Soal:</strong>
                        <code>pg</code> <code>essay</code> <code>pg_kompleks</code> <code>menjodohkan</code>
                    </div>
                    <div>
                        <strong style="color: #6366f1;">Jawaban Benar:</strong>
                        <code>A</code> <code>B</code> <code>C</code> <code>D</code> <code>E</code>
                    </div>
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

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#6366f1';
        dropZone.style.background = 'rgba(99, 102, 241, 0.06)';
    });
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'rgba(99, 102, 241, 0.25)';
        dropZone.style.background = 'rgba(99, 102, 241, 0.02)';
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
