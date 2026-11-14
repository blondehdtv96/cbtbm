/**
 * Filter cepat untuk tabel (client-side).
 *
 * Otomatis aktif pada setiap <table class="table-ios"> di .content-area, kecuali:
 *  - tabel bertanda data-no-filter
 *  - tabel di dalam modal
 *  - halaman yang memakai paginasi server atau sudah punya form pencarian GET
 *    (filter di sana harus lewat server, supaya tidak hanya menyaring satu halaman)
 *
 * Paksa aktif dengan data-filter="on" pada <table>.
 * Toolbar muncul bila tabel punya minimal MIN_ROWS baris data, dan ikut
 * diperbarui saat baris tabel diganti oleh JavaScript halaman.
 */
(function () {
    'use strict';

    var MIN_ROWS = 4;
    var MAX_SELECTS = 3;
    var SKIP_HEADERS = ['', '#', 'no', 'no.', 'aksi', 'action', 'opsi'];
    var EMPTY = '__tf_empty__';

    function norm(text) {
        return (text || '').replace(/\s+/g, ' ').trim();
    }

    function headers(table) {
        var row = table.tHead ? table.tHead.rows[table.tHead.rows.length - 1] : null;
        if (!row) return [];
        return Array.prototype.map.call(row.cells, function (c) { return norm(c.textContent); });
    }

    // Baris data = bukan baris placeholder "Tidak ada data" (satu sel dengan colspan).
    function dataRows(table) {
        var rows = [];
        Array.prototype.forEach.call(table.tBodies, function (tb) {
            Array.prototype.forEach.call(tb.rows, function (tr) {
                if (tr.cells.length === 1 && tr.cells[0].colSpan > 1) return;
                rows.push(tr);
            });
        });
        return rows;
    }

    function cellText(tr, idx) {
        var cell = tr.cells[idx];
        return cell ? norm(cell.textContent) : '';
    }

    function pageUsesServerFilter(root) {
        if (root.querySelector('.pagination, .pagination-ios nav')) return true;
        return !!root.querySelector('form[method="GET"] input[name="search"], form[method="get"] input[name="search"]');
    }

    function placementAnchor(table) {
        var body = table.closest('.card-body');
        var node = table;
        if (body) {
            while (node.parentElement && node.parentElement !== body) node = node.parentElement;
            return node;
        }
        if (table.parentElement && table.parentElement.classList.contains('table-responsive')) {
            return table.parentElement;
        }
        return table;
    }

    function setup(table) {
        if (table.dataset.filterReady) return;
        table.dataset.filterReady = '1';

        var bar = document.createElement('div');
        bar.className = 'table-filter-bar';
        bar.hidden = true;
        bar.innerHTML =
            '<div class="tf-search">' +
                '<i class="bi bi-search"></i>' +
                '<input type="search" class="form-control-ios" placeholder="Cari di tabel...">' +
            '</div>' +
            '<select class="form-select-ios tf-column" title="Cari di kolom"></select>' +
            '<span class="tf-selects"></span>' +
            '<button type="button" class="btn btn-ios btn-ios-light tf-reset" title="Reset filter" hidden><i class="bi bi-x-lg"></i></button>' +
            '<span class="tf-count"></span>';

        var anchor = placementAnchor(table);
        anchor.parentNode.insertBefore(bar, anchor);
        if (table.closest('.card-body.p-0')) bar.classList.add('tf-in-card');

        var input = bar.querySelector('input');
        var columnSel = bar.querySelector('.tf-column');
        var selectsWrap = bar.querySelector('.tf-selects');
        var resetBtn = bar.querySelector('.tf-reset');
        var count = bar.querySelector('.tf-count');
        var catSelects = [];

        function buildControls() {
            var heads = headers(table);
            var rows = dataRows(table);
            bar.hidden = rows.length < MIN_ROWS;

            // Pilihan kolom untuk pencarian teks.
            var prevCol = columnSel.value;
            columnSel.innerHTML = '<option value="">Semua kolom</option>';
            heads.forEach(function (h, i) {
                if (SKIP_HEADERS.indexOf(h.toLowerCase()) !== -1) return;
                var o = document.createElement('option');
                o.value = String(i);
                o.textContent = h;
                columnSel.appendChild(o);
            });
            columnSel.value = prevCol;
            if (columnSel.value !== prevCol) columnSel.value = '';

            // Dropdown untuk kolom kategori (nilai sedikit & pendek, mis. Status, Kelas).
            var prev = {};
            catSelects.forEach(function (s) { prev[s.dataset.col] = s.value; });
            selectsWrap.innerHTML = '';
            catSelects = [];

            heads.forEach(function (h, i) {
                if (catSelects.length >= MAX_SELECTS) return;
                if (SKIP_HEADERS.indexOf(h.toLowerCase()) !== -1) return;
                var seen = {};
                var values = [];
                var ok = rows.every(function (tr) {
                    var v = cellText(tr, i);
                    if (v.length > 30) return false;
                    if (!seen[v]) { seen[v] = true; values.push(v); }
                    return values.length <= 8;
                });
                if (!ok || values.length < 2 || values.length >= rows.length) return;

                values.sort(function (a, b) { return a.localeCompare(b, 'id', { numeric: true }); });
                var sel = document.createElement('select');
                sel.className = 'form-select-ios tf-cat';
                sel.dataset.col = String(i);
                sel.title = 'Filter ' + h;
                sel.innerHTML = '<option value="">Semua ' + escapeHtml(h) + '</option>';
                values.forEach(function (v) {
                    var o = document.createElement('option');
                    o.value = v === '' ? EMPTY : v;
                    o.textContent = v === '' ? '(kosong)' : v;
                    sel.appendChild(o);
                });
                var keep = prev[sel.dataset.col];
                if (keep !== undefined && values.indexOf(keep === EMPTY ? '' : keep) !== -1) {
                    sel.value = keep;
                }
                sel.addEventListener('change', apply);
                selectsWrap.appendChild(sel);
                catSelects.push(sel);
            });

            apply();
        }

        function apply() {
            var q = norm(input.value).toLowerCase();
            var col = columnSel.value === '' ? null : parseInt(columnSel.value, 10);
            var rows = dataRows(table);
            var shown = 0;

            rows.forEach(function (tr) {
                var match = true;
                if (q) {
                    var hay = col === null ? norm(tr.textContent) : cellText(tr, col);
                    match = hay.toLowerCase().indexOf(q) !== -1;
                }
                for (var k = 0; match && k < catSelects.length; k++) {
                    var s = catSelects[k];
                    if (s.value === '') continue;
                    var want = s.value === EMPTY ? '' : s.value;
                    if (cellText(tr, parseInt(s.dataset.col, 10)) !== want) match = false;
                }
                tr.classList.toggle('tf-hidden', !match);
                if (match) shown++;
            });

            var active = !!q || catSelects.some(function (s) { return s.value !== ''; });
            resetBtn.hidden = !active;
            count.textContent = active
                ? (shown === 0 ? 'Tidak ada data yang cocok' : shown + ' dari ' + rows.length + ' baris')
                : rows.length + ' baris';
            count.classList.toggle('tf-none', active && shown === 0);
        }

        var timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(apply, 120);
        });
        columnSel.addEventListener('change', apply);
        resetBtn.addEventListener('click', function () {
            input.value = '';
            columnSel.value = '';
            catSelects.forEach(function (s) { s.value = ''; });
            apply();
            input.focus();
        });

        // Tabel yang barisnya diganti JS (monitoring, import, dll.) ikut diperbarui.
        var rebuildTimer;
        new MutationObserver(function () {
            clearTimeout(rebuildTimer);
            rebuildTimer = setTimeout(buildControls, 200);
        }).observe(table, { childList: true, subtree: true, characterData: true });

        buildControls();
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function init() {
        var root = document.querySelector('.content-area');
        if (!root) return;
        var serverFiltered = pageUsesServerFilter(root);

        root.querySelectorAll('table.table-ios').forEach(function (table) {
            if (table.dataset.noFilter !== undefined) return;
            if (table.closest('.modal')) return;
            if (serverFiltered && table.dataset.filter !== 'on') return;
            setup(table);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
