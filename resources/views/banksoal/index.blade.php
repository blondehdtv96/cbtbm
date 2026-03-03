@extends('layouts.app')
@section('title', 'Bank Soal')
@section('page-title', 'Bank Soal')
@section('page-subtitle', 'Kelola soal ujian per mata pelajaran')

@section('content')
<div class="fade-in">

    {{-- Flash --}}
    @if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #166534; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Action Buttons (top bar, like reference) --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button type="button" class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSoal">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Soal Baru
        </button>
        <a href="{{ route('admin.import-banksoal.index') }}" class="btn btn-ios btn-ios-success">
            <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> Upload File Soal
        </a>
        <a href="{{ route('admin.import-banksoal.template') }}" class="btn btn-ios btn-ios-light">
            <i class="bi bi-download me-1"></i> Download Template Excel
        </a>
    </div>

    {{-- Main Table --}}
    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No.</th>
                        <th>Kode Mapel</th>
                        <th>Mata Pelajaran</th>
                        <th style="text-align: center;">Soal PG</th>
                        <th style="text-align: center;">Soal Essay</th>
                        <th style="text-align: center;">Total</th>
                        <th>Jurusan</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapels as $idx => $m)
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: #64748b;">{{ $idx + 1 }}.</td>
                        <td>
                            <span style="font-family: 'Courier New', monospace; font-weight: 800; font-size: 13px; color: #2563eb; background: rgba(37,99,235,0.07); padding: 4px 10px; border-radius: 8px;">
                                {{ $m->kode_mapel }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a;">{{ $m->nama_mapel }}</div>
                            @if($m->is_umum)
                                <small style="color: #94a3b8; font-size: 11px;">Mata Pelajaran Umum</small>
                            @else
                                <small style="color: #94a3b8; font-size: 11px;">Mata Pelajaran Kejuruan</small>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($m->pg_soal > 0)
                                <span class="badge-ios purple">{{ $m->pg_soal }} soal</span>
                            @else
                                <span style="color: #cbd5e1; font-size: 13px;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($m->essay_soal > 0)
                                <span class="badge-ios info">{{ $m->essay_soal }} soal</span>
                            @else
                                <span style="color: #cbd5e1; font-size: 13px;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($m->total_soal > 0)
                                <span style="font-weight: 800; color: #0f172a; font-size: 15px;">{{ $m->total_soal }}</span>
                            @else
                                <span style="color: #e2e8f0; font-size: 13px;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($m->jurusan)
                                <span class="badge-ios secondary" style="font-size: 11px;">{{ $m->jurusan->kode_jurusan }}</span>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ Str::limit($m->jurusan->nama_jurusan, 18) }}</div>
                            @else
                                <span class="badge-ios info" style="font-size: 11px;">Umum</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($m->is_active)
                                <span class="badge-ios success"><i class="bi bi-circle-fill" style="font-size: 7px;"></i> Aktif</span>
                            @else
                                <span class="badge-ios secondary"><i class="bi bi-circle" style="font-size: 7px;"></i> Non Aktif</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex gap-1 justify-content-center">
                                {{-- Lihat Soal --}}
                                <a href="{{ route('banksoal.index', ['mapel_id' => $m->id]) }}"
                                   class="btn btn-ios btn-ios-sm"
                                   style="background: rgba(6,182,212,0.1); color: #0891b2; border: 1px solid rgba(6,182,212,0.2);"
                                   title="Lihat & Kelola Soal">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                {{-- Tambah Soal ke Mapel ini --}}
                                <button type="button"
                                   class="btn btn-ios btn-ios-sm"
                                   style="background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.2);"
                                   onclick="openModalWithMapel({{ $m->id }})"
                                   title="Tambah Soal">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                {{-- Import --}}
                                <a href="{{ route('admin.import-banksoal.index') }}"
                                   class="btn btn-ios btn-ios-sm"
                                   style="background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.2);"
                                   title="Import Soal">
                                    <i class="bi bi-file-earmark-arrow-up"></i>
                                </a>
                                {{-- Hapus Semua Soal Mapel --}}
                                @if($m->total_soal > 0)
                                <form action="{{ route('banksoal.bulk-destroy') }}" method="POST" onsubmit="return confirm('Yakin hapus semua {{ $m->total_soal }} soal {{ $m->nama_mapel }}?')" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="mapel_id" value="{{ $m->id }}">
                                    <input type="hidden" name="delete_all_mapel" value="1">
                                    <button type="submit"
                                       class="btn btn-ios btn-ios-sm"
                                       style="background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2);"
                                       title="Hapus Semua Soal">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-book"></i>
                                <h5>Belum ada mata pelajaran</h5>
                                <p>Tambahkan mata pelajaran terlebih dahulu</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary footer --}}
    <div style="margin-top: 14px; font-size: 13px; color: #94a3b8; font-weight: 500;">
        Menampilkan {{ $mapels->count() }} mata pelajaran •
        Total {{ $mapels->sum('total_soal') }} soal
    </div>
