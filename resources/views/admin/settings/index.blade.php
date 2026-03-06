@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Kelola pengaturan aplikasi, logo, dan konfigurasi sistem')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-gear-fill me-2"></i>
                        Pengaturan Sistem
                    </h5>
                    <div>
                        <button type="button" class="btn btn-light btn-sm" onclick="clearCache()">
                            <i class="bi bi-arrow-clockwise"></i> Clear Cache
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="confirmReset()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Default
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        @foreach($groups as $key => $label)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                        id="{{ $key }}-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#{{ $key }}" 
                                        type="button" 
                                        role="tab">
                                    @if($key === 'general')
                                        <i class="bi bi-info-circle me-1"></i>
                                    @elseif($key === 'appearance')
                                        <i class="bi bi-palette me-1"></i>
                                    @elseif($key === 'exam')
                                        <i class="bi bi-file-text me-1"></i>
                                    @elseif($key === 'email')
                                        <i class="bi bi-envelope me-1"></i>
                                    @endif
                                    {{ $label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Form -->
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="tab-content">
                            @foreach($groups as $groupKey => $groupLabel)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                     id="{{ $groupKey }}" 
                                     role="tabpanel">
                                    
                                    <div class="row">
                                        @foreach($settings[$groupKey] as $setting)
                                            <div class="col-md-{{ $setting->type === 'textarea' ? '12' : '6' }} mb-4">
                                                <label class="form-label fw-bold">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <i class="bi bi-info-circle text-muted" 
                                                           data-bs-toggle="tooltip" 
                                                           title="{{ $setting->description }}"></i>
                                                    @endif
                                                </label>

                                                @if($setting->type === 'text')
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="{{ $setting->key }}" 
                                                           value="{{ old($setting->key, $setting->value) }}"
                                                           placeholder="{{ $setting->label }}">

                                                @elseif($setting->type === 'textarea')
                                                    <textarea class="form-control" 
                                                              name="{{ $setting->key }}" 
                                                              rows="3"
                                                              placeholder="{{ $setting->label }}">{{ old($setting->key, $setting->value) }}</textarea>

                                                @elseif($setting->type === 'number')
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="{{ $setting->key }}" 
                                                           value="{{ old($setting->key, $setting->value) }}"
                                                           placeholder="{{ $setting->label }}">

                                                @elseif($setting->type === 'color')
                                                    <div class="input-group">
                                                        <input type="color" 
                                                               class="form-control form-control-color" 
                                                               name="{{ $setting->key }}" 
                                                               value="{{ old($setting->key, $setting->value) }}"
                                                               style="max-width: 80px;">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               value="{{ old($setting->key, $setting->value) }}"
                                                               readonly>
                                                    </div>

                                                @elseif($setting->type === 'boolean')
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="{{ $setting->key }}" 
                                                               id="{{ $setting->key }}"
                                                               {{ old($setting->key, $setting->value) == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ $setting->key }}">
                                                            Aktif
                                                        </label>
                                                    </div>

                                                @elseif($setting->type === 'image')
                                                    @if($setting->value)
                                                        <div class="mb-2">
                                                            <img src="{{ asset('storage/' . $setting->value) }}" 
                                                                 alt="{{ $setting->label }}" 
                                                                 class="img-thumbnail"
                                                                 style="max-height: 150px;">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger ms-2"
                                                                    onclick="deleteImage('{{ $setting->key }}')">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </div>
                                                    @endif
                                                    <input type="file" 
                                                           class="form-control" 
                                                           name="{{ $setting->key }}"
                                                           accept="image/*">
                                                    <small class="text-muted">Max 2MB, format: JPG, PNG, GIF</small>
                                                @endif

                                                @if($setting->description)
                                                    <small class="text-muted d-block mt-1">
                                                        {{ $setting->description }}
                                                    </small>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Konfirmasi Reset
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin mereset semua pengaturan ke nilai default?</p>
                <p class="text-danger mb-0">
                    <strong>Peringatan:</strong> Semua perubahan yang telah Anda buat akan hilang!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('admin.settings.reset') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">Ya, Reset</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Color picker sync
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = colorInput.nextElementSibling;
    
    colorInput.addEventListener('input', function() {
        textInput.value = this.value;
    });
});

// Confirm reset
function confirmReset() {
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));
    modal.show();
}

// Delete image
function deleteImage(key) {
    if (!confirm('Yakin ingin menghapus gambar ini?')) {
        return;
    }

    fetch('{{ route("admin.settings.delete-image") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ key: key })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Gagal menghapus gambar: ' + data.error);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error);
    });
}

// Clear cache
function clearCache() {
    if (!confirm('Yakin ingin membersihkan cache?')) {
        return;
    }

    window.location.href = '{{ route("admin.settings.clear-cache") }}';
}
</script>
@endpush
