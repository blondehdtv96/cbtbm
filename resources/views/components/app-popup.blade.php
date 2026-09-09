@props(['flash' => true])

@php
    $initialPopups = [];

    if ($flash) {
        if ($errors->any()) {
            $initialPopups[] = [
                'message' => "• ".implode("\n• ", $errors->all()),
                'title' => 'Periksa Kembali Data',
                'type' => 'danger',
                'okText' => 'Perbaiki',
            ];
        }

        foreach (['error' => 'danger', 'warning' => 'warning', 'success' => 'success', 'info' => 'info', 'status' => 'success'] as $key => $type) {
            if (session()->has($key)) {
                $initialPopups[] = [
                    'message' => session($key),
                    'title' => match ($type) {
                        'success' => 'Berhasil',
                        'danger' => 'Terjadi Kesalahan',
                        'warning' => 'Peringatan',
                        default => 'Informasi',
                    },
                    'type' => $type,
                ];
            }
        }
    }
@endphp

<style>
    .app-popup-overlay {
        display: none; position: fixed; inset: 0; z-index: 11000; align-items: center; justify-content: center;
        padding: 20px; background: rgba(15, 23, 42, .64); backdrop-filter: blur(4px);
    }
    .app-popup-overlay.is-visible { display: flex; animation: appPopupFade .16s ease-out; }
    .app-popup-dialog {
        width: min(100%, 420px); padding: 28px 24px 22px; border: 1px solid rgba(255,255,255,.65);
        border-radius: 22px; background: #fff; box-shadow: 0 24px 70px rgba(15,23,42,.28);
        text-align: center; animation: appPopupIn .2s cubic-bezier(.2,.8,.2,1);
    }
    .app-popup-icon {
        display: flex; align-items: center; justify-content: center; width: 62px; height: 62px;
        margin: 0 auto 16px; border-radius: 18px; font-size: 29px;
    }
    .app-popup-dialog.type-info .app-popup-icon { color: #2563eb; background: #eff6ff; }
    .app-popup-dialog.type-success .app-popup-icon { color: #16a34a; background: #f0fdf4; }
    .app-popup-dialog.type-warning .app-popup-icon { color: #d97706; background: #fffbeb; }
    .app-popup-dialog.type-danger .app-popup-icon { color: #dc2626; background: #fef2f2; }
    .app-popup-title { margin: 0 0 8px; color: #0f172a; font: 800 18px/1.3 Inter, sans-serif; }
    .app-popup-message {
        margin: 0 0 22px; color: #64748b; font: 500 13.5px/1.65 Inter, sans-serif;
        white-space: pre-line; overflow-wrap: anywhere;
    }
    .app-popup-actions { display: flex; gap: 10px; }
    .app-popup-actions button {
        flex: 1; min-height: 44px; padding: 11px 16px; border: 0; border-radius: 12px;
        font: 700 13px Inter, sans-serif; cursor: pointer; transition: transform .12s, filter .15s;
    }
    .app-popup-actions button:active { transform: scale(.97); }
    .app-popup-cancel { color: #475569; background: #f1f5f9; }
    .app-popup-ok { color: #fff; background: #2563eb; }
    .app-popup-dialog.type-success .app-popup-ok { background: #16a34a; }
    .app-popup-dialog.type-warning .app-popup-ok { background: #d97706; }
    .app-popup-dialog.type-danger .app-popup-ok { background: #dc2626; }
    @keyframes appPopupFade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes appPopupIn { from { opacity: 0; transform: translateY(12px) scale(.94); } to { opacity: 1; transform: none; } }
    @media (max-width: 480px) {
        .app-popup-overlay { padding: 14px; align-items: flex-end; }
        .app-popup-dialog { padding: 24px 18px 18px; border-radius: 22px 22px 16px 16px; }
    }
</style>

<div class="app-popup-overlay" id="appPopupOverlay" role="presentation" aria-hidden="true">
    <div class="app-popup-dialog type-info" id="appPopupDialog" role="dialog" aria-modal="true" aria-labelledby="appPopupTitle" aria-describedby="appPopupMessage">
        <div class="app-popup-icon" id="appPopupIcon"><i class="bi bi-info-circle-fill"></i></div>
        <h2 class="app-popup-title" id="appPopupTitle">Pemberitahuan</h2>
        <div class="app-popup-message" id="appPopupMessage"></div>
        <div class="app-popup-actions">
            <button type="button" class="app-popup-cancel" id="appPopupCancel">Batal</button>
            <button type="button" class="app-popup-ok" id="appPopupOk">OK</button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.AppPopup) return;

    const overlay = document.getElementById('appPopupOverlay');
    const dialog = document.getElementById('appPopupDialog');
    const icon = document.getElementById('appPopupIcon');
    const title = document.getElementById('appPopupTitle');
    const message = document.getElementById('appPopupMessage');
    const okButton = document.getElementById('appPopupOk');
    const cancelButton = document.getElementById('appPopupCancel');
    const icons = {
        info: 'bi-info-circle-fill', success: 'bi-check-circle-fill',
        warning: 'bi-exclamation-triangle-fill', danger: 'bi-shield-x',
    };

    let queue = Promise.resolve();
    let active = false;
    let visibilityGuardUntil = 0;
    let previousFocus = null;

    function run(options) {
        const settings = Object.assign({
            title: 'Pemberitahuan', type: 'info', okText: 'OK', cancelText: 'Batal', showCancel: false,
        }, options || {});

        return new Promise(resolve => {
            active = true;
            previousFocus = document.activeElement;
            dialog.className = `app-popup-dialog type-${icons[settings.type] ? settings.type : 'info'}`;
            icon.innerHTML = `<i class="bi ${icons[settings.type] || icons.info}"></i>`;
            title.textContent = settings.title;
            message.textContent = String(settings.message ?? '');
            okButton.textContent = settings.okText;
            cancelButton.textContent = settings.cancelText;
            cancelButton.hidden = !settings.showCancel;
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
            document.dispatchEvent(new CustomEvent('app-popup:opened'));
            (settings.showCancel ? cancelButton : okButton).focus({ preventScroll: true });

            function finish(result) {
                overlay.classList.remove('is-visible');
                overlay.setAttribute('aria-hidden', 'true');
                okButton.removeEventListener('click', onOk);
                cancelButton.removeEventListener('click', onCancel);
                document.removeEventListener('keydown', onKeydown);
                active = false;
                visibilityGuardUntil = Date.now() + 600;
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    previousFocus.focus({ preventScroll: true });
                }
                document.dispatchEvent(new CustomEvent('app-popup:closed', { detail: { result } }));
                resolve(result);
            }
            function onOk() { finish(true); }
            function onCancel() { finish(false); }
            function onKeydown(event) {
                if (event.key === 'Enter') { event.preventDefault(); onOk(); }
                if (event.key === 'Escape' && settings.showCancel) { event.preventDefault(); onCancel(); }
                if (event.key === 'Tab') {
                    const focusable = settings.showCancel ? [cancelButton, okButton] : [okButton];
                    const current = focusable.indexOf(document.activeElement);
                    event.preventDefault();
                    focusable[(current + (event.shiftKey ? -1 : 1) + focusable.length) % focusable.length].focus();
                }
            }

            okButton.addEventListener('click', onOk, { once: true });
            cancelButton.addEventListener('click', onCancel, { once: true });
            document.addEventListener('keydown', onKeydown);
        });
    }

    function open(options) {
        const task = () => run(options);
        const result = queue.then(task, task);
        queue = result.catch(() => {});
        return result;
    }

    const api = {
        alert(message, options = {}) {
            return open(Object.assign({}, options, { message, showCancel: false }));
        },
        confirm(message, options = {}) {
            return open(Object.assign({ title: 'Konfirmasi', okText: 'Ya, Lanjutkan' }, options, { message, showCancel: true }));
        },
        isOpen() { return active; },
        isBlockingVisibility() { return active || Date.now() < visibilityGuardUntil; },
    };

    window.AppPopup = api;
    window.showAlert = (text, options = {}) => api.alert(text, options);
    window.showConfirm = (text, options = {}) => api.confirm(text, options);
    window.alert = text => {
        const value = String(text);
        const isSuccess = /berhasil|tersalin|selesai|✅/i.test(value);
        const isDanger = /gagal|kesalahan|error/i.test(value);
        api.alert(value, {
            type: isSuccess ? 'success' : (isDanger ? 'danger' : 'warning'),
            title: isSuccess ? 'Berhasil' : (isDanger ? 'Terjadi Kesalahan' : 'Peringatan'),
        });
    };

    function extractInlineConfirmation(handler) {
        if (!handler) return null;
        const match = handler.trim().match(/^return\s+confirm\((['"`])([\s\S]*)\1\)\s*;?$/);
        return match ? match[2] : null;
    }

    function migrateInlineConfirmations() {
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            const text = extractInlineConfirmation(form.getAttribute('onsubmit'));
            if (text !== null) {
                form.dataset.confirm = text;
                form.removeAttribute('onsubmit');
            }
        });
        document.querySelectorAll('[onclick]').forEach(element => {
            const text = extractInlineConfirmation(element.getAttribute('onclick'));
            if (text !== null) {
                element.dataset.confirmClick = text;
                element.removeAttribute('onclick');
            }
        });
    }

    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-confirm]');
        if (!form || form.dataset.confirmApproved === '1') return;
        event.preventDefault();
        const approved = await api.confirm(form.dataset.confirm, {
            title: form.dataset.confirmTitle || 'Konfirmasi Tindakan',
            type: form.dataset.confirmType || 'danger',
            okText: form.dataset.confirmOk || 'Ya, Lanjutkan',
        });
        if (!approved) return;
        form.dataset.confirmApproved = '1';
        form.requestSubmit(event.submitter || undefined);
    });

    const approvedClicks = new WeakSet();
    document.addEventListener('click', async event => {
        const element = event.target.closest('[data-confirm-click]');
        if (!element || approvedClicks.has(element)) return;
        event.preventDefault();
        const approved = await api.confirm(element.dataset.confirmClick, { type: 'danger' });
        if (!approved) return;
        approvedClicks.add(element);
        element.click();
        approvedClicks.delete(element);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', migrateInlineConfirmations, { once: true });
    } else {
        migrateInlineConfirmations();
    }

    const initialPopups = @json($initialPopups);
    initialPopups.forEach(item => api.alert(item.message, item));
})();
</script>