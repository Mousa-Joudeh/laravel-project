@if (Route::has('admin.ai.chat'))
<div class="space-floating-assistant" id="space-floating-assistant"
     data-chat-url="{{ route('admin.ai.chat') }}">
    <button class="space-chat-launcher" id="space-chat-launcher" type="button"
            aria-label="Open SPACE admin assistant" aria-controls="space-chat-window" aria-expanded="false">
        <i class="bi bi-chat-dots" aria-hidden="true"></i>
        <span class="space-chat-launcher-label">Ask SPACE</span>
    </button>

    <section class="space-chat-window" id="space-chat-window" role="dialog" aria-modal="false"
             aria-label="SPACE admin assistant" aria-hidden="true" hidden>
        <header class="space-chat-window-head">
            <span class="space-chat-avatar"><i class="bi bi-stars" aria-hidden="true"></i></span>
            <div class="space-chat-heading">
                <strong>SPACE Assistant</strong>
                <small>Admin intelligence · Read only</small>
            </div>
            <button class="space-chat-close" type="button" id="space-chat-close" aria-label="Close assistant">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="space-chat-conversation" id="space-chat-conversation" role="log"
             aria-live="polite" aria-relevant="additions" aria-label="Chat messages">
            <div class="space-chat-message space-chat-message-assistant">
                <span class="space-chat-message-name">SPACE Assistant</span>
                <p>Ask me about bookings, payments, partner approvals, reviews or traffic. I can only answer questions about SPACE operations.</p>
            </div>
        </div>

        <div class="space-chat-quick-prompts" aria-label="Suggested questions">
            <button type="button" data-space-chat-question="How are bookings performing?">Bookings</button>
            <button type="button" data-space-chat-question="What is our net processed revenue?">Revenue</button>
            <button type="button" data-space-chat-question="Which approvals need attention?">Approvals</button>
        </div>

        <form id="space-chat-form" class="space-chat-form">
            <label for="space-chat-question" class="space-chat-visually-hidden">Ask a question about SPACE</label>
            <textarea id="space-chat-question" name="question" maxlength="450" rows="2"
                      placeholder="Ask about SPACE..." required></textarea>
            <div class="space-chat-form-bottom">
                <label class="space-chat-period-label" for="space-chat-period">Period
                    <select id="space-chat-period" name="days" aria-label="Reporting period">
                        <option value="7">7 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="90">90 days</option>
                    </select>
                </label>
                <button type="submit" id="space-chat-send" aria-label="Send question">
                    <i class="bi bi-arrow-up" aria-hidden="true"></i>
                </button>
            </div>
        </form>

        <footer class="space-chat-footer">
            <span><i class="bi bi-shield-lock" aria-hidden="true"></i> No changes to records</span>
            <a href="{{ route('admin.ai.index') }}">Open Intelligence <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </footer>
    </section>
</div>
@endif
