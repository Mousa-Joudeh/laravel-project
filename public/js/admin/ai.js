(() => {
    'use strict';

    const root = document.getElementById('space-ai');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const days = Number(root.dataset.days);
    const chatForm = document.getElementById('ai-chat-form');
    const questionInput = document.getElementById('ai-question');
    const thread = document.getElementById('ai-chat-thread');
    const sendButton = chatForm.querySelector('button[type="submit"]');
    const briefingButton = document.getElementById('ai-generate-briefing');
    const briefingResult = document.getElementById('ai-briefing-result');

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        if (response.status === 419) throw new Error('Your session has expired. Refresh this page and try again.');
        if (response.status === 401 || response.status === 403) throw new Error('Administrator access is required.');
        if (response.status === 429) throw new Error('Request limit reached. Please try again in a minute.');
        if (!response.ok) throw new Error('Could not complete the request. Check your connection and Laravel logs.');
        return response.json();
    }

    function addMessage(kind, message, notice) {
        const bubble = document.createElement('div');
        bubble.className = `ai-bubble ai-bubble-${kind}`;
        const label = document.createElement('span');
        label.className = 'ai-bubble-label';
        label.textContent = kind === 'user' ? 'You' : 'SPACE Assistant';
        const body = document.createElement('p');
        body.textContent = message;
        bubble.append(label, body);
        if (notice) {
            const note = document.createElement('span');
            note.className = 'ai-bubble-notice';
            note.textContent = notice;
            bubble.appendChild(note);
        }
        thread.appendChild(bubble);
        thread.scrollTop = thread.scrollHeight;
        return bubble;
    }

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const question = questionInput.value.trim();
        if (question.length < 3 || sendButton.disabled) return;
        addMessage('user', question);
        questionInput.value = '';
        sendButton.disabled = true;
        const pending = addMessage('assistant', 'Checking SPACE metrics...');
        try {
            const result = await postJson(root.dataset.chatUrl, { question, days });
            pending.querySelector('p').textContent = result.answer || 'No response was available.';
            if (result.notice) {
                const notice = document.createElement('span');
                notice.className = 'ai-bubble-notice';
                notice.textContent = result.notice;
                pending.appendChild(notice);
            }
        } catch (error) {
            pending.querySelector('p').textContent = error.message;
        } finally {
            sendButton.disabled = false;
            questionInput.focus();
            thread.scrollTop = thread.scrollHeight;
        }
    });

    document.querySelectorAll('[data-ai-question]').forEach((button) => {
        button.addEventListener('click', () => {
            questionInput.value = button.dataset.aiQuestion;
            questionInput.focus();
        });
    });

    briefingButton.addEventListener('click', async () => {
        briefingButton.disabled = true;
        briefingResult.textContent = 'Preparing the operational briefing...';
        try {
            const result = await postJson(root.dataset.briefingUrl, { days });
            briefingResult.textContent = (result.answer || 'No briefing available.') + (result.notice ? `\n\n${result.notice}` : '');
            briefingResult.classList.add('is-ready');
        } catch (error) {
            briefingResult.textContent = error.message;
        } finally {
            briefingButton.disabled = false;
        }
    });
})();
