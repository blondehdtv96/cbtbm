@extends('layouts.app')
@section('title', 'Kartu Ujian')
@section('page-title', 'Kartu Ujian')
@section('page-subtitle', 'Cetak kartu peserta sebelum ujian dimulai')

@section('content')
<div class="fade-in">

    @if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #166534; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Action Buttons --}}
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="btn btn-ios btn-ios-primary" onclick="openModal()">
            <i class="bi bi-credit-card-2-front-fill me-1"></i> Cetak Kartu Ujian
        </button>
        <button class="btn btn-ios btn-ios-light" data-bs-toggle="modal" data-bs-target="#modalSettings">
            <i class="bi bi-gear-fill me-1"></i> Pengaturan Kartu
        </button>
    </div>

    {{-- Preview Current Settings --}}
    <div class="card-ios">
        <div class="card-header"><i class="bi bi-eye-fill me-2" style="color:var(--primary);"></i>Preview Pengaturan Kartu</div>
        <div class="card-body">
            <div style="max-width:420px; border: 1.5px solid #1e293b; font-family: 'Times New Roman', serif; background: white; padding: 0;">
                {{-- Header --}}
                <div style="border-bottom: 1.5px solid #1e293b; padding: 10px 14px; display: flex; align-items: center; gap: 12px;">
                    <div style="width:48px;height:48px;background:#e2e8f0;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        @if($kartuSettings['logo'])
                            <img src="{{ asset('storage/'.$kartuSettings['logo']) }}" style="width:100%;height:100%;object-fit:contain;">
                        @else
                            <i class="bi bi-mortarboard-fill" style="font-size:22px;color:#64748b;"></i>
                        @endif
                    </div>
                    <div style="text-align:center;flex:1;">
                        <div style="font-weight:700;font-size:13px;color:#991b1b;">{{ $kartuSettings['judul'] }}</div>
                        <div style="font-weight:700;font-size:13px;">{{ $kartuSettings['nama_sekolah'] }}</div>
                        <div style="font-size:12px;">Tahun Pelajaran {{ $kartuSettings['tahun_pelajaran'] }}</div>
                    </div>
                </div>
                {{-- Body --}}
                <div style="padding: 10px 14px; font-size: 13px;">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr><td style="width:120px;padding:3px 0;font-weight:700;">Nama Peserta</td><td style="width:10px;">:</td><td style="padding:3px 0;">(nama siswa)</td></tr>
                        <tr><td style="padding:3px 0;font-weight:700;">Jurusan/Kelas</td><td>:</td><td style="padding:3px 0;">(jurusan / kelas)</td></tr>
                        @if($kartuSettings['show_sesi'] == '1')
                        <tr><td style="padding:3px 0;font-weight:700;">Sesi - Ruang</td><td>:</td><td style="padding:3px 0;">(sesi - ruang)</td></tr>
                        @endif
                        @if($kartuSettings['show_username'] == '1')
                        <tr><td style="padding:3px 0;font-weight:700;">Username</td><td>:</td><td style="padding:3px 0;">(username)</td></tr>
                        @endif
                        @if($kartuSettings['show_password'] == '1')
                        <tr><td style="padding:3px 0;font-weight:700;">Password</td><td>:</td><td style="padding:3px 0;">(password)</td></tr>
                        @endif
                    </table>
                    @if($kartuSettings['show_foto'] == '1' || $kartuSettings['show_ttd'] == '1')
                    <div style="display:flex;margin-top:12px;align-items:flex-end;">
                        @if($kartuSettings['show_foto'] == '1')
                        <div style="width:70px;height:80px;background:#e2e8f0;border:1px solid #cbd5e1;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-person-fill" style="font-size:30px;color:#94a3b8;"></i>
                        </div>
                        @endif
                        @if($kartuSettings['show_ttd'] == '1')
                        <div style="flex:1;text-align:center;padding-bottom:4px;">
                            <div style="font-weight:700;font-size:12px;">{{ $kartuSettings['nama_sekolah'] }}</div>
                            <div style="margin-top:28px;font-size:12px;">Ttd ,</div>
                            <div style="font-weight:700;font-size:12px;">{{ $kartuSettings['nama_ttd'] ?: '________________' }}</div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ===================== MODAL CETAK ===================== --}}
