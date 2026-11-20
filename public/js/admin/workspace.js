document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-management="approvals"], [data-management="operations"]');
    if (!page) return;
    const get = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const text = (id, value) => { const target = get(id); if (target) target.textContent = value || '—'; };
    let activeRow = null;

    const show = id => { const dialog = get(id); if (dialog && !dialog.open) dialog.showModal(); };
    const fail = (dialog, message) => {
        const box = dialog?.querySelector('[data-mgmt-errors]');
        if (box) { box.hidden = false; box.textContent = message; }
    };
    async function send(url, form, dialog, button) {
        if (button) button.disabled = true;
        const box = dialog?.querySelector('[data-mgmt-errors]');
        if (box) { box.hidden = true; box.textContent = ''; }
        try {
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
                body: new FormData(form)
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                fail(dialog, result.errors ? Object.values(result.errors).flat().join(' ') : result.message || `Request failed (${response.status})`);
                return;
            }
            try { sessionStorage.setItem('space-admin-management-feedback', result.message || 'Saved successfully'); } catch (_) {}
            location.reload();
        } catch (_) { fail(dialog, 'Connection error. Try again.'); }
        finally { if (button) button.disabled = false; }
    }

    function openApproval(row, focus = false) {
        activeRow = row;
        const d = row.dataset;
        text('approval-title', d.recordName);
        text('approval-subtitle', `${d.recordType} · #${d.recordId}`);
        text('approval-owner', d.recordOwner);
        text('approval-status', d.recordStatus);
        text('approval-date', d.recordCreated);
        text('approval-existing-note', d.recordNote);
        text('approval-description', d.recordDescription);
        if (get('approval-reject-button')) get('approval-reject-button').hidden = d.recordType === 'packages';
        text('approval-hint', d.recordType === 'packages' ? 'Approving activates the package. Rejection history is not available without a dedicated status field.' : 'Only pending requests can be approved or rejected.');
        const form = get('approval-form');
        form.action = d.reviewUrl;
        form.hidden = d.recordStatus !== 'pending';
        get('approval-note').value = '';
        show('approval-detail');
        if (focus && !form.hidden) get('approval-note').focus();
    }
    document.querySelectorAll('[data-approval-status]').forEach(button => button.addEventListener('click', () => {
        if (!activeRow || activeRow.dataset.recordStatus !== 'pending') return;
        const status = button.dataset.approvalStatus;
        const note = get('approval-note').value.trim();
        if (status === 'rejected' && !note) {
            fail(get('approval-detail'), 'A reason is required to reject a request.'); return;
        }
        get('approval-new-status').value = status;
        send(get('approval-form').action, get('approval-form'), get('approval-detail'), button);
    }));
    function openRecord(row) {
        const d = row.dataset;
        text('operation-title', d.recordName);
        text('operation-type', d.recordType);
        ['owner', 'studio', 'email', 'status', 'price', 'period', 'extra'].forEach(field => text(`operation-${field}`, d[`record${field[0].toUpperCase()}${field.slice(1)}`]));
        show('operation-detail');
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-work-action]');
        if (!button) return;
        const row = button.closest('tr[data-record-id]');
        if (!row) return;
        document.querySelectorAll('.mgmt-menu:popover-open').forEach(menu => menu.hidePopover());
        switch (button.dataset.workAction) {
            case 'approval-view': openApproval(row); break;
            case 'approval-review': openApproval(row, true); break;
            case 'operation-view': openRecord(row); break;
            case 'review-delete':
                activeRow = row;
                get('review-remove-form').action = row.dataset.removeUrl;
                get('review-remove-form').reset();
                text('review-remove-title', `Remove review #${row.dataset.recordId}`);
                show('review-remove-dialog');
                break;
        }
    });
    get('review-remove-form')?.addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        const reason = get('review-remove-reason').value.trim();
        if (reason.length < 5) { fail(get('review-remove-dialog'), 'Please provide a reason of at least 5 characters.'); return; }
        send(form.action, form, get('review-remove-dialog'), form.querySelector('[type="submit"]'));
    });
});
