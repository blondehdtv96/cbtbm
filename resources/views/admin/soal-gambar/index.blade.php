@extends('layouts.app')

@section('title', 'Pustaka Gambar Soal')
@section('page-title', 'Pustaka Gambar Soal')
@section('page-subtitle', 'Upload gambar soal & opsi di sini sebelum mereferensikan nama filenya di Excel import')

@section('content')
<div class="fade-in">
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="row g-4">
        {{-- Upload Card --}}
        <div class="col-lg-4">
            <div class="card-ios">
                <div class="card-header"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload Gambar</div>
                <div class="card-body">
                    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.06), rgba(139, 92, 246, 0.06)); border: 1px solid rgba(99, 102, 241, 0.12); border-radius: 14px; padding: 14px 18px; margin-bottom: 18px; font-size: 12px; color: #334155; line-height: 1.7;">
                        Upload gambar di sini <strong>dulu</strong>, baru ketik <strong>nama filenya</strong> (harus sama persis, huruf besar/kecil diabaikan) di kolom "Gambar Soal" / "Gambar Opsi A-E" pada Excel import. Format: jpg/jpeg/png, maks 1MB per file. <strong>Bisa pilih banyak file sekaligus</strong> — otomatis dikirim bertahap supaya tidak kena batas server.
                    </div>

                    <form id="uploadForm" onsubmit="return false;">
                        <input type="file" id="gambarInput" class="form-control-ios w-100 mb-3" accept="image/jpeg,image/png" multiple required>
                        <button type="button" id="uploadBtn" class="btn btn-ios btn-ios-primary w-100" onclick="startBatchUpload()">
                            <i class="bi bi-upload me-1"></i> Upload
                        </button>

                        <div id="uploadProgressWrap" class="mt-3" style="display: none;">
                            <div style="height: 8px; background: rgba(99,102,241,0.1); border-radius: 6px; overflow: hidden;">
                                <div id="uploadProgressBar" style="height: 100%; width: 0%; background: linear-gradient(135deg, var(--primary), var(--accent)); transition: width 0.2s;"></div>
                            </div>
                            <div id="uploadProgressText" style="font-size: 12px; color: var(--text-secondary); margin-top: 6px;"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Library List --}}
        <div class="col-lg-8">
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-images me-2"></i>Gambar Terupload <span class="badge-ios primary">{{ $totalGambar }} total</span></span>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control-ios" placeholder="Cari nama file..." value="{{ request('search') }}">
                    </form>
                </div>
                <div class="card-body p-0" style="overflow-x: auto;">
                    <table class="table-ios">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Preview</th>
                                <th>Nama File</th>
                                <th style="width: 90px;">Ukuran</th>
                                <th style="width: 140px;">Diupload</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gambars as $gambar)
                            <tr>
                                <td>
                                    <img src="{{ asset('storage/' . $gambar->stored_path) }}" alt="{{ $gambar->original_filename }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                                </td>
                                <td><code>{{ $gambar->original_filename }}</code></td>
                                <td>{{ number_format($gambar->size / 1024, 1) }} KB</td>
                                <td style="font-size: 12px; color: var(--text-secondary);">{{ $gambar->created_at->diffForHumans() }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.soal-gambar.destroy', $gambar) }}" onsubmit="return confirm('Hapus gambar ini dari pustaka?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ios btn-ios-sm btn-ios-danger"><i class="bi bi-trash3-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4" style="color: var(--text-secondary);">Belum ada gambar diupload.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $gambars->links() }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// PHP membatasi jumlah file per request (max_file_uploads, default 20) dan ukuran
// total request (post_max_size). Supaya upload ratusan gambar sekaligus tetap
// jalan tanpa perlu ubah konfigurasi server, file dikirim bertahap (batch kecil)
// secara berurutan lewat fetch, bukan satu form submit besar.
const BATCH_SIZE = 15;

async function startBatchUpload() {
    const input = document.getElementById('gambarInput');
    const files = Array.from(input.files);

    if (files.length === 0) {
        alert('Pilih minimal satu gambar dulu.');
        return;
    }

    const btn = document.getElementById('uploadBtn');
    const wrap = document.getElementById('uploadProgressWrap');
    const bar = document.getElementById('uploadProgressBar');
    const text = document.getElementById('uploadProgressText');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    btn.disabled = true;
    wrap.style.display = 'block';

    const batches = [];
    for (let i = 0; i < files.length; i += BATCH_SIZE) {
        batches.push(files.slice(i, i + BATCH_SIZE));
    }

    let totalUploaded = 0;
    const allSkipped = [];
    let failed = false;

    for (let i = 0; i < batches.length; i++) {
        text.textContent = `Mengupload batch ${i + 1} dari ${batches.length}... (${files.length} file total)`;
        bar.style.width = `${Math.round((i / batches.length) * 100)}%`;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        batches[i].forEach(file => formData.append('gambar[]', file));

        try {
            const response = await fetch('{{ route('admin.soal-gambar.store') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            });

            if (!response.ok) {
                const errBody = await response.json().catch(() => null);
                const msg = errBody?.errors ? Object.values(errBody.errors).flat().join(', ') : `HTTP ${response.status}`;
                text.textContent = `Batch ${i + 1} gagal: ${msg}. Batch lain tetap dilanjutkan...`;
                failed = true;
                continue;
            }

            const result = await response.json();
            totalUploaded += result.uploaded;
            allSkipped.push(...result.skipped);
        } catch (e) {
            text.textContent = `Batch ${i + 1} gagal (koneksi terputus). Melanjutkan batch berikutnya...`;
            failed = true;
        }
    }

    bar.style.width = '100%';

    let summary = `Selesai: ${totalUploaded} dari ${files.length} gambar berhasil diupload.`;
    if (allSkipped.length > 0) {
        summary += ` ${allSkipped.length} dilewati karena nama file sudah ada.`;
    }
    if (failed) {
        summary += ' Ada batch yang gagal — cek daftar di bawah, upload ulang file yang belum masuk.';
    }
    text.textContent = summary;

    setTimeout(() => window.location.reload(), 1500);
}
</script>
@endpush
@endsection
