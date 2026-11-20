document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.mgmt-page');
    if (!page) return;

    const $ = id => document.getElementById(id);
    const text = (id, value) => { const node = $(id); if (node) node.textContent = value || '—'; };
    const dialogs = [...document.querySelectorAll('.mgmt-dialog')];
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const flashKey = 'space-admin-management-feedback';
    let currentRow = null;

    try {
        const message = sessionStorage.getItem(flashKey);
        if (message) {
            sessionStorage.removeItem(flashKey);
            const alert = document.createElement('div');
            alert.className = 'sp-alert sp-alert-success mgmt-flash';
            alert.role = 'status';
            alert.textContent = message;
            page.before(alert);
        }
    } catch (_) {}

    function closeMenus() {
        document.querySelectorAll('.mgmt-menu:popover-open').forEach(menu => menu.hidePopover());
    }

    document.querySelectorAll('[data-mgmt-menu]').forEach(trigger => {
        const menu = $(trigger.dataset.mgmtMenu);
        if (!menu) return;
        trigger.addEventListener('click', () => {
            const opened = menu.matches(':popover-open');
            closeMenus();
            if (opened) return;
            menu.showPopover();
            const r = trigger.getBoundingClientRect();
            const m = menu.getBoundingClientRect();
            const left = r.left >= m.width + 18 ? r.left - m.width - 12 : r.right + 12;
            menu.dataset.side = r.left >= m.width + 18 ? 'left' : 'right';
            menu.style.left = `${Math.max(8, Math.min(left, innerWidth - m.width - 8))}px`;
            menu.style.top = `${Math.max(8, Math.min(r.top + (r.height - m.height) / 2, innerHeight - m.height - 8))}px`;
        });
        menu.addEventListener('toggle', () => trigger.setAttribute('aria-expanded', String(menu.matches(':popover-open'))));
    });

    document.addEventListener('scroll', event => {
        if (!event.target.closest?.('.mgmt-menu')) closeMenus();
    }, true);
    window.addEventListener('resize', closeMenus);
    document.querySelectorAll('[data-mgmt-print]').forEach(button => button.addEventListener('click', () => {
        closeMenus();
        window.print();
    }));

    document.querySelectorAll('.sp-data-table').forEach(table => {
        const dense = table.querySelector('[data-table-dense]');
        const key = `space-admin-dense-${table.id}`;
        if (dense) {
            try { dense.checked = localStorage.getItem(key) === '1'; } catch (_) {}
            table.classList.toggle('mgmt-is-dense', dense.checked);
            dense.addEventListener('change', () => {
                table.classList.toggle('mgmt-is-dense', dense.checked);
                try { localStorage.setItem(key, dense.checked ? '1' : '0'); } catch (_) {}
            });
        }
        table.querySelector('[data-table-page-size]')?.addEventListener('change', event => event.currentTarget.form.requestSubmit());
    });

    document.querySelectorAll('[data-mgmt-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
    dialogs.forEach(dialog => dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    }));

    function openDialog(id) {
        const dialog = $(id);
        if (!dialog) return;
        dialogs.forEach(other => { if (other !== dialog && other.open) other.close(); });
        dialog.querySelectorAll('[data-mgmt-errors]').forEach(el => { el.hidden = true; el.textContent = ''; });
        if (!dialog.open) dialog.showModal();
    }

    function error(dialog, message) {
        const box = dialog.querySelector('[data-mgmt-errors]');
        if (!box) return;
        box.textContent = message;
        box.hidden = false;
    }

    async function request(url, body, dialog, submitter = null) {
        if (submitter) submitter.disabled = true;
        const box = dialog?.querySelector('[data-mgmt-errors]');
        if (box) { box.hidden = true; box.textContent = ''; }
        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                error(dialog, validation || payload.message || `Request failed (${response.status}).`);
                return;
            }
            try { sessionStorage.setItem(flashKey, payload.message || 'Changes saved successfully.'); } catch (_) {}
            window.location.reload();
        } catch (_) {
            error(dialog, 'Connection error. Please try again.');
        } finally {
            if (submitter) submitter.disabled = false;
        }
    }

    function categoryOpen(mode, row = null) {
        currentRow = row;
        const form = $('mgmt-category-form');
        if (!form) return;
        form.reset();
        const editing = mode === 'edit';
        form.action = editing ? row.dataset.updateUrl : form.dataset.storeUrl || form.action;
        $('mgmt-category-method').value = editing ? 'PATCH' : 'POST';
        text('mgmt-category-title', editing ? 'Edit category' : 'Add category');
        $('mgmt-category-name').value = editing ? row.dataset.recordName : '';
        $('mgmt-category-img').value = editing ? row.dataset.recordImg : '';
        $('mgmt-category-active').checked = editing ? row.dataset.recordStatus === 'active' : true;
        $('mgmt-category-status').value = $('mgmt-category-active').checked ? '1' : '0';
        openDialog('mgmt-category-dialog');
    }

    const categoryForm = $('mgmt-category-form');
    if (categoryForm) {
        categoryForm.dataset.storeUrl = categoryForm.action;
        $('mgmt-category-active').addEventListener('change', event => {
            $('mgmt-category-status').value = event.target.checked ? '1' : '0';
        });
        categoryForm.addEventListener('submit', event => {
            event.preventDefault();
            request(categoryForm.action, new FormData(categoryForm), $('mgmt-category-dialog'), categoryForm.querySelector('[data-mgmt-submit]'));
        });
        $('mgmt-category-confirm-delete').addEventListener('click', () => {
            if (!currentRow) return;
            const data = new FormData();
            data.set('_method', 'DELETE');
            request(currentRow.dataset.deleteUrl, data, $('mgmt-category-delete-dialog'), $('mgmt-category-confirm-delete'));
        });
    }

    function partnerOpen(row, focusReview = false) {
        currentRow = row;
        const d = row.dataset;
        text('mgmt-partner-name', d.recordName);
        text('mgmt-partner-owner', d.recordOwner);
        text('mgmt-partner-city', d.recordCity);
        text('mgmt-partner-email', d.recordEmail);
        text('mgmt-partner-phone', d.recordPhone);
        text('mgmt-partner-owner-status', d.recordOwnerStatus);
        text('mgmt-partner-joined', d.recordJoined);
        text('mgmt-partner-instagram', d.recordInstagram);
        text('mgmt-partner-bio', d.recordBio || d.recordTagline);
        text('mgmt-partner-existing-note', d.recordNote);
        text('mgmt-partner-status', d.recordStatus.charAt(0).toUpperCase() + d.recordStatus.slice(1));
        $('mgmt-partner-status').className = `sp-badge ${d.recordStatus === 'approved' ? 'sp-badge-success' : d.recordStatus === 'rejected' ? 'sp-badge-danger' : 'sp-badge-warning'}`;
        const pending = d.recordStatus === 'pending';
        const form = $('mgmt-partner-review-form');
        form.hidden = !pending;
        $('mgmt-partner-readonly').hidden = pending;
        form.action = d.reviewUrl;
        $('mgmt-partner-note').value = '';
        openDialog('mgmt-partner-dialog');
        if (focusReview && pending) $('mgmt-partner-note').focus();
    }

    document.querySelectorAll('[data-partner-review]').forEach(button => button.addEventListener('click', () => {
        const form = $('mgmt-partner-review-form');
        const status = button.dataset.partnerReview;
        if (status === 'rejected' && !$('mgmt-partner-note').value.trim()) {
            error($('mgmt-partner-dialog'), 'Please enter a reason before rejecting a partner.');
            return;
        }
        $('mgmt-partner-new-status').value = status;
        if (!confirm(`${status === 'approved' ? 'Approve' : 'Reject'} this partner request?`)) return;
        request(form.action, new FormData(form), $('mgmt-partner-dialog'), button);
    }));

    function catalogOpen(row, focusReview = false) {
        currentRow = row;
        const d = row.dataset;
        text('mgmt-catalog-name', d.recordName);
        text('mgmt-catalog-kind', d.recordType.charAt(0).toUpperCase() + d.recordType.slice(1));
        text('mgmt-catalog-owner', d.recordHost);
        text('mgmt-catalog-secondary', d.recordCity);
        text('mgmt-catalog-price', `${d.recordPrice} JOD`);
        text('mgmt-catalog-visibility', d.recordVisibility);
        text('mgmt-catalog-created', d.recordCreated);
        text('mgmt-catalog-description', d.recordDescription);
        text('mgmt-catalog-status', d.recordStatus.charAt(0).toUpperCase() + d.recordStatus.slice(1));
        $('mgmt-catalog-status').className = `sp-badge ${['approved', 'active'].includes(d.recordStatus) ? 'sp-badge-success' : d.recordStatus === 'rejected' ? 'sp-badge-danger' : 'sp-badge-warning'}`;
        const isPackage = d.recordType === 'packages';
        const editable = isPackage || d.recordStatus === 'pending';
        $('mgmt-catalog-review-trigger').hidden = !editable;
        $('mgmt-catalog-readonly').hidden = editable;
        const form = $('mgmt-catalog-review-form');
        form.hidden = !editable || !focusReview;
        if (editable) {
            form.action = d.reviewUrl;
            const statusSelect = $('mgmt-catalog-new-status');
            statusSelect.querySelector('option[value="rejected"]').hidden = isPackage;
            statusSelect.querySelector('option[value="rejected"]').disabled = isPackage;
            statusSelect.querySelector('option[value="inactive"]').hidden = !isPackage;
            statusSelect.querySelector('option[value="inactive"]').disabled = !isPackage;
            statusSelect.value = isPackage && d.recordStatus === 'active' ? 'inactive' : 'approved';
            $('mgmt-catalog-note').value = d.recordNote || '';
        }
        openDialog('mgmt-catalog-dialog');
        if (focusReview && editable) $('mgmt-catalog-new-status').focus();
    }

    $('mgmt-catalog-review-trigger')?.addEventListener('click', () => {
        $('mgmt-catalog-review-form').hidden = false;
        $('mgmt-catalog-new-status').focus();
    });
    document.querySelector('[data-mgmt-review-cancel]')?.addEventListener('click', () => {
        $('mgmt-catalog-review-form').hidden = true;
    });
    $('mgmt-catalog-review-form')?.addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        request(form.action, new FormData(form), $('mgmt-catalog-dialog'), form.querySelector('[data-mgmt-submit]'));
    });

    function visitOpen(row) {
        const d = row.dataset;
        text('mgmt-visit-path', d.recordName);
        text('mgmt-visit-route', d.recordRoute);
        text('mgmt-visit-type', d.recordType);
        text('mgmt-visit-owner', d.recordOwner);
        text('mgmt-visit-email', d.recordEmail);
        text('mgmt-visit-time', d.recordTime);
        openDialog('mgmt-visit-dialog');
    }

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-mgmt-action]');
        if (!button) return;
        const action = button.dataset.mgmtAction;
        const row = button.closest('tr[data-record-id]');
        closeMenus();
        if (action === 'category-create') categoryOpen('create');
        if (action === 'category-edit' && row) categoryOpen('edit', row);
        if (action === 'category-delete' && row) {
            currentRow = row;
            text('mgmt-delete-name', row.dataset.recordName);
            openDialog('mgmt-category-delete-dialog');
        }
        if (action === 'partner-view' && row) partnerOpen(row);
        if (action === 'partner-review' && row) partnerOpen(row, true);
        if (action === 'catalog-view' && row) catalogOpen(row);
        if (action === 'catalog-review' && row) catalogOpen(row, true);
        if (action === 'visit-view' && row) visitOpen(row);
    });
});
