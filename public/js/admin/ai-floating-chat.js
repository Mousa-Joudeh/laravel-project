(() => {
    'use strict';

    const widget = document.getElementById('space-floating-assistant');
    if (!widget) return;

    const launcher = document.getElementById('space-chat-launcher');
    const panel = document.getElementById('space-chat-window');
    const closeButton = document.getElementById('space-chat-close');
    const conversation = document.getElementById('space-chat-conversation');
    const form = document.getElementById('space-chat-form');
    const input = document.getElementById('space-chat-question');
    const period = document.getElementById('space-chat-period');
    const send = document.getElementById('space-chat-send');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    function setOpen(open) {
        panel.hidden = !open;
        panel.setAttribute('aria-hidden', String(!open));
        launcher.setAttribute('aria-expanded', String(open));
        launcher.setAttribute('aria-label', open ? 'Hide SPACE admin assistant' : 'Open SPACE admin assistant');
        if (open) input.focus({ preventScroll: true });
        else launcher.focus({ preventScroll: true });
    }

    function appendMessage(type, text, notice = '') {
        const item = document.createElement('div');
        item.className = `space-chat-message space-chat-message-${type}`;

        const label = document.createElement('span');
        label.className = 'space-chat-message-name';
        label.textContent = type === 'user' ? 'You' : 'SPACE Assistant';

        const content = document.createElement('p');
        content.textContent = text;
        item.append(label, content);

        if (notice) {
            const detail = document.createElement('small');
            detail.className = 'space-chat-message-notice';
            detail.textContent = notice;
            item.appendChild(detail);
        }
        conversation.appendChild(item);
        conversation.scrollTop = conversation.scrollHeight;
        return item;
    }

    async function requestAnswer(question) {
        const response = await fetch(widget.dataset.chatUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf || ''
            },
            body: JSON.stringify({ question, days: Number(period.value) })
        });
        if (response.status === 419) throw new Error('Your session expired. Refresh the page and try again.');
        if (response.status === 401 || response.status === 403) throw new Error('Administrator access is required.');
        if (response.status === 429) throw new Error('Too many requests. Please try again in a minute.');
        if (!response.ok) throw new Error('The assistant is temporarily unavailable. Please try again.');
        return response.json();
    }

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            event.preventDefault();
            setOpen(false);
        }
    });

    widget.querySelectorAll('[data-space-chat-question]').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.dataset.spaceChatQuestion;
            input.focus();
        });
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const question = input.value.trim();
        if (question.length < 3 || question.length > 450 || send.disabled) return;

        appendMessage('user', question);
        input.value = '';
        send.disabled = true;
        input.disabled = true;
        const waiting = appendMessage('assistant', 'Checking SPACE data...');

        try {
            const result = await requestAnswer(question);
            waiting.querySelector('p').textContent = result.answer || 'No answer was available.';
            if (result.notice) {
                const notice = document.createElement('small');
                notice.className = 'space-chat-message-notice';
                notice.textContent = result.notice;
                waiting.appendChild(notice);
            }
        } catch (error) {
            waiting.querySelector('p').textContent = error.message;
        } finally {
            send.disabled = false;
            input.disabled = false;
            if (!panel.hidden) input.focus({ preventScroll: true });
            conversation.scrollTop = conversation.scrollHeight;
        }
    });
})();
