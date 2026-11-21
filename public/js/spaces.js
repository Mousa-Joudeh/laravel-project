/* SPACES — small vanilla JS for the whole site (no build step needed) */
(function () {
    'use strict';

    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
    const csrf = () => $('meta[name="csrf-token"]')?.content;
    const money = (n) => 'JD ' + Number(n).toFixed(Number(n) % 1 === 0 ? 0 : 2);

    /* ---------- nav: shadow on scroll ---------- */
    const nav = $('.site-nav');
    if (nav) {
        const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- dropdowns ---------- */
    $$('[data-dropdown]').forEach((dd) => {
        const btn = $('[data-dropdown-btn]', dd);
        btn?.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = !dd.classList.contains('open');
            $$('[data-dropdown].open').forEach((o) => o.classList.remove('open'));
            dd.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', open);
        });
    });
    document.addEventListener('click', (e) => {
        $$('[data-dropdown].open').forEach((dd) => {
            if (!dd.contains(e.target)) {
                dd.classList.remove('open');
                $('[data-dropdown-btn]', dd)?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    /* ---------- toggles: mobile nav, dashboard sidebar, filters drawer ---------- */
    $$('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = $(btn.dataset.toggle);
            target?.classList.toggle('open');
            if (btn.dataset.backdrop) $(btn.dataset.backdrop)?.classList.toggle('open');
        });
    });
    $$('.filters-backdrop').forEach((b) => b.addEventListener('click', () => {
        b.classList.remove('open');
        $('.filters')?.classList.remove('open');
    }));

    /* ---------- toasts ---------- */
    $$('.toast').forEach((t, i) => {
        const close = () => { t.classList.add('hide'); setTimeout(() => t.remove(), 350); };
        $('button', t)?.addEventListener('click', close);
        setTimeout(close, 4800 + i * 600);
    });

    /* ---------- reveal on scroll ---------- */
    const reveals = $$('.reveal');
    if ('IntersectionObserver' in window && reveals.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
        reveals.forEach((el) => io.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add('in'));
    }

    /* ---------- image sliders inside cards ---------- */
    $$('[data-slider]').forEach((slider) => {
        const track = $('.slides', slider);
        const count = track ? track.children.length : 0;
        const dots = $$('.slide-dots span', slider);
        const prev = $('.slide-btn.prev', slider);
        const next = $('.slide-btn.next', slider);
        let i = 0;
        const go = (n) => {
            i = Math.max(0, Math.min(count - 1, n));
            track.style.transform = `translateX(-${i * 100}%)`;
            dots.forEach((d, k) => d.classList.toggle('on', k === i));
            if (prev) prev.disabled = i === 0;
            if (next) next.disabled = i === count - 1;
        };
        prev?.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); go(i - 1); });
        next?.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); go(i + 1); });
        if (count) go(0);
    });

    /* ---------- hero carousel with a shutter flash ---------- */
    const hero = $('[data-hero]');
    if (hero) {
        const imgs = $$('img', hero);
        const dots = $$('.hero-dots button');
        const flash = $('.hero-flash', hero);
        let i = 0;
        let timer;
        const show = (n) => {
            i = (n + imgs.length) % imgs.length;
            flash?.classList.remove('go');
            void flash?.offsetWidth;
            flash?.classList.add('go');
            imgs.forEach((im, k) => im.classList.toggle('on', k === i));
            dots.forEach((d, k) => d.classList.toggle('on', k === i));
        };
        const auto = () => { clearInterval(timer); timer = setInterval(() => show(i + 1), 5200); };
        dots.forEach((d, k) => d.addEventListener('click', () => { show(k); auto(); }));
        auto();
    }

    /* ---------- film strip: drifts by itself and follows the scroll ---------- */
    const film = $('.filmstrip-track');
    if (film) {
        film.innerHTML += film.innerHTML; // loop
        let offset = 0;
        let last = performance.now();
        const half = () => film.scrollHeight / 2;
        const tick = (now) => {
            offset += (now - last) * 0.012;
            last = now;
            const y = (offset + window.scrollY * 0.35) % half();
            film.style.transform = `translateY(-${y}px)`;
            requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }

    /* ---------- gallery lightbox ---------- */
    const lightbox = $('.lightbox');
    if (lightbox) {
        const img = $('img', lightbox);
        const counter = $('.lb-count', lightbox);
        const sources = $$('[data-gallery-src]').map((b) => b.dataset.gallerySrc);
        const uniq = [...new Set(sources)];
        let i = 0;
        const show = (n) => {
            i = (n + uniq.length) % uniq.length;
            img.src = uniq[i];
            counter.textContent = `${String(i + 1).padStart(2, '0')} / ${String(uniq.length).padStart(2, '0')}`;
        };
        $$('[data-gallery-src]').forEach((b) => b.addEventListener('click', () => {
            show(uniq.indexOf(b.dataset.gallerySrc));
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }));
        const close = () => { lightbox.classList.remove('open'); document.body.style.overflow = ''; };
        $('.lb-close', lightbox).addEventListener('click', close);
        $('.lb-prev', lightbox).addEventListener('click', () => show(i - 1));
        $('.lb-next', lightbox).addEventListener('click', () => show(i + 1));
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(i - 1);
            if (e.key === 'ArrowRight') show(i + 1);
        });
    }

    /* ---------- favourites (heart buttons) ---------- */
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.fav-form');
        if (!form) return;
        e.preventDefault();
        const btn = $('button', form);
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                body: new FormData(form),
            });
            if (res.status === 401 || res.redirected) { window.location = '/login'; return; }
            const data = await res.json();
            btn.classList.toggle('saved', data.saved);
            btn.classList.remove('pop'); void btn.offsetWidth; btn.classList.add('pop');
            const icon = $('i', btn);
            icon.className = data.saved ? 'bi bi-heart-fill' : 'bi bi-heart';
            const label = $('[data-fav-label]', btn);
            if (label) label.textContent = data.saved ? 'Saved' : 'Save';
            if (form.dataset.removeCard && !data.saved) form.closest('[data-card]')?.remove();
        } catch (err) {
            form.submit();
        }
    });

    /* ---------- confirm before destructive actions ---------- */
    // Shows a toast with Cancel / Confirm buttons instead of the browser's popup.
    const toastWrap = () => {
        let wrap = $('.toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'toast-wrap';
            wrap.setAttribute('role', 'status');
            document.body.appendChild(wrap);
        }
        return wrap;
    };

    const toastConfirm = (message, danger) => new Promise((resolve) => {
        const t = document.createElement('div');
        t.className = 'toast confirm' + (danger ? ' danger' : '');
        t.setAttribute('role', 'alertdialog');

        const icon = document.createElement('i');
        icon.className = 'bi ' + (danger ? 'bi-exclamation-triangle' : 'bi-question-circle');
        const text = document.createElement('span');
        text.className = 'msg';
        text.textContent = message;
        const actions = document.createElement('div');
        actions.className = 'toast-actions';
        const no = document.createElement('button');
        no.type = 'button';
        no.className = 'toast-no';
        no.textContent = 'Cancel';
        const yes = document.createElement('button');
        yes.type = 'button';
        yes.className = 'toast-yes';
        yes.textContent = 'Confirm';
        actions.append(no, yes);
        t.append(icon, text, actions);

        const done = (answer) => {
            document.removeEventListener('keydown', onKey);
            t.classList.add('hide');
            setTimeout(() => t.remove(), 350);
            resolve(answer);
        };
        const onKey = (ev) => { if (ev.key === 'Escape') done(false); };
        no.addEventListener('click', () => done(false));
        yes.addEventListener('click', () => done(true));
        document.addEventListener('keydown', onKey);

        toastWrap().appendChild(t);
        yes.focus();
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form.dataset.confirm || form.dataset.confirmed === '1') return;

        e.preventDefault();
        const submitter = e.submitter;
        const danger = !!form.querySelector('input[name="_method"][value="DELETE"], input[name="status"][value="cancelled"]');

        toastConfirm(form.dataset.confirm, danger).then((ok) => {
            if (!ok) return;
            form.dataset.confirmed = '1';
            form.requestSubmit ? form.requestSubmit(submitter) : form.submit();
        });
    }, true);

    /* ---------- modals ---------- */
    const openModal = (m) => { m.classList.add('open'); document.body.style.overflow = 'hidden'; m.dispatchEvent(new Event('modal:open')); };
    const closeModal = (m) => { m.classList.remove('open'); document.body.style.overflow = ''; };
    $$('[data-open-modal]').forEach((b) => b.addEventListener('click', (e) => {
        e.preventDefault();
        const m = $(b.dataset.openModal);
        if (m) openModal(m);
    }));
    $$('.modal').forEach((m) => {
        m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close-modal]')) closeModal(m); });
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.modal.open').forEach(closeModal); });

    /* ---------- booking modal ---------- */
    $$('[data-booking]').forEach((modal) => {
        const cfg = modal.dataset;
        const min = parseInt(cfg.min, 10);
        const max = parseInt(cfg.max, 10);
        let hours = min;
        let date = null;
        let time = null;
        let basePrice = 0;

        const strip = $('.date-strip', modal);
        const dateInput = $('[name="date"]', modal);
        const dayChip = $('[data-day]', modal);
        const hoursOut = $('[data-hours]', modal);
        const hoursInput = $('[name="hours"]', modal);
        const slotGrid = $('.slot-grid', modal);
        const timeInput = $('[name="start_time"]', modal);
        const submit = $('[type="submit"]', modal);
        const addons = $$('[name="addons[]"]', modal);
        const sumBase = $('[data-sum-base]', modal);
        const sumAddons = $('[data-sum-addons]', modal);
        const sumTotal = $('[data-sum-total]', modal);
        const sumWhen = $('[data-sum-when]', modal);

        // next 14 days as quick buttons
        const fmt = (d) => d.toISOString().slice(0, 10);
        const local = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000);
        const today = new Date();
        for (let k = 0; k < 14; k++) {
            const d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + k);
            const b = document.createElement('button');
            b.type = 'button';
            b.dataset.date = fmt(local(d));
            b.innerHTML = `<small>${d.toLocaleDateString('en-GB', { weekday: 'short' })}</small><b>${d.getDate()}</b>`;
            b.addEventListener('click', () => setDate(b.dataset.date));
            strip.appendChild(b);
        }
        dateInput.min = fmt(local(today));

        const setDate = (value) => {
            date = value;
            dateInput.value = value;
            $$('button', strip).forEach((b) => b.classList.toggle('on', b.dataset.date === value));
            const d = new Date(value + 'T00:00:00');
            dayChip.innerHTML = `<i class="bi bi-calendar-event"></i> ${d.toLocaleDateString('en-GB', { weekday: 'long' })}`;
            load();
        };
        dateInput.addEventListener('change', () => dateInput.value && setDate(dateInput.value));

        const setHours = (h) => {
            hours = Math.max(min, Math.min(max, h));
            hoursOut.textContent = hours + (hours === 1 ? ' hour' : ' hours');
            hoursInput.value = hours;
            if (date) load();
        };
        $('[data-hours-minus]', modal).addEventListener('click', () => setHours(hours - 1));
        $('[data-hours-plus]', modal).addEventListener('click', () => setHours(hours + 1));

        addons.forEach((a) => a.addEventListener('change', () => { if (date) load(); else summary(); }));

        const addonTotal = () => addons.filter((a) => a.checked).reduce((sum, a) => {
            const p = parseFloat(a.dataset.price);
            return sum + (a.dataset.pricing === 'hourly' ? p * hours : p);
        }, 0);

        const summary = () => {
            const extra = addonTotal();
            sumBase.textContent = money(basePrice);
            sumAddons.textContent = money(extra);
            sumAddons.parentElement.style.display = extra ? '' : 'none';
            sumTotal.textContent = money(basePrice + extra);
            sumWhen.textContent = date && time
                ? `${new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })} · ${time} · ${hours}h`
                : 'Pick a date and a time';
            submit.disabled = !(date && time);
        };

        let request = 0;
        const load = async () => {
            const id = ++request;
            time = null;
            timeInput.value = '';
            slotGrid.innerHTML = '<div class="slot-skeleton"></div>'.repeat(6);
            summary();
            const params = new URLSearchParams({ type: cfg.type, id: cfg.id, date, hours });
            addons.filter((a) => a.checked).forEach((a) => params.append('addons[]', a.value));
            try {
                const res = await fetch(cfg.slotsUrl + '?' + params, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (id !== request) return;
                basePrice = data.price || 0;
                slotGrid.innerHTML = '';
                if (!data.slots || !data.slots.length) {
                    slotGrid.innerHTML = '<div class="slot-empty"><i class="bi bi-moon-stars"></i> No free times on this day. Try another date or fewer hours.</div>';
                } else {
                    data.slots.forEach((s) => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'slot';
                        b.textContent = s.time;
                        b.title = s.label;
                        b.addEventListener('click', () => {
                            $$('.slot', slotGrid).forEach((x) => x.classList.remove('on'));
                            b.classList.add('on');
                            time = s.time;
                            timeInput.value = s.time;
                            summary();
                        });
                        slotGrid.appendChild(b);
                    });
                }
                summary();
            } catch (err) {
                slotGrid.innerHTML = '<div class="slot-empty">Could not load the times. Please try again.</div>';
            }
        };

        setHours(min);
        modal.addEventListener('modal:open', () => { if (!date) setDate(fmt(local(today))); });
        summary();

        // came back from the login page: open right away
        if (new URLSearchParams(location.search).get('book') === '1' && cfg.autoOpen === '1') {
            openModal(modal);
            history.replaceState(null, '', location.pathname);
        }
    });

    /* ---------- image upload previews ---------- */
    $$('[data-preview]').forEach((input) => {
        const box = $(input.dataset.preview);
        const zone = input.closest('.upload');
        const render = () => {
            box.innerHTML = '';
            Array.from(input.files).forEach((f) => {
                const div = document.createElement('div');
                div.className = 'thumb';
                const im = document.createElement('img');
                im.src = URL.createObjectURL(f);
                div.appendChild(im);
                box.appendChild(div);
            });
        };
        input.addEventListener('change', render);
        if (zone) {
            ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('drag'); }));
            ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, () => zone.classList.remove('drag')));
            zone.addEventListener('drop', (e) => { e.preventDefault(); input.files = e.dataTransfer.files; render(); });
        }
    });

    // single avatar preview
    $$('[data-avatar-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = $(input.dataset.avatarInput);
            if (input.files[0] && target) {
                const url = URL.createObjectURL(input.files[0]);
                if (target.tagName === 'IMG') target.src = url;
                else target.outerHTML = `<img src="${url}" class="${target.className}" id="${target.id}" alt="">`;
            }
        });
    });

    /* ---------- equipment repeater ---------- */
    $$('[data-repeater]').forEach((rep) => {
        const list = $('[data-repeater-list]', rep);
        const tpl = $('template', rep);
        let n = list.children.length;
        $('[data-repeater-add]', rep).addEventListener('click', () => {
            list.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', n++));
        });
        rep.addEventListener('click', (e) => {
            const rm = e.target.closest('[data-repeater-remove]');
            if (rm) rm.closest('.repeater-row').remove();
        });
    });

    /* ---------- AI description writer ---------- */
    $$('[data-ai]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const form = btn.closest('form');
            const target = $(btn.dataset.target, form);
            const name = ($('[name="name"]', form) || $('[name="title"]', form))?.value.trim();
            if (!name) { alert('Write a name first, then let AI describe it.'); return; }

            const highlights = [];
            $$('[name="facilities[]"]:checked', form).forEach((c) => highlights.push(c.value));
            $$('[name$="[item]"]', form).forEach((i) => { if (i.value) highlights.push(i.value); });
            $$('select[data-ai-fact]', form).forEach((s) => { if (s.value) highlights.push(s.options[s.selectedIndex].text); });
            $$('[name="services[]"]:checked', form).forEach((c) => highlights.push(c.dataset.label || c.value));

            const body = new FormData();
            body.append('kind', btn.dataset.ai);
            body.append('name', name);
            const city = $('[name="city"]', form)?.value;
            if (city) body.append('city', city);
            highlights.slice(0, 10).forEach((h) => body.append('highlights[]', h));

            btn.classList.add('loading');
            btn.disabled = true;
            try {
                const res = await fetch(btn.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body });
                const data = await res.json();
                target.value = '';
                const text = data.text || '';
                let k = 0;
                const type = () => {
                    target.value = text.slice(0, k += 3);
                    if (k < text.length) requestAnimationFrame(type);
                };
                type();
            } catch (err) {
                alert('The AI writer is not available right now.');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });
    });

    /* ---------- register: customer / studio owner ---------- */
    $$('[name="account_type"]').forEach((r) => r.addEventListener('change', () => {
        const provider = $('[name="account_type"]:checked')?.value === 'provider';
        $$('[data-provider-only]').forEach((el) => { el.hidden = !provider; });
    }));

    /* ---------- service form: a studio service is always "at the studio" ---------- */
    const studioSelect = $('[data-studio-select]');
    if (studioSelect) {
        const sync = () => {
            const linked = !!studioSelect.value;
            $$('[data-delivery] input').forEach((r) => {
                if (r.value === 'at_studio') { r.disabled = !linked; if (linked) r.checked = true; }
                else { r.disabled = linked; }
            });
            if (!linked && $('[data-delivery] input[value="at_studio"]').checked) {
                $('[data-delivery] input[value="at_client"]').checked = true;
            }
            $$('[data-studio-only]').forEach((el) => { el.hidden = !linked; });
        };
        studioSelect.addEventListener('change', sync);
        sync();
    }

    /* ---------- demo logins (local testing only) ---------- */
    $$('[data-demo]').forEach((b) => b.addEventListener('click', () => {
        const form = b.closest('form') || $('form');
        $('[name="email"]', form).value = b.dataset.demo;
        $('[name="password"]', form).value = 'password';
        $('[name="email"]', form).focus();
    }));
})();