<div class="modal fade" id="modalKartuUjian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
            <div style="background:linear-gradient(135deg,#1e293b,#334155);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;"><i class="bi bi-printer-fill"></i></div>
                <div><h5 style="font-size:16px;font-weight:700;color:white;margin:0;">Kartu Ujian</h5><small style="color:#94a3b8;font-size:12px;">Pilih Jurusan, Kelas, dan isi Sesi/Ruang</small></div>
            </div>
            <div style="padding:24px;background:var(--bg-card);">
                <form id="formKartuUjian" method="GET" action="{{ route('kartu-peserta.print-by-kelas') }}" target="_blank">
                    <div class="mb-3">
                        <label class="form-label-ios">Jurusan *</label>
                        <select id="selectJurusan" name="jurusan_id" class="form-select form-control-ios" onchange="loadKelas(this.value)" required>
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach($jurusans as $jurusan)
                            <option value="{{ $jurusan->id }}">{{ $jurusan->kode_jurusan ?? '' }} - {{ $jurusan->nama_jurusan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-ios">Kelas *</label>
                        <select id="selectKelas" name="kelas_id" class="form-select form-control-ios" required disabled>
                            <option value="">-- Pilih Jurusan dulu --</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-ios">Sesi - Ruang</label>
                        <input type="text" name="sesi_ruang" class="form-control-ios w-100" placeholder="contoh: 3 - LAB TKJ 3" value="{{ $kartuSettings['ruang'] }}">
                    </div>
                    <div class="d-flex gap-3 justify-content-end">
                        <button type="button" style="background:#dc2626;color:white;border:none;border-radius:10px;padding:10px 20px;font-weight:600;font-size:14px;cursor:pointer;" onclick="closeModal()">
                            <i class="bi bi-x-circle-fill"></i> Tutup
                        </button>
                        <button type="submit" id="btnTampilkan" disabled style="background:linear-gradient(135deg,#16a34a,#15803d);color:white;border:none;border-radius:10px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer;">
                            <i class="bi bi-printer-fill"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ===================== MODAL PENGATURAN ===================== --}}
