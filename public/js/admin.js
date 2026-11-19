document.addEventListener('DOMContentLoaded', () => {
    const open = document.getElementById('admin-menu-button');
    const backdrop = document.getElementById('admin-backdrop');
    const close = () => {
        document.body.classList.remove('nav-open');
        open?.setAttribute('aria-expanded', 'false');
    };

    open?.addEventListener('click', () => {
        const isOpen = document.body.classList.toggle('nav-open');
        open.setAttribute('aria-expanded', String(isOpen));
    });

    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') close();
    });
});
