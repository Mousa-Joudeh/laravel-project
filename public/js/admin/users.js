document.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('sp-users-card');
    if (!card) return;

    const byId = id => document.getElementById(id);
    const adminUrl = card.dataset.adminUsersUrl;
    const selfId = card.dataset.currentUserId;
    const dense = byId('sp-dense-toggle');

    try {
        dense.checked = localStorage.getItem('space-users-dense') === '1';
    } catch (_) {
        dense.checked = false;
    }
    card.classList.toggle('sp-is-dense', dense.checked);
    dense.addEventListener('change', () => {
        card.classList.toggle('sp-is-dense', dense.checked);
        try {
            localStorage.setItem('space-users-dense', dense.checked ? '1' : '0');
        } catch (_) {}
    });

    byId('sp-rows-per-page')?.addEventListener('change', event => {
        event.currentTarget.form.requestSubmit();
    });

    const openMenus = () => document.querySelectorAll('[popover]:popover-open');
    const closeMenus = () => openMenus().forEach(menu => menu.hidePopover());

    function positionMenu(trigger, menu, mode) {
        const rect = trigger.getBoundingClientRect();
        const { width, height } = menu.getBoundingClientRect();
        const margin = 8;
        const clamp = (number, min, max) => Math.max(min, Math.min(number, max));

        if (mode === 'row') {
            const leftSide = rect.left >= width + 18;
            menu.dataset.side = leftSide ? 'left' : 'right';
            menu.style.left = `${clamp(leftSide ? rect.left - width - 13 : rect.right + 13, margin, innerWidth - width - margin)}px`;
            menu.style.top = `${clamp(rect.top + (rect.height - height) / 2, margin, innerHeight - height - margin)}px`;
        } else {
            menu.style.left = `${clamp(rect.right - width, margin, innerWidth - width - margin)}px`;
            menu.style.top = `${clamp(rect.bottom + 6, margin, innerHeight - height - margin)}px`;
        }
    }

    document.querySelectorAll('[data-more-toggle], [data-sp-menu]').forEach(trigger => {
        const menuId = trigger.dataset.moreToggle || trigger.dataset.spMenu;
        const menu = byId(menuId);
        if (!menu) return;

        trigger.addEventListener('click', () => {
            const wasOpen = menu.matches(':popover-open');
            closeMenus();
            if (wasOpen) return;
            menu.showPopover();
            positionMenu(trigger, menu, trigger.hasAttribute('data-more-toggle') ? 'row' : 'tools');
        });

        menu.addEventListener('toggle', () => {
            trigger.setAttribute('aria-expanded', String(menu.matches(':popover-open')));
        });
    });

    document.addEventListener('scroll', event => {
        if (!event.target.closest?.('.sp-popup-menu, .sp-more-menu')) closeMenus();
    }, true);
    window.addEventListener('resize', closeMenus);
    byId('sp-table-tools')?.querySelector('[data-sp-print]')?.addEventListener('click', () => {
        closeMenus();
        window.print();
    });

    const importDialog = byId('sp-import-dialog');
    document.querySelector('[data-sp-import-open]')?.addEventListener('click', () => {
        closeMenus();
        importDialog?.showModal();
    });
    document.querySelectorAll('[data-sp-import-close]').forEach(button => {
        button.addEventListener('click', () => importDialog?.close());
    });

    const cities = [
        'Amman', 'Zarqa', 'Irbid', 'Aqaba', 'Salt', 'Madaba',
        'Jerash', 'Ajloun', 'Mafraq', 'Karak', 'Tafila', "Ma'an",
        'Ramtha', 'Russeifa', 'Sahab', 'Fuheis', 'Wadi Musa'
    ];
    const closeCityLists = [];

    document.querySelectorAll('.sp-city-dropdown').forEach(root => {
        const input = root.querySelector('input[role="combobox"]');
        const list = root.querySelector('[role="listbox"]');
        const toggle = root.querySelector('.sp-city-arrow');
        if (!input || !list || !toggle) return;

        let matches = [];
        let activeIndex = -1;

        const close = () => {
            list.hidden = true;
            activeIndex = -1;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        };
        closeCityLists.push(close);

        const select = city => {
            input.value = city;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus({ preventScroll: true });
            close();
        };

        const render = query => {
            matches = cities.filter(city => city.toLowerCase().includes(query.trim().toLowerCase()));
            activeIndex = -1;
            list.replaceChildren();

            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'sp-city-empty';
                empty.textContent = 'No matching cities';
                list.appendChild(empty);
            }

            matches.forEach((city, index) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.id = `${list.id}-option-${index}`;
                option.className = 'sp-city-option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', String(city === input.value));
                option.textContent = city;
                option.addEventListener('click', () => select(city));
                list.appendChild(option);
            });

            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        const highlight = next => {
            if (!matches.length) return;
            activeIndex = (next + matches.length) % matches.length;
            const options = [...list.querySelectorAll('.sp-city-option')];
            options.forEach((option, index) => {
                option.classList.toggle('is-highlighted', index === activeIndex);
            });
            input.setAttribute('aria-activedescendant', options[activeIndex].id);
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        };

        input.addEventListener('focus', () => render(input.value));
        input.addEventListener('input', () => render(input.value));
        toggle.addEventListener('click', () => {
            if (!list.hidden) return close();
            input.focus();
            render('');
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.hidden) render(input.value);
                highlight(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
            } else if (event.key === 'Enter' && !list.hidden && matches.length) {
                event.preventDefault();
                select(matches[activeIndex >= 0 ? activeIndex : 0]);
            } else if (event.key === 'Escape' && !list.hidden) {
                event.preventDefault();
                event.stopPropagation();
                close();
            } else if (event.key === 'Tab') {
                close();
            }
        });
        document.addEventListener('pointerdown', event => {
            if (!root.contains(event.target)) close();
        });
    });

    const createDialog = byId('sp-create-user-dialog');
    const createForm = byId('sp-create-user-form');
    const createError = byId('sp-create-user-error');
    const createSubmit = byId('sp-create-user-submit');

    const setAlert = (element, message) => {
        element.textContent = message;
        element.hidden = !message;
    };

    function clearFormErrors(form, alert, selector) {
        setAlert(alert, '');
        form.querySelectorAll(`[${selector}]`).forEach(node => { node.textContent = ''; });
        form.querySelectorAll('[aria-invalid="true"]').forEach(input => input.removeAttribute('aria-invalid'));
    }

    function showFormErrors(form, errors, attribute) {
        for (const [name, messages] of Object.entries(errors || {})) {
            const error = [...form.querySelectorAll(`[${attribute}]`)]
                .find(node => node.getAttribute(attribute) === name);
            if (error) error.textContent = messages[0] || '';
            const input = form.elements.namedItem(name);
            if (input?.setAttribute) input.setAttribute('aria-invalid', 'true');
        }
    }

    async function submitUserForm(form, button, alert, attribute) {
        clearFormErrors(form, alert, attribute);
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'manual',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
                window.location.assign(adminUrl);
                return;
            }

            const payload = await response.json().catch(() => ({}));
            if (response.ok && payload.success !== false && response.headers.get('content-type')?.includes('application/json')) {
                window.location.assign(adminUrl);
                return;
            }
            if (response.status === 422) {
                showFormErrors(form, payload.errors, attribute);
            }
            setAlert(alert, response.status === 419
                ? 'Session expired. Refresh the page and try again.'
                : payload.message || 'Unable to complete the action.');
        } catch (_) {
            setAlert(alert, 'Connection error. Please try again.');
        } finally {
            button.disabled = false;
        }
    }

    byId('sp-open-create-user')?.addEventListener('click', () => {
        closeMenus();
        createDialog.showModal();
        byId('create-name').focus();
    });
    createDialog?.querySelectorAll('[data-close-create-user]').forEach(button => {
        button.addEventListener('click', () => createDialog.close());
    });
    createDialog?.addEventListener('close', () => closeCityLists.forEach(close => close()));
    createForm?.addEventListener('submit', event => {
        event.preventDefault();
        submitUserForm(createForm, createSubmit, createError, 'data-error-for');
    });

    const userDialog = byId('sp-user-dialog');
    const editForm = byId('sp-user-edit-panel');
    const deleteForm = byId('sp-user-delete-panel');
    if (!userDialog || !editForm || !deleteForm) return;

    let currentUser;
    const showValue = value => value?.trim() || '—';
    const capitalize = value => value ? value.charAt(0).toUpperCase() + value.slice(1) : '—';
    const text = (id, value) => { byId(id).textContent = showValue(value); };

    function showPanel(panel) {
        if (!currentUser || (panel === 'delete' && currentUser.id === selfId)) return;
        ['view', 'edit', 'delete'].forEach(name => {
            byId(`sp-user-${name}-panel`).hidden = name !== panel;
        });
        userDialog.classList.toggle('sp-um-is-delete', panel === 'delete');
        closeCityLists.forEach(close => close());
        if (!userDialog.open) userDialog.showModal();
        if (panel === 'edit') byId('sp-edit-name').focus();
    }

    function populateUser(row) {
        const data = row.dataset;
        currentUser = {
            id: data.userId,
            name: data.userName || '',
            email: data.userEmail || '',
            phone: data.userPhone || '',
            city: data.userCity || '',
            company: data.userCompany || '',
            role: data.userRole || '',
            status: data.userStatus || '',
            verified: data.userVerified || '',
            joined: data.userJoined || '',
            updated: data.userUpdated || '',
            updateUrl: data.userUpdateUrl,
            deleteUrl: data.userDeleteUrl
        };
        const user = currentUser;

        for (const name of ['name', 'email', 'phone', 'city', 'company', 'verified', 'joined', 'updated']) {
            text(`sp-view-${name}`, user[name]);
        }
        text('sp-view-id', `#${user.id}`);
        text('sp-view-role', capitalize(user.role));
        text('sp-view-status', capitalize(user.status));
        byId('sp-view-avatar').textContent = Array.from(user.name)[0]?.toUpperCase() || 'U';
        byId('sp-view-status').className = `sp-badge ${user.status === 'active' ? 'sp-badge-success' : 'sp-badge-danger'}`;
        byId('sp-view-company-wrap').hidden = !(user.role === 'provider' && user.company);
        byId('sp-view-delete').hidden = user.id === selfId;
        byId('sp-copy-email').querySelector('span').textContent = 'Copy email';

        editForm.reset();
        editForm.action = user.updateUrl;
        deleteForm.action = user.deleteUrl;
        for (const name of ['name', 'email', 'phone', 'city', 'role', 'status']) {
            byId(`sp-edit-${name}`).value = user[name];
        }
        for (const name of ['sp-edit-password', 'sp-edit-password-confirmation']) {
            byId(name).value = '';
        }
        text('sp-delete-name', user.name);

        for (const fieldName of ['role', 'status']) {
            const select = byId(`sp-edit-${fieldName}`);
            const allowed = fieldName === 'role' ? 'admin' : 'active';
            [...select.options].forEach(option => {
                option.disabled = user.id === selfId && option.value !== allowed;
            });
        }
        clearFormErrors(editForm, byId('sp-edit-error'), 'data-edit-error');
        clearFormErrors(deleteForm, byId('sp-delete-error'), 'data-edit-error');
    }

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-sp-user-action]');
        if (!button) return;
        const row = button.closest('tr[data-user-id]');
        if (!row) return;
        closeMenus();
        populateUser(row);
        showPanel(button.dataset.spUserAction);
    });

    userDialog.querySelectorAll('[data-um-close]').forEach(button => {
        button.addEventListener('click', () => userDialog.close());
    });
    userDialog.querySelectorAll('[data-um-switch]').forEach(button => {
        button.addEventListener('click', () => showPanel(button.dataset.umSwitch));
    });
    userDialog.addEventListener('close', () => closeCityLists.forEach(close => close()));

    byId('sp-copy-email')?.addEventListener('click', async () => {
        if (!currentUser) return;
        const label = byId('sp-copy-email').querySelector('span');
        try {
            await navigator.clipboard.writeText(currentUser.email);
            label.textContent = 'Copied!';
        } catch (_) {
            label.textContent = 'Unable to copy';
        }
    });

    editForm.addEventListener('submit', event => {
        event.preventDefault();
        submitUserForm(editForm, byId('sp-edit-submit'), byId('sp-edit-error'), 'data-edit-error');
    });
    deleteForm.addEventListener('submit', event => {
        event.preventDefault();
        submitUserForm(deleteForm, byId('sp-delete-submit'), byId('sp-delete-error'), 'data-edit-error');
    });
});
