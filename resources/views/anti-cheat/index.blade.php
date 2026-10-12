@extends('layouts.app')

@section('title', 'Anti-Cheat Log')
@section('page-title', 'Anti-Cheat Log')
@section('page-subtitle', 'Riwayat pelanggaran selama ujian')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card danger">
            <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
            <div class="stat-value">{{ $totalViolations }}</div>
            <div class="stat-label">Total Pelanggaran</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value">{{ $todayViolations }}</div>
            <div class="stat-label">Hari Ini</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $uniqueStudents }}</div>
            <div class="stat-label">Siswa Terlibat</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card-ios mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.anti-cheat.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label-ios">Cari Siswa</label>
                    <input type="text" name="search" class="form-control-ios w-100" placeholder="Nama siswa..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label-ios">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control-ios w-100" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label-ios">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control-ios w-100" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-ios btn-ios-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Pembersihan Log -->
<div class="card-ios mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-trash3-fill me-2"></i>Bersihkan Log</span>
        <span class="badge-ios secondary" style="font-size: 12px;">Agar data tidak menumpuk</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.anti-cheat.clear') }}" id="clearLogForm"
            data-confirm-title="Bersihkan Log Pelanggaran"
            data-confirm-ok="Ya, Hapus"
            data-confirm="{{ $hasFilter
                ? 'Hapus log pelanggaran yang cocok dengan filter aktif? Tindakan ini tidak bisa dibatalkan.'
                : 'Hapus SELURUH log pelanggaran anti-cheat? Tindakan ini tidak bisa dibatalkan.' }}">
            @csrf
            @method('DELETE')

            {{-- Filter aktif ikut dikirim supaya pembersihan mengikuti data yang sedang tampil --}}
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" value="{{ request('date_to') }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label-ios">Data yang Dihapus</label>
                    <select name="older_than" id="olderThanSelect" class="form-control-ios w-100">
                        <option value="">{{ $hasFilter ? 'Semua yang cocok dengan filter aktif' : 'Semua log pelanggaran' }}</option>
                        @foreach($retentionOptions as $days)
                            <option value="{{ $days }}">Hanya yang lebih lama dari {{ $days }} hari</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-ios btn-ios-danger w-100">
                        <i class="bi bi-trash3-fill"></i> Bersihkan Sekarang
                    </button>
                </div>
                <div class="col-md-3">
                    <div style="font-size: 11.5px; color: var(--text-secondary); line-height: 1.6;">
                        @if($hasFilter)
                            <i class="bi bi-funnel-fill me-1"></i>Filter aktif — hanya data hasil filter yang dihapus.
                        @else
                            <i class="bi bi-info-circle me-1"></i>Pilih retensi hari untuk menyisakan data terbaru.
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Delete Floating Bar (hidden by default) --}}
<div id="bulkActionBar" style="display:none; position:sticky; top:12px; z-index:100; background:linear-gradient(135deg,#ef4444,#dc2626); color:white; border-radius:14px; padding:12px 20px; margin-bottom:14px; align-items:center; justify-content:space-between; gap:12px; box-shadow:0 8px 30px rgba(239,68,68,0.35);">
    <div style="display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px;">
        <i class="bi bi-check2-square" style="font-size:18px;"></i>
        <span id="bulkCountText">0 log dipilih</span>
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
<form id="bulkDeleteForm" method="POST" action="{{ route('admin.anti-cheat.bulk-destroy') }}" style="display:none;">
    @csrf
    @method('DELETE')
    <div id="bulkIdsContainer"></div>
</form>

