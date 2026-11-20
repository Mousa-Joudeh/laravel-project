@extends('layouts.admin')
@section('title', 'AI Intelligence')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/ai.css') }}">
@endpush
@section('content')
<div class="admin-page-heading ai-heading">
    <div>
        <span class="admin-kicker">OVERVIEW / INTELLIGENCE</span>
        <h1>SPACE Intelligence</h1>
        <p>Operational intelligence grounded in the marketplace database. Private, read-only, and administrator-only.</p>
    </div>
    <form method="GET" action="{{ route('admin.ai.index') }}" class="ai-period">
        <label for="ai-period-select">Reporting period</label>
        <select class="sp-select" name="period" id="ai-period-select" onchange="this.form.requestSubmit()">
            <option value="7" @selected($days === 7)>Last 7 days</option>
            <option value="30" @selected($days === 30)>Last 30 days</option>
            <option value="90" @selected($days === 90)>Last 90 days</option>
        </select>
    </form>
</div>

<div class="ai-workspace" id="space-ai" data-chat-url="{{ route('admin.ai.chat') }}" data-briefing-url="{{ route('admin.ai.briefing') }}" data-days="{{ $days }}">
    <div class="ai-intro">
        <span class="ai-status-dot" aria-hidden="true"></span>
        <strong>Database connected insights</strong>
        <span>Evidence from {{ $data['window']['from'] }} through {{ $data['window']['to'] }}. No automated account or booking changes.</span>
        <span class="ai-connection-status {{ $geminiReady ? 'ai-online' : '' }}">{{ $geminiReady ? 'Gemini configured · live connection not verified' : 'Gemini not configured · local insights available' }}</span>
    </div>

    <section class="ai-metric-grid" aria-label="SPACE summary metrics">
        <article class="ai-metric">
            <div class="ai-metric-top"><span>New bookings</span><i class="bi bi-calendar2-check" aria-hidden="true"></i></div>
            <strong>{{ number_format($data['bookings']['total']) }}</strong>
            <small>{{ $data['bookings']['change_pct'] !== null ? (($data['bookings']['change_pct'] > 0 ? '+' : '') . number_format($data['bookings']['change_pct'], 1) . '% vs prior window') : 'No prior-period benchmark' }}</small>
        </article>
        <article class="ai-metric">
            <div class="ai-metric-top"><span>Net processed payments</span><i class="bi bi-credit-card" aria-hidden="true"></i></div>
            <strong>{{ number_format($data['payments']['net'], 2) }} <span>JOD</span></strong>
            <small>Succeeded payments less succeeded refunds</small>
        </article>
        <article class="ai-metric">
            <div class="ai-metric-top"><span>Pending approvals</span><i class="bi bi-patch-check" aria-hidden="true"></i></div>
            <strong>{{ number_format($data['approvals']['total']) }}</strong>
            <small>Partners, studios and services · current backlog</small>
        </article>
        <article class="ai-metric">
            <div class="ai-metric-top"><span>Cancellation rate</span><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></div>
            <strong>{{ $data['bookings']['cancellation_rate_pct'] === null ? '—' : number_format($data['bookings']['cancellation_rate_pct'], 1) . '%' }}</strong>
            <small>{{ $data['bookings']['cancelled'] }} cancelled of {{ $data['bookings']['total'] }} new bookings</small>
        </article>
    </section>

    <div class="ai-columns">
        <div class="ai-primary-column">
            <section class="ai-panel ai-recommendation-panel" aria-labelledby="ai-recommendations-title">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-eyebrow">DECISION SUPPORT</span>
                        <h2 id="ai-recommendations-title">Operational recommendations</h2>
                        <p>Rule-based alerts with measurable evidence. Decisions remain with the administrator.</p>
                    </div>
                    <span class="ai-small-label">{{ count($data['recommendations']) }} signals</span>
                </div>
                <div class="ai-recommendations">
                    @foreach($data['recommendations'] as $recommendation)
                    <article class="ai-recommendation">
                        <span class="ai-priority ai-priority-{{ $recommendation['priority'] }}">{{ strtoupper($recommendation['priority']) }}</span>
                        <div class="ai-recommendation-content">
                            <h3>{{ $recommendation['title'] }}</h3>
                            <p class="ai-evidence">{{ $recommendation['evidence'] }}</p>
                            <p class="ai-next"><i class="bi bi-arrow-return-right" aria-hidden="true"></i>{{ $recommendation['action'] }}</p>
                        </div>
                        <a class="ai-rec-link" href="{{ route($recommendation['route']) }}" aria-label="Open related admin section for {{ $recommendation['title'] }}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </article>
                    @endforeach
                </div>
            </section>

            <section class="ai-panel ai-trend-panel" aria-labelledby="ai-trend-title">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-eyebrow">BOOKING ACTIVITY</span>
                        <h2 id="ai-trend-title">New bookings over time</h2>
                        <p>Creation dates, not the booked session dates.</p>
                    </div>
                    <span class="ai-small-label">{{ $days }} days</span>
                </div>
                @php($maxBookings = max(1, max(array_column($data['daily_bookings'], 'total'))))
                <div class="ai-bar-chart" role="img" aria-label="Daily new booking counts, maximum {{ $maxBookings }} bookings per day">
                    @foreach($data['daily_bookings'] as $i => $point)
                    <div class="ai-bar-column" title="{{ $point['label'] }}: {{ $point['total'] }} bookings">
                        <div class="ai-bar-fill" style="height: {{ $point['total'] > 0 ? max(4, round($point['total'] / $maxBookings * 100)) : 0 }}%"></div>
                        <small>{{ $days === 7 || $i % ($days === 30 ? 6 : 15) === 0 ? $point['label'] : '' }}</small>
                    </div>
                    @endforeach
                </div>
                @if($data['bookings']['total'] === 0)
                    <p class="ai-empty-label">No new booking records during the selected period.</p>
                @endif
            </section>

            <section class="ai-panel ai-briefing-panel" aria-labelledby="ai-briefing-title">
                <div class="ai-panel-head">
                    <div><span class="ai-eyebrow">EXECUTIVE BRIEFING</span><h2 id="ai-briefing-title">Data-led interpretation</h2><p>Request a concise summary for your current reporting window.</p></div>
                    <button type="button" class="ai-action" id="ai-generate-briefing"><i class="bi bi-stars" aria-hidden="true"></i> Generate briefing</button>
                </div>
                <div id="ai-briefing-result" class="ai-briefing-placeholder" role="status" aria-live="polite">No AI briefing generated yet. The verified recommendations above are available without an API key.</div>
            </section>
        </div>

        <aside class="ai-secondary-column" aria-label="Assistant and operational context">
            <section class="ai-panel ai-chat-panel" aria-labelledby="ai-chat-title">
                <div class="ai-chat-head">
                    <div class="ai-chat-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></div>
                    <div><span class="ai-eyebrow">SPACE OPERATIONS ONLY</span><h2 id="ai-chat-title">Ask SPACE Assistant</h2><p>Answers use aggregated platform facts. No personal data or external questions.</p></div>
                </div>
                <div class="ai-chat-thread" id="ai-chat-thread" role="log" aria-label="Assistant messages" aria-live="polite" aria-relevant="additions">
                    <div class="ai-bubble ai-bubble-assistant"><span class="ai-bubble-label">SPACE Assistant</span><p>Ask about bookings, payment performance, approval queues, reviews or traffic. I cannot change records or answer unrelated questions.</p></div>
                </div>
                <div class="ai-suggestions" aria-label="Suggested questions">
                    <button type="button" data-ai-question="Summarize booking performance and cancellations.">Bookings</button>
                    <button type="button" data-ai-question="What is our payment success rate and net revenue?">Payments</button>
                    <button type="button" data-ai-question="Which approvals need attention?">Approvals</button>
                    <button type="button" data-ai-question="What do our reviews show?">Reviews</button>
                </div>
                <form id="ai-chat-form" class="ai-chat-form">
                    <label for="ai-question" class="sp-sr-only">Question about SPACE operations</label>
                    <textarea id="ai-question" maxlength="450" rows="2" required placeholder="Ask a question about SPACE metrics..." aria-describedby="ai-chat-disclaimer"></textarea>
                    <div class="ai-chat-form-bottom"><span id="ai-chat-disclaimer">Read-only · Admin access</span><button class="ai-chat-send" type="submit" aria-label="Send question"><i class="bi bi-arrow-up" aria-hidden="true"></i></button></div>
                </form>
            </section>

            <section class="ai-panel ai-context-panel" aria-labelledby="ai-context-title">
                <div class="ai-panel-head"><div><span class="ai-eyebrow">DATA CONTEXT</span><h2 id="ai-context-title">What the assistant can see</h2></div></div>
                <dl class="ai-context-stats">
                    <div><dt>Active studios</dt><dd>{{ number_format($data['catalog']['approved_active_studios']) }}</dd></div>
                    <div><dt>Active services</dt><dd>{{ number_format($data['catalog']['approved_active_services']) }}</dd></div>
                    <div><dt>Active packages</dt><dd>{{ number_format($data['catalog']['active_packages']) }}</dd></div>
                    <div><dt>New customer accounts</dt><dd>{{ number_format($data['users']['new_customers']) }}</dd></div>
                    <div><dt>Average new review rating</dt><dd>{{ $data['reviews']['average_rating'] === null ? '—' : number_format($data['reviews']['average_rating'], 2) . '/5' }}</dd></div>
                    <div><dt>Recorded public page views</dt><dd>{{ number_format($data['traffic']['recorded_public_page_views']) }}</dd></div>
                </dl>
                <p class="ai-context-disclaimer"><i class="bi bi-shield-lock" aria-hidden="true"></i> Only aggregate counts, amounts and operational summaries are shared with Gemini. No customer emails, phone numbers, passwords or raw records.</p>
            </section>
        </aside>
    </div>
    <p class="ai-footer-note">Interpretation is advisory. No automated approvals, cancellations or refunds. Traffic counts depend on active visit tracking; inactive packages do not automatically imply pending approval.</p>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/ai.js') }}" defer></script>
@endpush
