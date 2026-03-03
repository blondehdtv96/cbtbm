@extends('layouts.app')
@section('title', 'Soal — ' . $mapel->nama_mapel)
@section('page-title', $mapel->nama_mapel)
@section('page-subtitle', 'Kelola soal untuk ' . $mapel->kode_mapel)

@section('content')
<div class="fade-in">

    {{-- Breadcrumb --}}
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; font-size: 13px; color: #64748b;">
        <a href="{{ route('banksoal.index') }}" style="color: #2563eb; font-weight: 600; text-decoration: none;">
            <i class="bi bi-book-fill me-1"></i>Bank Soal
        </a>
        <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
        <span style="font-weight: 600; color: #0f172a;">{{ $mapel->kode_mapel }} — {{ $mapel->nama_mapel }}</span>
    </div>

    {{-- Stat summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div style="background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.08)); border: 1px solid rgba(99,102,241,0.15); border-radius: 14px; padding: 16px 20px;">
                <div style="font-size: 24px; font-weight: 800; color: #6366f1;">{{ $soals->total() }}</div>
                <div style="font-size: 12px; color: #64748b; font-weight: 500;">Total Soal</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="background: rgba(34,197,94,0.07); border: 1px solid rgba(34,197,94,0.15); border-radius: 14px; padding: 16px 20px;">
                <div style="font-size: 24px; font-weight: 800; color: #16a34a;">{{ $mapel->jurusan->nama_jurusan ?? 'Umum' }}</div>
                <div style="font-size: 12px; color: #64748b; font-weight: 500;">Jurusan</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                <input type="text" name="search" class="form-control-ios" placeholder="Cari soal..."
                    value="{{ request('search') }}" style="width: 200px; padding-left: 36px;">
            </div>
            <select name="tipe_soal" class="form-select-ios" style="width: 140px;" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="pg" {{ request('tipe_soal') == 'pg' ? 'selected' : '' }}>Pilihan Ganda</option>
                <option value="essay" {{ request('tipe_soal') == 'essay' ? 'selected' : '' }}>Essay</option>
                <option value="pg_kompleks" {{ request('tipe_soal') == 'pg_kompleks' ? 'selected' : '' }}>PG Kompleks</option>
            </select>
            <select name="tingkat_kesulitan" class="form-select-ios" style="width: 130px;" onchange="this.form.submit()">
                <option value="">Semua Level</option>
                <option value="mudah" {{ request('tingkat_kesulitan') == 'mudah' ? 'selected' : '' }}>Mudah</option>
                <option value="sedang" {{ request('tingkat_kesulitan') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                <option value="sulit" {{ request('tingkat_kesulitan') == 'sulit' ? 'selected' : '' }}>Sulit</option>
            </select>
            <button type="submit" class="btn btn-ios btn-ios-light"><i class="bi bi-search"></i></button>
        </form>
        <div class="d-flex gap-2">
            <a href="{{ route('banksoal.index') }}" class="btn btn-ios btn-ios-light">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <button type="button" class="btn btn-ios btn-ios-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSoal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Soal
            </button>
        </div>
    </div>

    {{-- Bulk Delete Floating Bar (hidden by default) --}}
    <div id="bulkActionBar" style="display:none; position:sticky; top:12px; z-index:100; background:linear-gradient(135deg,#ef4444,#dc2626); color:white; border-radius:14px; padding:12px 20px; margin-bottom:14px; box-shadow:0 8px 30px rgba(239,68,68,0.35); display:none; align-items:center; justify-content:space-between; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px;">
            <i class="bi bi-check2-square" style="font-size:18px;"></i>
            <span id="bulkCountText">0 soal dipilih</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="clearSelection()" style="background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3); color:white; padding:7px 16px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer;">
                <i class="bi bi-x-lg me-1"></i> Batal
            </button>
            <button type="button" onclick="confirmBulkDelete()" style="background:white; border:none; color:#dc2626; padding:7px 18px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <i class="bi bi-trash-fill me-1"></i> Hapus Terpilih
            </button>
        </div>
    </div>

    {{-- Hidden bulk delete form --}}
    <form id="bulkDeleteForm" method="POST" action="{{ route('banksoal.bulk-destroy') }}" style="display:none;">
        @csrf
        <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
        <div id="bulkIdsContainer"></div>
    </form>


    {{-- Table --}}
    <div class="card-ios">
        <div class="card-body p-0">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">
                            <input type="checkbox" id="selectAll" style="width:16px; height:16px; accent-color:#6366f1; cursor:pointer;" title="Pilih Semua">
                        </th>
                        <th style="width:40px; text-align:center;">No.</th>
                        <th>Pertanyaan</th>
                        <th>Tipe</th>
                        <th>Level</th>
                        <th style="text-align:center;">Bobot</th>
                        <th>Oleh</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($soals as $soal)
                    <tr id="row-{{ $soal->id }}">
                        <td style="text-align:center;">
                            <input type="checkbox" class="soal-checkbox" value="{{ $soal->id }}"
                                style="width:16px; height:16px; accent-color:#6366f1; cursor:pointer;"
                                onchange="updateBulkBar()">
                        </td>
                        <td style="text-align:center; color:#94a3b8; font-weight:600;">{{ $soals->firstItem() + $loop->index }}</td>
                        <td>
                            <div style="max-width:400px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500; color:#0f172a;">
                                {{ Str::limit(strip_tags($soal->pertanyaan), 90) }}
                            </div>
                            @if($soal->opsiJawabans->count() > 0)
                                <small style="color:#94a3b8; font-size:11px;">{{ $soal->opsiJawabans->count() }} opsi</small>
                            @endif
                        </td>
                        <td><span class="badge-ios {{ $soal->tipe_soal === 'essay' ? 'info' : 'purple' }}">{{ strtoupper(str_replace('_', ' ', $soal->tipe_soal)) }}</span></td>
                        <td><span class="badge-ios {{ $soal->tingkat_kesulitan == 'mudah' ? 'success' : ($soal->tingkat_kesulitan == 'sedang' ? 'warning' : 'danger') }}">{{ ucfirst($soal->tingkat_kesulitan) }}</span></td>
                        <td style="text-align:center; font-weight:700;">{{ $soal->bobot_nilai }}</td>
                        <td style="font-size:12px; color:#64748b;">{{ $soal->guru->nama ?? '-' }}</td>
                        <td><span class="badge-ios {{ $soal->status == 'aktif' ? 'success' : 'secondary' }}">{{ ucfirst($soal->status) }}</span></td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-ios btn-ios-sm btn-ios-light" onclick="showDetail({{ $soal->id }})" title="Detail"><i class="bi bi-eye"></i></button>
                                <a href="{{ route('banksoal.edit', $soal) }}" class="btn btn-ios btn-ios-sm btn-ios-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('banksoal.destroy', $soal) }}" method="POST" onsubmit="return confirm('Yakin hapus soal ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ios btn-ios-sm btn-ios-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-database"></i><h5>Belum ada soal</h5><p>Klik "Tambah Soal" untuk mulai menambahkan soal</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-3 pagination-ios">{{ $soals->withQueryString()->links() }}</div>
</div>

{{-- MODAL TAMBAH SOAL --}}
<div class="modal fade" id="modalTambahSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none; border-radius:20px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; padding:20px 28px;">
                <h5 class="modal-title" style="color:white; font-weight:800; font-size:18px;">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Soal — {{ $mapel->nama_mapel }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('banksoal.store') }}" enctype="multipart/form-data" id="formTambahSoal">
                @csrf
                <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
                <input type="hidden" name="_redirect_mapel" value="{{ $mapel->id }}">
                <div class="modal-body" style="padding:28px;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-ios">Tipe Soal <span style="color:#ef4444;">*</span></label>
                            <select name="tipe_soal" class="form-select-ios w-100" id="modalTipeSoal" required onchange="toggleModalOptions()">
                                <option value="pg">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                                <option value="pg_kompleks">PG Kompleks</option>
                                <option value="menjodohkan">Menjodohkan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-ios">Tingkat Kesulitan <span style="color:#ef4444;">*</span></label>
                            <select name="tingkat_kesulitan" class="form-select-ios w-100" required>
                                <option value="mudah">Mudah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label-ios">Bobot Nilai <span style="color:#ef4444;">*</span></label>
                            <input type="number" name="bobot_nilai" class="form-control-ios w-100" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Kategori</label>
                            <input type="text" name="kategori" class="form-control-ios w-100" placeholder="Opsional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-ios">Tag</label>
                            <input type="text" name="tag" class="form-control-ios w-100" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-ios">Pertanyaan <span style="color:#ef4444;">*</span></label>
                        <textarea name="pertanyaan" class="form-control-ios w-100" rows="4" required placeholder="Tulis pertanyaan di sini..."></textarea>
                    </div>
                    <div id="modalPgOptions">
                        <label class="form-label-ios" style="margin-bottom:12px;"><i class="bi bi-list-check me-1" style="color:#6366f1;"></i>Opsi Jawaban</label>
                        @foreach(['A','B','C','D','E'] as $i => $label)
                        <div class="d-flex align-items-center gap-3 mb-2" style="background:#f8fafc; padding:12px 14px; border-radius:12px; border:1px solid #e2e8f0;">
                            <div style="width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0;">{{ $label }}</div>
                            <input type="hidden" name="opsi_label[]" value="{{ $label }}">
                            <input type="text" name="opsi_isi[]" class="form-control-ios flex-grow-1" placeholder="Isi opsi {{ $label }}" style="padding:10px 14px; font-size:13px;">
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; white-space:nowrap; font-size:12px; font-weight:600; color:#64748b;">
                                <input type="checkbox" name="opsi_correct[]" value="{{ $i }}" style="width:16px; height:16px; accent-color:#22c55e;"> Benar
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label class="form-label-ios">Pembahasan</label>
                        <textarea name="pembahasan" class="form-control-ios w-100" rows="3" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 28px; background:#fafbfc;">
                    <button type="button" class="btn btn-ios btn-ios-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-ios btn-ios-primary"><i class="bi bi-check-lg me-1"></i> Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL SOAL --}}
<div class="modal fade" id="modalDetailSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none; border-radius:20px; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#2563eb,#6366f1); border:none; padding:20px 28px;">
                <h5 class="modal-title" style="color:white; font-weight:800; font-size:18px;"><i class="bi bi-eye-fill me-2"></i>Detail Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:28px;" id="detailSoalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
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

// ── Bulk Select / Delete ──────────────────────────────────
function getSelected() {
    return [...document.querySelectorAll('.soal-checkbox:checked')].map(cb => cb.value);
}

function updateBulkBar() {
    const selected = getSelected();
    const bar = document.getElementById('bulkActionBar');
    if (selected.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('bulkCountText').textContent = selected.length + ' soal dipilih';
    } else {
        bar.style.display = 'none';
    }
    // Update select-all state
    const all = document.querySelectorAll('.soal-checkbox');
    document.getElementById('selectAll').indeterminate = selected.length > 0 && selected.length < all.length;
    document.getElementById('selectAll').checked = selected.length === all.length && all.length > 0;
}

document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.soal-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});

