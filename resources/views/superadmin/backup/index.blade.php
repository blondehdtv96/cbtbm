@extends('layouts.app')

@section('title', 'Backup Database')
@section('page-title', 'Backup Database')
@section('page-subtitle', 'Backup, upload, dan restore database — selalu mengikuti tabel yang benar-benar ada di server')

@section('content')
<div class="fade-in">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-database-fill"></i></div>
                <div class="stat-value">{{ count($tables) }}</div>
                <div class="stat-label">Tabel di Database</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-hdd-fill"></i></div>
                <div class="stat-value">{{ $totalDbSizeHuman }}</div>
                <div class="stat-label">Ukuran Database</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-file-earmark-zip-fill"></i></div>
                <div class="stat-value">{{ count($backupFiles) }}</div>
                <div class="stat-label">File Backup</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-pie-chart-fill"></i></div>
                <div class="stat-value">{{ $diskUsage['used_percent'] }}%</div>
                <div class="stat-label">Disk Terpakai ({{ $diskUsage['total_human'] }})</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Tabel dalam Database --}}
            <div class="card-ios mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-table me-2"></i>Tabel dalam Database ({{ $database }})</span>
                    <span class="badge-ios primary">Live dari server</span>
                </div>
                <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
                    <table class="table-ios" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Nama Tabel</th>
                                <th>Jumlah Baris</th>
                                <th>Ukuran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tables as $table)
                            <tr>
                                <td><code>{{ $table['name'] }}</code></td>
                                <td>{{ number_format($table['rows']) }}</td>
                                <td>{{ $table['size_human'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body" style="padding-top: 0;">
                    <small style="color: var(--text-muted);">
                        <i class="bi bi-info-circle"></i>
                        Daftar ini dibaca langsung dari <code>information_schema</code> — backup akan selalu menyertakan tabel-tabel di atas apa adanya, tanpa daftar tabel yang di-hardcode.
                    </small>
                </div>
            </div>

            {{-- Daftar Backup --}}
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-archive-fill me-2"></i>Daftar Backup</span>
                    <span class="badge-ios secondary">{{ count($backupFiles) }} file</span>
                </div>
                <div class="card-body p-0">
                    @if(count($backupFiles) === 0)
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Belum Ada Backup</h5>
                            <p>Buat backup baru atau upload file backup dari server lain.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table-ios">
                                <thead>
                                    <tr>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backupFiles as $file)
                                    <tr>
                                        <td style="font-family: monospace; font-size: 12px;">{{ $file['filename'] }}</td>
                                        <td>{{ $file['size_human'] }}</td>
                                        <td>
                                            <div style="font-size: 13px;">{{ $file['created_at']->format('d/m/Y H:i') }}</div>
                                            <div style="font-size: 11px; color: var(--text-muted);">{{ $file['created_at']->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('superadmin.backup.download', $file['filename']) }}" class="btn btn-ios btn-ios-sm btn-ios-light" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-ios btn-ios-sm btn-ios-warning" title="Restore"
                                                        onclick="openRestoreModal('{{ $file['filename'] }}')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                                <form action="{{ route('superadmin.backup.destroy', $file['filename']) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Hapus file backup {{ $file['filename'] }}? Tindakan ini tidak bisa dibatalkan.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-ios btn-ios-sm btn-ios-danger" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Buat Backup Baru --}}
            <div class="card-ios mb-4">
                <div class="card-header">
                    <i class="bi bi-plus-circle-fill me-2"></i>Buat Backup Baru
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.backup.store') }}">
                        @csrf
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="compress" id="compress" value="1" checked>
                            <label class="form-check-label" for="compress">Kompres hasil backup (gzip)</label>
                        </div>
                        <button type="submit" class="btn btn-ios btn-ios-primary w-100" style="padding: 12px;">
                            <i class="bi bi-database-fill-down me-2"></i>Backup Sekarang
                        </button>
                        <small class="d-block mt-2" style="color: var(--text-muted);">
                            Menggunakan <code>mysqldump</code> jika tersedia di server, otomatis fallback ke metode PHP murni jika tidak.
                        </small>
                    </form>
                </div>
            </div>

            {{-- Upload & Restore --}}
            <div class="card-ios">
                <div class="card-header">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload File Backup
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.backup.upload') }}" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <div id="dropZone" style="border: 2px dashed rgba(37, 99, 235, 0.25); border-radius: 16px; padding: 32px 20px; text-align: center; transition: all 0.3s ease; cursor: pointer; background: rgba(37, 99, 235, 0.02);" onclick="document.getElementById('fileInput').click()">
                            <div id="dropIcon" style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(99, 102, 241, 0.1)); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                <i class="bi bi-file-earmark-zip" style="font-size: 24px; color: #2563eb;"></i>
                            </div>
                            <div id="dropText" style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 4px;">
                                Drag & drop file .sql / .sql.gz
                            </div>
                            <div style="font-size: 12px; color: #64748b;">
                                atau <span style="color: #2563eb; font-weight: 600; text-decoration: underline;">klik untuk memilih file</span>
                            </div>
                            <div id="fileName" style="display: none; margin-top: 12px; padding: 8px 14px; background: rgba(34, 197, 94, 0.08); border-radius: 10px; font-size: 12px; color: #15803d; font-weight: 600;"></div>
                            <input type="file" name="backup_file" id="fileInput" accept=".sql,.gz" style="display: none;" onchange="handleFileSelect(event)">
                        </div>
                        <button type="submit" id="uploadBtn" class="btn btn-ios btn-ios-primary w-100 mt-3" disabled>
                            <i class="bi bi-upload me-2"></i>Upload
                        </button>
                    </form>
                    <small class="d-block mt-2" style="color: var(--text-muted);">
                        Setelah diupload, file akan muncul di Daftar Backup dan bisa langsung di-restore.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Restore Confirmation Modal --}}
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="restoreForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Restore Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan me-restore database dari file:</p>
                    <p><code id="restoreFilename"></code></p>
                    <p class="text-danger">
                        <strong>Peringatan:</strong> Seluruh data saat ini akan <strong>ditimpa</strong> oleh isi file backup ini.
                        Sebuah safety backup akan otomatis dibuat sebelum proses restore berjalan.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Ketik nama database (<code>{{ $database }}</code>) untuk konfirmasi:</label>
                        <input type="text" name="confirm_database" class="form-control" required autocomplete="off">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm_ack" value="1" id="confirmAck" required>
                        <label class="form-check-label" for="confirmAck">
                            Saya paham data saat ini akan ditimpa dan tindakan ini tidak bisa dibatalkan.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Ya, Restore Database</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openRestoreModal(filename) {
    document.getElementById('restoreFilename').textContent = filename;
    document.getElementById('restoreForm').action = '{{ url("superadmin/backup") }}/' + encodeURIComponent(filename) + '/restore';
    new bootstrap.Modal(document.getElementById('restoreModal')).show();
}

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

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
    if (!file) return;

    const name = file.name.toLowerCase();
    if (!name.endsWith('.sql') && !name.endsWith('.sql.gz')) {
        alert('Format file harus .sql atau .sql.gz');
        fileInput.value = '';
        return;
    }

    const sizeKb = (file.size / 1024).toFixed(1);
    document.getElementById('fileName').style.display = 'block';
    document.getElementById('fileName').innerHTML =
        `<i class="bi bi-file-earmark-check-fill me-1"></i> ${file.name} <span style="color: #64748b; font-weight: 400;">(${sizeKb} KB)</span>`;
    document.getElementById('uploadBtn').disabled = false;
    document.getElementById('dropIcon').innerHTML = '<i class="bi bi-check-circle-fill" style="font-size: 24px; color: #22c55e;"></i>';
    document.getElementById('dropText').textContent = 'File siap diupload';
    document.getElementById('dropText').style.color = '#15803d';
}
</script>
@endpush
@endsection