<!-- Table -->
<div class="card-ios">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-shield-exclamation me-2"></i>Riwayat Pelanggaran</span>
        <span class="badge-ios danger">{{ $logs->total() }} record</span>
    </div>
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="empty-state">
                <i class="bi bi-shield-check"></i>
                <h5>Tidak Ada Pelanggaran</h5>
                <p>Belum ada pelanggaran anti-cheat yang tercatat.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table-ios" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAll" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;" title="Pilih Semua">
                            </th>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Siswa</th>
                            <th>Ujian</th>
                            <th>Kelas</th>
                            <th>Tipe Pelanggaran</th>
                            <th>IP Address</th>
                            <th style="width: 70px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $index => $log)
                        @php $logData = $log->data ?? []; @endphp
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="log-checkbox" value="{{ $log->id }}" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;" onchange="updateBulkBar()">
                            </td>
                            <td>{{ $logs->firstItem() + $index }}</td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px;">{{ $log->created_at->format('d/m/Y') }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $logData['siswa_nama'] ?? ($log->user->name ?? '-') }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $logData['siswa_nisn'] ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge-ios primary">{{ $logData['ujian_nama'] ?? '-' }}</span>
                            </td>
                            <td>{{ $logData['kelas'] ?? '-' }}</td>
                            <td>
                                @php
                                    $vType = $logData['violation_type'] ?? 'tab_switch';
                                    $vLabels = [
                                        'tab_switch' => ['Pindah Tab / Home', 'danger'],
                                        'copy_attempt' => ['Copy Text', 'warning'],
                                        'screenshot' => ['Screenshot', 'warning'],
                                        'devtools' => ['Developer Tools', 'danger'],
                                    ];
                                    $vLabel = $vLabels[$vType] ?? [$vType, 'secondary'];
                                @endphp
                                <span class="badge-ios {{ $vLabel[1] }}">
                                    <i class="bi bi-exclamation-circle-fill"></i> {{ $vLabel[0] }}
                                </span>
                            </td>
                            <td style="font-size: 12px; font-family: monospace;">{{ $log->ip_address }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.anti-cheat.destroy', $log) }}"
                                    data-confirm="Hapus log pelanggaran {{ $logData['siswa_nama'] ?? ($log->user->name ?? 'siswa ini') }} pada {{ $log->created_at->format('d/m/Y H:i') }}?"
                                    data-confirm-title="Hapus Log Pelanggaran"
                                    data-confirm-ok="Ya, Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ios btn-ios-sm btn-ios-danger" title="Hapus log ini">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if($logs->hasPages())
<div class="d-flex justify-content-center mt-4 pagination-ios">
    {{ $logs->links() }}
</div>
@endif
@endsection

@push('scripts')
<script>
function getSelectedLogIds() {
    return [...document.querySelectorAll('.log-checkbox:checked')].map(cb => cb.value);
}

function updateBulkBar() {
    const selected = getSelectedLogIds();
    const bar = document.getElementById('bulkActionBar');
    if (selected.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('bulkCountText').textContent = selected.length + ' log dipilih';
    } else {
        bar.style.display = 'none';
    }
    const all = document.querySelectorAll('.log-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (!selectAll) return;
    selectAll.indeterminate = selected.length > 0 && selected.length < all.length;
    selectAll.checked = selected.length === all.length && all.length > 0;
}

function clearSelection() {
    document.querySelectorAll('.log-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.checked = false;
    updateBulkBar();
}

const selectAllBox = document.getElementById('selectAll');
if (selectAllBox) {
    selectAllBox.addEventListener('change', function () {
        document.querySelectorAll('.log-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });
}

async function confirmBulkDelete() {
    const ids = getSelectedLogIds();
    if (ids.length === 0) return;

    const approved = await showConfirm(
        `Yakin hapus ${ids.length} log pelanggaran yang dipilih? Tindakan ini tidak bisa dibatalkan.`,
        { title: 'Hapus Log Pelanggaran', type: 'danger', okText: 'Ya, Hapus' }
    );
    if (!approved) return;

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

{{-- Teks konfirmasi mengikuti pilihan retensi --}}
const olderThanSelect = document.getElementById('olderThanSelect');
const clearLogForm = document.getElementById('clearLogForm');
if (olderThanSelect && clearLogForm) {
    const defaultConfirm = clearLogForm.dataset.confirm;
    olderThanSelect.addEventListener('change', function () {
        clearLogForm.dataset.confirm = this.value
            ? `Hapus log pelanggaran yang lebih lama dari ${this.value} hari? Tindakan ini tidak bisa dibatalkan.`
            : defaultConfirm;
        delete clearLogForm.dataset.confirmApproved;
    });
}
</script>
@endpush