</div>

{{-- ============================================ --}}
{{-- MODAL TAMBAH SOAL --}}
{{-- ============================================ --}}
<div class="modal fade" id="modalTambahSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; padding: 20px 28px;">
                <h5 class="modal-title" style="color: white; font-weight: 800; font-size: 18px; letter-spacing: -0.02em;">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Soal Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('banksoal.store') }}" enctype="multipart/form-data" id="formTambahSoal">
                @csrf
                <div class="modal-body" style="padding: 28px;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label-ios">Mata Pelajaran <span style="color:#ef4444;">*</span></label>
                            <select name="mapel_id" class="form-select-ios w-100" required id="modalMapel">
                                <option value="">Pilih Mapel</option>
                                @foreach($mapels as $m)
                                <option value="{{ $m->id }}">{{ $m->kode_mapel }} — {{ $m->nama_mapel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Tipe Soal <span style="color:#ef4444;">*</span></label>
                            <select name="tipe_soal" class="form-select-ios w-100" id="modalTipeSoal" required onchange="toggleModalOptions()">
                                <option value="pg">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                                <option value="pg_kompleks">PG Kompleks</option>
                                <option value="menjodohkan">Menjodohkan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Tingkat Kesulitan <span style="color:#ef4444;">*</span></label>
                            <select name="tingkat_kesulitan" class="form-select-ios w-100" required>
                                <option value="mudah">Mudah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label-ios">Bobot Nilai <span style="color:#ef4444;">*</span></label>
                            <input type="number" name="bobot_nilai" class="form-control-ios w-100" value="1" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-ios">Kategori</label>
                            <input type="text" name="kategori" class="form-control-ios w-100" placeholder="Opsional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-ios">Tag</label>
                            <input type="text" name="tag" class="form-control-ios w-100" placeholder="Opsional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-ios">Gambar Soal</label>
                            <input type="file" name="gambar_soal" class="form-control-ios w-100" accept="image/*" style="font-size: 12px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-ios">Pertanyaan <span style="color:#ef4444;">*</span></label>
                        <textarea name="pertanyaan" class="form-control-ios w-100" rows="4" required placeholder="Tulis pertanyaan di sini..."></textarea>
                    </div>
                    <div id="modalPgOptions">
                        <label class="form-label-ios" style="margin-bottom: 12px;"><i class="bi bi-list-check me-1" style="color:#6366f1;"></i>Opsi Jawaban</label>
                        @foreach(['A','B','C','D','E'] as $i => $label)
                        <div class="d-flex align-items-center gap-3 mb-2" style="background:#f8fafc; padding:12px 14px; border-radius:12px; border:1px solid #e2e8f0;">
                            <div style="width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0;">{{ $label }}</div>
                            <input type="hidden" name="opsi_label[]" value="{{ $label }}">
                            <input type="text" name="opsi_isi[]" class="form-control-ios flex-grow-1" placeholder="Isi opsi {{ $label }}" style="padding:10px 14px; font-size:13px;">
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; white-space:nowrap; font-size:12px; font-weight:600; color:#64748b;">
                                <input type="checkbox" name="opsi_correct[]" value="{{ $i }}" style="width:16px; height:16px; accent-color:#22c55e;">
                                <span>Benar</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label class="form-label-ios">Pembahasan</label>
                        <textarea name="pembahasan" class="form-control-ios w-100" rows="3" placeholder="Penjelasan jawaban (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 28px; background:#fafbfc;">
                    <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Batal</button>
                    <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleModalOptions() {
    const tipe = document.getElementById('modalTipeSoal').value;
    document.getElementById('modalPgOptions').style.display = (tipe === 'pg' || tipe === 'pg_kompleks') ? 'block' : 'none';
}
toggleModalOptions();

function openModalWithMapel(mapelId) {
    document.getElementById('modalMapel').value = mapelId;
    new bootstrap.Modal(document.getElementById('modalTambahSoal')).show();
}

document.getElementById('modalTambahSoal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formTambahSoal').reset();
    toggleModalOptions();
});
</script>
@endpush
@endsection
