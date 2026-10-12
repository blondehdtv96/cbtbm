{{--
    Overlay loading global.

    Tujuan: setiap aksi (submit form, pindah halaman, hapus data, filter, paginasi)
    memberi umpan balik visual DAN mencegah klik/submit ganda yang membuat server
    bekerja dua kali — penyebab paling umum aplikasi terasa berat saat dipakai
    banyak user bersamaan.

    API manual:
        AppLoading.show('Menyimpan data...')
        AppLoading.hide()
        await AppLoading.during(fetch(url), 'Mengambil data...')

    Opt-out per elemen: tambahkan atribut data-no-loading
    Teks khusus per elemen: data-loading="Teks saya..."
--}}

<style>
    .app-loading-overlay {
        position: fixed; inset: 0; z-index: 12000; display: none;
        align-items: center; justify-content: center; padding: 20px;
        background: rgba(15, 23, 42, .55); backdrop-filter: blur(4px);
        opacity: 0; transition: opacity .18s ease-out;
    }
    .app-loading-overlay.is-visible { display: flex; opacity: 1; }
    .app-loading-card {
        display: flex; align-items: center; gap: 16px;
        min-width: 210px; max-width: min(100%, 380px); padding: 20px 24px;
        border: 1px solid rgba(255, 255, 255, .6); border-radius: 20px;
        background: #fff; box-shadow: 0 24px 70px rgba(15, 23, 42, .3);
        transform: translateY(10px) scale(.96); transition: transform .2s cubic-bezier(.2, .8, .2, 1);
    }
    .app-loading-overlay.is-visible .app-loading-card { transform: none; }
    .app-loading-spinner {
        width: 42px; height: 42px; flex: 0 0 auto; border-radius: 50%;
        border: 3.5px solid #e2e8f0; border-top-color: #2563eb;
        animation: appLoadingSpin .7s linear infinite;
    }
    .app-loading-text { min-width: 0; }
    .app-loading-title { color: #0f172a; font: 800 14.5px/1.35 Inter, sans-serif; overflow-wrap: anywhere; }
    .app-loading-hint { margin-top: 3px; color: #94a3b8; font: 500 12px/1.4 Inter, sans-serif; }
    .app-loading-bar {
        position: fixed; top: 0; left: 0; z-index: 12001; height: 3px; width: 0;
        background: linear-gradient(90deg, #2563eb, #6366f1);
        box-shadow: 0 0 10px rgba(37, 99, 235, .6);
        opacity: 0; transition: width .25s ease-out, opacity .2s;
    }
    .app-loading-bar.is-visible { opacity: 1; }

    /* Spinner kecil di dalam tombol yang sedang diproses */
    .app-btn-spinner {
        display: inline-block; width: 13px; height: 13px; margin-right: 7px;
        vertical-align: -2px; border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, .45); border-top-color: #fff;
        animation: appLoadingSpin .7s linear infinite;
    }
    .btn-ios-light .app-btn-spinner { border-color: rgba(71, 85, 105, .25); border-top-color: #475569; }
    [data-loading-busy] { pointer-events: none; opacity: .72; }

    @keyframes appLoadingSpin { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        .app-loading-overlay, .app-loading-card { transition: none; }
        .app-loading-spinner, .app-btn-spinner { animation-duration: 1.4s; }
    }
    @media (max-width: 480px) {
        .app-loading-card { padding: 16px 18px; gap: 13px; }
        .app-loading-spinner { width: 36px; height: 36px; }
    }
</style>

<div class="app-loading-bar" id="appLoadingBar"></div>
<div class="app-loading-overlay" id="appLoadingOverlay" role="alert" aria-live="assertive" aria-hidden="true">
    <div class="app-loading-card">
        <div class="app-loading-spinner"></div>
        <div class="app-loading-text">
            <div class="app-loading-title" id="appLoadingTitle">Memproses...</div>
            <div class="app-loading-hint">Mohon tunggu, jangan tutup halaman ini.</div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.AppLoading) return;

    const overlay = document.getElementById('appLoadingOverlay');
    const bar = document.getElementById('appLoadingBar');
    const title = document.getElementById('appLoadingTitle');

    // Tampilkan setelah jeda singkat supaya aksi yang super cepat tidak "berkedip".
    const SHOW_DELAY = 160;
    // Jaring pengaman: aksi yang tidak memicu pindah halaman (misal unduh file)
    // tidak boleh membuat overlay menggantung selamanya.
    const SAFETY_TIMEOUT = 20000;

    let showTimer = null;
    let safetyTimer = null;
    let barTimer = null;
    let visible = false;
    let depth = 0;

    function paintBar() {
        bar.classList.add('is-visible');
        bar.style.width = '35%';
        clearTimeout(barTimer);
        barTimer = setTimeout(function () { bar.style.width = '72%'; }, 450);
    }

    function reveal(text) {
        visible = true;
        title.textContent = text;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
        paintBar();
    }

    function show(text, options) {
        const settings = options || {};
        const message = text || 'Memproses...';
        depth++;

        clearTimeout(showTimer);
        clearTimeout(safetyTimer);

        if (visible) {
            title.textContent = message;
        } else if (settings.immediate) {
            reveal(message);
        } else {
            showTimer = setTimeout(function () { reveal(message); }, SHOW_DELAY);
        }

        safetyTimer = setTimeout(forceHide, settings.timeout || SAFETY_TIMEOUT);
    }

    function forceHide() {
        depth = 0;
        visible = false;
        clearTimeout(showTimer);
        clearTimeout(safetyTimer);
        clearTimeout(barTimer);
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        bar.style.width = '100%';
        setTimeout(function () {
            if (!visible) {
                bar.classList.remove('is-visible');
                bar.style.width = '0';
            }
        }, 220);
        document.querySelectorAll('[data-loading-busy]').forEach(releaseButton);
    }

    function hide() {
        depth = Math.max(0, depth - 1);
        if (depth === 0) forceHide();
    }

    function during(task, text) {
        show(text);
        const promise = typeof task === 'function' ? Promise.resolve().then(task) : Promise.resolve(task);
        return promise.finally(hide);
    }

    function lockButton(button) {
        if (!button || button.hasAttribute('data-loading-busy')) return;
        button.setAttribute('data-loading-busy', '1');
        button.dataset.loadingHtml = button.innerHTML;
        if (button.tagName === 'BUTTON' || button.tagName === 'INPUT') {
            // Dinonaktifkan di tick berikutnya supaya name/value submitter tetap terkirim.
            setTimeout(function () { button.disabled = true; }, 0);
        }
        if (button.tagName !== 'INPUT') {
            // Tombol ikon tanpa teks tetap ikon berputar saja, tidak melebar.
            const hasText = (button.dataset.loadingHtml || '').replace(/<[^>]*>/g, '').trim().length > 0;
            const label = typeof button.dataset.loadingLabel === 'string'
                ? button.dataset.loadingLabel
                : (hasText ? 'Memproses...' : '');
            button.innerHTML = '<span class="app-btn-spinner"' + (label ? '' : ' style="margin-right:0"') + '></span>' + label;
        }
    }

    function releaseButton(button) {
        if (!button || !button.hasAttribute('data-loading-busy')) return;
        button.removeAttribute('data-loading-busy');
        button.disabled = false;
        if (typeof button.dataset.loadingHtml === 'string' && button.tagName !== 'INPUT') {
            button.innerHTML = button.dataset.loadingHtml;
        }
        delete button.dataset.loadingHtml;
    }

    function optedOut(element) {
        return !!(element && element.closest('[data-no-loading]'));
    }

    function formMethod(form) {
        const override = form.querySelector('input[name="_method"]');
        return String((override && override.value) || form.method || 'get').toUpperCase();
    }

    function formMessage(form) {
        if (form.dataset.loading) return form.dataset.loading;
        const action = (form.getAttribute('action') || '') + ' ' + (form.id || '');
        if (/logout/i.test(action)) return 'Keluar dari aplikasi...';
        switch (formMethod(form)) {
            case 'DELETE':
                return 'Menghapus data...';
            case 'GET':
                return 'Memuat data...';
            default:
                return /search|filter|preview|import|upload/i.test(action) ? 'Memproses data...' : 'Menyimpan data...';
        }
    }

    // Unduhan/cetak tidak memicu pindah halaman: cukup tampilkan sebentar.
    const FILE_PATTERN = /(download|export|template|cetak|print|credential|backup)/i;

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || optedOut(form)) return;

        const isFileAction = FILE_PATTERN.test(form.getAttribute('action') || '');
        show(formMessage(form), isFileAction ? { timeout: 3500 } : undefined);
        lockButton(event.submitter || form.querySelector('[type="submit"]'));
    });

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || optedOut(link)) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;

        const href = link.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel|sms):/i.test(href)) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        // Link yang hanya memindahkan posisi di halaman yang sama.
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

        if (FILE_PATTERN.test(url.pathname)) {
            show(link.dataset.loading || 'Menyiapkan file...', { timeout: 4000 });
            return;
        }

        show(link.dataset.loading || 'Memuat halaman...');
    });

    // form.submit() programatik tidak memicu event 'submit' — ikut dibungkus
    // supaya aksi seperti bulk delete tetap menampilkan loading.
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (!optedOut(this)) show(formMessage(this));
        return nativeSubmit.apply(this, arguments);
    };

    // Bersihkan saat halaman kembali dari cache browser (tombol back).
    window.addEventListener('pageshow', function (event) { if (event.persisted) forceHide(); });
    window.addEventListener('popstate', forceHide);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && visible) forceHide();
    });

    window.AppLoading = { show: show, hide: hide, forceHide: forceHide, during: during, isVisible: function () { return visible; } };
})();
</script>
