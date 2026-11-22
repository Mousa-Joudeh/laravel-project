/* SPACES Admin: non-blocking popups for success/error alerts, and a styled confirmation dialog. */
(() => {
    'use strict';

    const ALERTS = [
        '.admin-content .sp-alert',
        '.admin-content .mgmt-form-alert',
        '.admin-content .alert',
        '.admin-content .sp-um-alert',
        '.admin-content .mgmt-flash',
        '.admin-content [role="alert"]',
        '.admin-content .sp-badge-success[role="status"]'
    ].join(', ');

    function makeStack(host = document.body) {
        let stack = Array.from(host.children).find(child => child.classList?.contains('space-admin-popup-stack'));
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'space-admin-popup-stack';
            stack.setAttribute('aria-label', 'Notifications');
            host.appendChild(stack);
        }
        return stack;
    }

    function toast(message, type = 'success') {
        const text = String(message || '').replace(/\s+/g, ' ').trim();
        if (!text) return;
        const dialog = Array.from(document.querySelectorAll('dialog[open]')).at(-1);
        const stack = makeStack(dialog || document.body);
        const kind = type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success';
        const popup = document.createElement('div');
        popup.className = 'space-admin-popup' + (kind === 'success' ? '' : ` is-${kind}`);
        popup.setAttribute('role', kind === 'success' ? 'status' : 'alert');

        const icon = document.createElement('span');
        icon.className = 'space-admin-popup-icon';
        icon.innerHTML = '<i class="bi ' + (kind === 'success' ? 'bi-check2-circle' : kind === 'error' ? 'bi-exclamation-triangle' : 'bi-exclamation-circle') + '" aria-hidden="true"></i>';
        const copy = document.createElement('span');
        copy.className = 'space-admin-popup-copy';
        const title = document.createElement('div');
        title.className = 'space-admin-popup-title';
        title.textContent = kind === 'success' ? 'Success' : kind === 'warning' ? 'Notice' : 'Something went wrong';
        const body = document.createElement('div');
        body.className = 'space-admin-popup-message';
        body.textContent = text; // Never interpolate user / server messages as HTML.
        copy.append(title, body);
        const close = document.createElement('button');
        close.className = 'space-admin-popup-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
        close.addEventListener('click', () => popup.remove());
        popup.append(icon, copy, close);
        stack.append(popup);
        const duration = kind === 'success' ? 5500 : 9000;
        const timer = window.setTimeout(() => popup.remove(), duration);
        popup.addEventListener('mouseenter', () => window.clearTimeout(timer), {once: true});
    }

    function confirmMessage(message) {
        return new Promise(resolve => {
            const dialog = document.createElement('dialog');
            dialog.className = 'space-admin-popup-confirm';
            dialog.innerHTML = `
                <div class="space-admin-popup-confirm-icon"><i class="bi bi-question-circle" aria-hidden="true"></i></div>
                <h2>Confirm action</h2>
                <p class="space-confirm-message"></p>
                <div class="space-admin-popup-confirm-actions">
                    <button type="button" class="space-confirm-cancel">Cancel</button>
                    <button type="button" class="space-confirm-accept">Confirm</button>
                </div>`;
            dialog.querySelector('.space-confirm-message').textContent = String(message || 'Continue?');
            document.body.appendChild(dialog);
            let settled = false;
            function finish(answer) {
                if (settled) return;
                settled = true;
                dialog.close();
                dialog.remove();
                resolve(answer);
            }
            dialog.querySelector('.space-confirm-cancel').addEventListener('click', () => finish(false));
            dialog.querySelector('.space-confirm-accept').addEventListener('click', () => finish(true));
            dialog.addEventListener('cancel', event => { event.preventDefault(); finish(false); });
            dialog.showModal();
            dialog.querySelector('.space-confirm-cancel').focus();
        });
    }

    // Make the API available for existing admin scripts.
    window.SpaceAdminPopup = Object.freeze({ toast, confirm: confirmMessage });

    function alertKind(element) {
        const classes = element.className || '';
        if (/danger|error|invalid|fail/i.test(classes) || element.getAttribute('role') === 'alert') return 'error';
        if (/warning/i.test(classes)) return 'warning';
        return 'success';
    }

    function convert(element) {
        if (!(element instanceof HTMLElement) || element.closest('.space-admin-popup-stack')) return;
        if (element.hidden || element.closest('[hidden]')) {
            element.dataset.spacePopupLastMessage = '';
            return;
        }
        if (element.closest('dialog') && !element.closest('dialog').open) return;
        if (!element.classList.contains('space-admin-popup-source-hidden') && getComputedStyle(element).display === 'none') return;

        const text = (element.textContent || '').replace(/\s+/g, ' ').trim();
        if (!text || element.dataset.spacePopupLastMessage === text) return;
        element.dataset.spacePopupLastMessage = text;
        element.classList.add('space-admin-popup-source-hidden');
        toast(text, alertKind(element));
    }

    function scanAlerts(root = document) {
        if (root instanceof Element && root.matches(ALERTS)) convert(root);
        root.querySelectorAll?.(ALERTS).forEach(convert);
    }

    function start() {
        const adminContent = document.querySelector('.admin-content');
        if (!adminContent) return;
        scanAlerts(adminContent);
        // Detect server-rendered flash alerts and AJAX validation errors shown later.
        const observer = new MutationObserver(() => scanAlerts(adminContent));
        observer.observe(adminContent, { childList: true, subtree: true, characterData: true, attributes: true,
            attributeFilter: ['hidden', 'style', 'class', 'open'] });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
