document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('admin-csv-import');
    if (!dialog) return;
    document.querySelectorAll('[data-admin-csv-import-open]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.mgmt-menu:popover-open').forEach(menu => menu.hidePopover());
            if (!dialog.open) dialog.showModal();
        });
    });
    dialog.querySelectorAll('[data-admin-csv-import-close]').forEach(button =>
        button.addEventListener('click', () => dialog.close())
    );
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
});