<div class="modal fade" id="modalSettings" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
            <div style="background:linear-gradient(135deg,#1e293b,#334155);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;"><i class="bi bi-gear-fill"></i></div>
                <div><h5 style="font-size:16px;font-weight:700;color:white;margin:0;">Pengaturan Kartu Peserta</h5><small style="color:#94a3b8;font-size:12px;">Konfigurasi label dan tampilan kartu</small></div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('kartu-peserta.save-settings') }}" enctype="multipart/form-data">
                @csrf
                <div style="padding:24px;max-height:70vh;overflow-y:auto;">
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label-ios" style="text-transform:uppercase;font-size:11px;letter-spacing:1px;color:var(--primary);">Header Kartu</label></div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Judul Kartu</label>
                            <input type="text" name="kartu_judul" class="form-control-ios w-100" value="{{ $kartuSettings['judul'] }}" placeholder="KARTU PESERTA UBK">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Nama Sekolah</label>
                            <input type="text" name="kartu_nama_sekolah" class="form-control-ios w-100" value="{{ $kartuSettings['nama_sekolah'] }}" placeholder="SMK NEGERI 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Tahun Pelajaran</label>
                            <input type="text" name="kartu_tahun_pelajaran" class="form-control-ios w-100" value="{{ $kartuSettings['tahun_pelajaran'] }}" placeholder="2024/2025">
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label-ios" style="text-transform:uppercase;font-size:11px;letter-spacing:1px;color:var(--primary);">Logo Sekolah</label></div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Upload Logo</label>
                            <input type="file" name="kartu_logo" class="form-control-ios w-100" accept="image/*" style="font-size:12px;">
                            <small class="text-muted" style="font-size:11px;">Maks 2MB. Format: JPG, PNG, GIF</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Logo Saat Ini</label>
                            @if($kartuSettings['logo'])
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="{{ asset('storage/'.$kartuSettings['logo']) }}" style="width:48px;height:48px;object-fit:contain;border:1px solid #e2e8f0;border-radius:6px;background:white;">
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#dc2626;">
                                    <input type="checkbox" name="remove_logo" value="1"> Hapus logo
                                </label>
                            </div>
                            @else
                            <div style="color:#94a3b8;font-size:12px;padding:10px 0;">Belum ada logo</div>
                            @endif
                        </div>
                    </div>
                    <hr style="border-color:var(--border-color);">
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label-ios" style="text-transform:uppercase;font-size:11px;letter-spacing:1px;color:var(--primary);">Tanda Tangan</label></div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Nama Penandatangan</label>
                            <input type="text" name="kartu_nama_ttd" class="form-control-ios w-100" value="{{ $kartuSettings['nama_ttd'] }}" placeholder="Endah Sulistiani, S.Pd., M.Si">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Jabatan (opsional)</label>
                            <input type="text" name="kartu_jabatan_ttd" class="form-control-ios w-100" value="{{ $kartuSettings['jabatan_ttd'] }}" placeholder="Kepala Sekolah">
                        </div>
                    </div>
                    <hr style="border-color:var(--border-color);">
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label-ios" style="text-transform:uppercase;font-size:11px;letter-spacing:1px;color:var(--primary);">Default Sesi & Ruang</label></div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Ruang Default</label>
                            <input type="text" name="kartu_ruang" class="form-control-ios w-100" value="{{ $kartuSettings['ruang'] }}" placeholder="LAB TKJ 3">
                        </div>
                    </div>
                    <hr style="border-color:var(--border-color);">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label-ios" style="text-transform:uppercase;font-size:11px;letter-spacing:1px;color:var(--primary);">Tampilkan Field</label></div>
                        @php $toggles = [
                            ['kartu_show_sesi', $kartuSettings['show_sesi'], 'Sesi - Ruang'],
                            ['kartu_show_username', $kartuSettings['show_username'], 'Username'],
                            ['kartu_show_password', $kartuSettings['show_password'], 'Password'],
                            ['kartu_show_foto', $kartuSettings['show_foto'], 'Foto Peserta'],
                            ['kartu_show_ttd', $kartuSettings['show_ttd'], 'Area Tanda Tangan'],
                        ]; @endphp
                        @foreach($toggles as $t)
                        <div class="col-md-4">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 14px;background:var(--bg-secondary);border-radius:10px;font-size:13px;font-weight:600;">
                                <input type="hidden" name="{{ $t[0] }}" value="0">
                                <input type="checkbox" name="{{ $t[0] }}" value="1" {{ $t[1] == '1' ? 'checked' : '' }} style="width:16px;height:16px;">
                                {{ $t[2] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid var(--border-color);display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ajaxUrl = '{{ route("kartu-peserta.kelas-by-jurusan") }}';

function openModal() { new bootstrap.Modal(document.getElementById('modalKartuUjian')).show(); }
function closeModal() { var m = bootstrap.Modal.getInstance(document.getElementById('modalKartuUjian')); if(m) m.hide(); }

function loadKelas(jurusanId) {
    const sel = document.getElementById('selectKelas');
    const btn = document.getElementById('btnTampilkan');
    sel.innerHTML = '<option value="">Memuat...</option>'; sel.disabled = true; btn.disabled = true;
    if (!jurusanId) { sel.innerHTML = '<option value="">-- Pilih Jurusan dulu --</option>'; return; }
    fetch(`${ajaxUrl}?jurusan_id=${jurusanId}`, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json()).then(data => {
        sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
        data.forEach(k => { const o = document.createElement('option'); o.value = k.id; o.textContent = k.nama_kelas; sel.appendChild(o); });
        sel.disabled = false;
        sel.addEventListener('change', function() { btn.disabled = !this.value; });
    }).catch(() => { sel.innerHTML = '<option value="">Gagal memuat</option>'; });
}
</script>
@endpush
