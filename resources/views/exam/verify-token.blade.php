@extends('layouts.app')
@section('title', 'Verifikasi Token')
@section('page-title', 'Verifikasi Token')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card-ios" style="overflow:hidden;">
                {{-- Header --}}
                <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 24px;text-align:center;color:white;">
                    <div style="width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,0.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;font-size:26px;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h5 style="font-weight:700;font-size:18px;margin:0 0 6px;">Masukkan Token Ujian</h5>
                    <p style="font-size:13px;opacity:0.85;margin:0;">Token diberikan oleh pengawas ujian</p>
                </div>

                {{-- Ujian Info --}}
                <div style="background:var(--bg-secondary);padding:14px 24px;border-bottom:1px solid var(--border-color);font-size:13px;">
                    <div class="d-flex justify-content-between">
                        <span style="color:#64748b;">Ujian</span>
                        <strong>{{ $ujian->nama_ujian }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span style="color:#64748b;">Mata Pelajaran</span>
                        <span>{{ $ujian->mapel->nama_mapel ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span style="color:#64748b;">Durasi</span>
                        <span>{{ $ujian->durasi_menit }} menit</span>
                    </div>
                </div>

                {{-- Token Form --}}
                <div style="padding:28px 24px;">
                    @if(session('error'))
                    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('exam.verify-token', $ujian) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label-ios" style="text-align:center;display:block;margin-bottom:12px;">Token (5 karakter)</label>
                            <div class="d-flex justify-content-center gap-2" id="tokenInputs">
                                @for($i = 0; $i < 5; $i++)
                                <input type="text"
                                    class="token-char"
                                    maxlength="1"
                                    data-index="{{ $i }}"
                                    style="width:52px;height:60px;text-align:center;font-size:24px;font-weight:800;letter-spacing:2px;
                                    border:2px solid var(--border-color);border-radius:14px;background:var(--bg-secondary);
                                    text-transform:uppercase;outline:none;transition:all 0.2s;"
                                    autocomplete="off"
                                    oninput="handleTokenInput(this, {{ $i }})"
                                    onkeydown="handleTokenKeydown(event, {{ $i }})"
                                    onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                                    onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'"
                                    required>
                                @endfor
                            </div>
                            <input type="hidden" name="token" id="tokenHidden">
                        </div>
                        <button type="submit" class="btn btn-ios btn-ios-primary w-100" style="padding:14px;font-size:15px;font-weight:700;" id="btnVerify">
                            <i class="bi bi-shield-check me-2"></i>Verifikasi & Mulai Ujian
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('siswa.dashboard') }}" style="color:#64748b;font-size:13px;text-decoration:none;">
                            <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="card-ios mt-3" style="border-left:4px solid #f59e0b;">
                <div class="card-body" style="font-size:12px;color:#64748b;padding:14px 18px;">
                    <i class="bi bi-info-circle-fill me-1" style="color:#f59e0b;"></i>
                    <strong>Perhatian:</strong> Token diberikan oleh pengawas ujian saat ujian akan dimulai. Pastikan token yang dimasukkan benar sebelum memulai.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const chars = document.querySelectorAll('.token-char');
const hiddenInput = document.getElementById('tokenHidden');

function handleTokenInput(el, idx) {
    el.value = el.value.toUpperCase().replace(/[^A-Z]/g, '');
    if (el.value && idx < 4) {
        chars[idx + 1].focus();
    }
    updateHidden();
}

function handleTokenKeydown(e, idx) {
    if (e.key === 'Backspace' && !chars[idx].value && idx > 0) {
        chars[idx - 1].focus();
        chars[idx - 1].value = '';
        updateHidden();
    }
}

function updateHidden() {
    let token = '';
    chars.forEach(c => token += c.value);
    hiddenInput.value = token;
}

// Handle paste
chars[0].addEventListener('paste', function(e) {
    e.preventDefault();
    const pasted = (e.clipboardData.getData('text') || '').toUpperCase().replace(/[^A-Z]/g, '');
    for (let i = 0; i < Math.min(pasted.length, 5); i++) {
        chars[i].value = pasted[i];
    }
    if (pasted.length >= 5) chars[4].focus();
    else if (pasted.length > 0) chars[pasted.length - 1].focus();
    updateHidden();
});

// Auto focus first input
chars[0].focus();
</script>
@endpush
@endsection
