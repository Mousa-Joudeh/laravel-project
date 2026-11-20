document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const overlay = document.getElementById('admin-header-overlay');
    const panels = {
        notifications: document.getElementById('admin-notifications-drawer'),
        settings: document.getElementById('admin-settings-drawer'),
        search: document.getElementById('admin-header-search'),
    };
    let active = null;
    let previousFocus = null;

    const close = () => {
        if (!active) return;
        const panel = panels[active];
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        document.querySelector(`[data-admin-open="${active}"]`)?.setAttribute('aria-expanded', 'false');
        body.classList.remove('admin-overlay-open');
        active = null;
        if (previousFocus?.isConnected) previousFocus.focus({ preventScroll: true });
    };

    const open = (name) => {
        if (!panels[name]) return;
        if (active === name) return close();
        if (active) close();
        previousFocus = document.activeElement;
        document.getElementById('admin-header-profile')?.removeAttribute('open');
        body.classList.remove('nav-open');
        document.getElementById('admin-menu-button')?.setAttribute('aria-expanded', 'false');
        active = name;
        body.classList.add('admin-overlay-open');
        panels[name].classList.add('is-open');
        panels[name].setAttribute('aria-hidden', 'false');
        document.querySelector(`[data-admin-open="${name}"]`)?.setAttribute('aria-expanded', 'true');
        if (name === 'search') {
            filterSearch('');
            document.getElementById('admin-head-search-input').value = '';
            document.getElementById('admin-head-search-input').focus({ preventScroll: true });
        } else {
            panels[name].querySelector('[data-admin-close]')?.focus({ preventScroll: true });
        }
    };

    document.querySelectorAll('[data-admin-open]').forEach(button => {
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', () => open(button.dataset.adminOpen));
    });
    overlay?.addEventListener('click', close);
    document.querySelectorAll('[data-admin-close]').forEach(button => button.addEventListener('click', close));

    const searchInput = document.getElementById('admin-head-search-input');
    const searchResults = [...document.querySelectorAll('.admin-head-search-result')];
    let selectedSearch = 0;

    function filterSearch(value) {
        const term = value.trim().toLowerCase();
        searchResults.forEach(item => { item.hidden = !item.dataset.adminSearchText.includes(term); });
        selectedSearch = 0;
        updateSearchSelection();
        const noMatches = document.getElementById('admin-head-search-none');
        if (noMatches) noMatches.hidden = searchResults.some(item => !item.hidden);
    }

    function updateSearchSelection() {
        const shown = searchResults.filter(item => !item.hidden);
        searchResults.forEach(item => item.classList.remove('is-selected'));
        if (shown.length) {
            selectedSearch = (selectedSearch + shown.length) % shown.length;
            shown[selectedSearch].classList.add('is-selected');
            shown[selectedSearch].scrollIntoView({ block: 'nearest' });
        }
    }

    searchInput?.addEventListener('input', () => filterSearch(searchInput.value));
    searchInput?.addEventListener('keydown', event => {
        const shown = searchResults.filter(item => !item.hidden);
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!shown.length) return;
            selectedSearch += event.key === 'ArrowDown' ? 1 : -1;
            updateSearchSelection();
        }
        if (event.key === 'Enter' && shown[selectedSearch]) {
            event.preventDefault();
            window.location.assign(shown[selectedSearch].href);
        }
    });

    document.querySelectorAll('[data-admin-notice-filter]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-admin-notice-filter]').forEach(tab => {
                const selected = tab === button;
                tab.classList.toggle('is-selected', selected);
                tab.setAttribute('aria-pressed', String(selected));
            });
            const type = button.dataset.adminNoticeFilter;
            const rows = [...document.querySelectorAll('[data-admin-notice-kind]')];
            rows.forEach(row => { row.hidden = type !== 'all' && row.dataset.adminNoticeKind !== type; });
            const empty = document.getElementById('admin-notices-filter-empty');
            if (empty) empty.hidden = rows.some(row => !row.hidden);
        });
    });

    const preferences = { compact: 'admin-preference-compact', contrast: 'admin-preference-contrast', motion: 'admin-preference-reduced-motion' };
    const keyFor = key => `space-admin-header:${key}`;
    const getPreference = key => {
        try { return localStorage.getItem(keyFor(key)) === '1'; } catch (_) { return false; }
    };
    const setPreference = (key, enabled) => {
        body.classList.toggle(preferences[key], enabled);
        const input = document.querySelector(`[data-admin-setting="${key}"]`);
        if (input) input.checked = enabled;
        try { localStorage.setItem(keyFor(key), enabled ? '1' : '0'); } catch (_) {}
    };
    Object.keys(preferences).forEach(key => setPreference(key, getPreference(key)));
    document.querySelectorAll('[data-admin-setting]').forEach(input => input.addEventListener('change', () => setPreference(input.dataset.adminSetting, input.checked)));
    document.getElementById('admin-settings-reset')?.addEventListener('click', () => Object.keys(preferences).forEach(key => setPreference(key, false)));

    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            open('search');
            return;
        }
        if (event.key === 'Escape') {
            if (active) { event.preventDefault(); close(); }
            document.getElementById('admin-header-profile')?.removeAttribute('open');
        }
        if (event.key === 'Tab' && active) {
            const focusable = [...panels[active].querySelectorAll('button:not([disabled]), a[href]:not([hidden]), input:not([disabled]), [tabindex="0"]')]
                .filter(el => !el.hidden && el.offsetParent !== null);
            if (!focusable.length) { event.preventDefault(); return; }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    document.addEventListener('click', event => {
        const profile = document.getElementById('admin-header-profile');
        if (profile?.open && !profile.contains(event.target)) profile.open = false;
    });
});
