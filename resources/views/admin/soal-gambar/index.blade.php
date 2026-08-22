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
                        Upload gambar di sini <strong>dulu</strong>, baru ketik <strong>nama filenya</strong> (harus sama persis, huruf besar/kecil diabaikan) di kolom "Gambar Soal" / "Gambar Opsi A-E" pada Excel import. Format: jpg/jpeg/png, maks 1MB per file.
                    </div>

                    <form method="POST" action="{{ route('admin.soal-gambar.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="gambar[]" class="form-control-ios w-100 mb-3" accept="image/jpeg,image/png" multiple required>
                        <button type="submit" class="btn btn-ios btn-ios-primary w-100">
                            <i class="bi bi-upload me-1"></i> Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Library List --}}
        <div class="col-lg-8">
            <div class="card-ios">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-images me-2"></i>Gambar Terupload</span>
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
@endsection