function clearSelection() {
    document.querySelectorAll('.soal-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkBar();
}

function confirmBulkDelete() {
    const ids = getSelected();
    if (ids.length === 0) return;
    if (!confirm(`Yakin hapus ${ids.length} soal yang dipilih? Tindakan ini tidak bisa dibatalkan.`)) return;

    const container = document.getElementById('bulkIdsContainer');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });
    document.getElementById('bulkDeleteForm').submit();
}

const soalData = <?php
    $soalMap = [];
    foreach ($soals as $s) {
        $soalMap[$s->id] = [
            'id' => $s->id,
            'pertanyaan' => $s->pertanyaan,
            'tipe_soal' => $s->tipe_soal,
            'tingkat_kesulitan' => $s->tingkat_kesulitan,
            'bobot_nilai' => $s->bobot_nilai,
            'guru' => $s->guru->nama ?? '-',
            'pembahasan' => $s->pembahasan,
            'opsi' => $s->opsiJawabans->map(fn($o) => [
                'label' => $o->opsi_label, 'isi' => $o->isi_opsi, 'correct' => $o->is_correct,
            ])->values()->toArray(),
        ];
    }
    echo json_encode($soalMap);
?>;

function showDetail(id) {
    const soal = soalData[id]; if (!soal) return;
    const lc = soal.tingkat_kesulitan === 'mudah' ? 'success' : (soal.tingkat_kesulitan === 'sedang' ? 'warning' : 'danger');
    let opsiHtml = '';
    if (soal.opsi && soal.opsi.length > 0) {
        opsiHtml = '<div style="margin-top:16px;"><div style="font-weight:700; font-size:13px; color:#334155; margin-bottom:10px;"><i class="bi bi-list-check me-1" style="color:#6366f1;"></i>Opsi Jawaban:</div>';
        soal.opsi.forEach(o => {
            const c = o.correct;
            opsiHtml += `<div style="display:flex; align-items:center; gap:12px; padding:10px 14px; margin-bottom:6px; border-radius:10px; background:${c?'rgba(34,197,94,0.08)':'#f8fafc'}; border:1px solid ${c?'rgba(34,197,94,0.2)':'#e2e8f0'};">
                <div style="width:30px; height:30px; border-radius:8px; background:${c?'linear-gradient(135deg,#22c55e,#10b981)':'#e2e8f0'}; color:${c?'white':'#64748b'}; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">${o.label}</div>
                <div style="font-weight:${c?'600':'400'}; color:${c?'#166534':'#334155'}; font-size:14px;">${o.isi}</div>
                ${c?'<span class="badge-ios success" style="font-size:10px; margin-left:auto;"><i class="bi bi-check-lg"></i> Benar</span>':''}
            </div>`;
        });
        opsiHtml += '</div>';
    }
    const pb = soal.pembahasan ? `<div style="margin-top:16px; background:rgba(37,99,235,0.04); border:1px solid rgba(37,99,235,0.1); border-radius:12px; padding:14px 16px;"><div style="font-weight:700; font-size:13px; color:#2563eb; margin-bottom:6px;"><i class="bi bi-lightbulb-fill me-1"></i>Pembahasan:</div><div style="font-size:14px; color:#334155; line-height:1.7;">${soal.pembahasan}</div></div>` : '';
    document.getElementById('detailSoalBody').innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-4"><div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase;">Tipe</div><div style="margin-top:4px;"><span class="badge-ios purple">${soal.tipe_soal.toUpperCase()}</span></div></div>
            <div class="col-4"><div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase;">Tingkat</div><div style="margin-top:4px;"><span class="badge-ios ${lc}">${soal.tingkat_kesulitan}</span></div></div>
            <div class="col-4"><div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase;">Bobot</div><div style="font-weight:800; color:#0f172a; margin-top:4px;">${soal.bobot_nilai}</div></div>
        </div>
        <hr style="border-color:#f1f5f9;">
        <div style="font-weight:700; font-size:13px; color:#334155; margin-bottom:8px;">Pertanyaan:</div>
        <div style="font-size:15px; color:#0f172a; line-height:1.7; background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">${soal.pertanyaan}</div>
        ${opsiHtml}${pb}
        <div style="margin-top:14px; font-size:12px; color:#94a3b8;"><i class="bi bi-person-fill me-1"></i>${soal.guru}</div>`;
    new bootstrap.Modal(document.getElementById('modalDetailSoal')).show();
}
</script>
@endpush
@endsection
