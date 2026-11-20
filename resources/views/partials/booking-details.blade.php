{{-- booking details shared by the provider and the admin pages --}}
<div class="dash-grid">
    <div>
        <div class="panel">
            <div class="panel-head"><h3>Items</h3><span class="mono muted">{{ $booking->reference }}</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Item</th><th>Type</th><th>Unit price</th><th style="text-align:right">Line total</th></tr></thead>
                    <tbody>
                        @foreach ($booking->items as $item)
                            <tr>
                                <td><b>{{ $item->item_name }}</b></td>
                                <td><span class="tag">{{ ucfirst($item->item_type) }}</span></td>
                                <td class="muted">JD {{ number_format($item->unit_price, 2) }} {{ $item->pricing_type === 'hourly' ? '/ h' : '' }}</td>
                                <td style="text-align:right">{{ $item->is_included ? 'Included' : 'JD ' . number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr><td colspan="3"><b>Total</b></td><td style="text-align:right"><b>JD {{ number_format($booking->total_price, 2) }}</b></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel" style="margin-top:14px">
            <div class="panel-head"><h3>Payments</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Type</th><th>Method</th><th>Amount</th><th>Status</th><th>Processed</th></tr></thead>
                    <tbody>
                        @forelse ($booking->transactions as $transaction)
                            <tr>
                                <td>{{ ucfirst($transaction->type) }}</td>
                                <td>{{ ucfirst($transaction->provider) }}</td>
                                <td>JD {{ number_format($transaction->amount, 2) }}</td>
                                <td><x-status :value="$transaction->status" /></td>
                                <td class="muted">{{ $transaction->processed_at?->format('j M Y, H:i') ?? '–' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted">No payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><h3>Session</h3><x-status :value="$booking->status" /></div>
            <div class="panel-body">
                <dl class="kv">
                    <dt>Date</dt><dd>{{ $booking->starts_at->format('l, j F Y') }}</dd>
                    <dt>Time</dt><dd>{{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }} ({{ $booking->hours() }}h)</dd>
                    <dt>Mode</dt><dd>{{ ucfirst($booking->mode) }}</dd>
                    <dt>Booked</dt><dd>{{ $booking->created_at->format('j M Y, H:i') }}</dd>
                    @if ($booking->cancelled_at)
                        <dt>Cancelled</dt><dd>{{ $booking->cancelled_at->format('j M Y, H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="panel" style="margin-top:14px">
            <div class="panel-head"><h3>Customer</h3></div>
            <div class="panel-body">
                <div class="row">
                    @if ($booking->customer->avatarUrl())
                        <img src="{{ $booking->customer->avatarUrl() }}" class="avatar avatar-lg" alt="">
                    @else
                        <span class="avatar avatar-lg">{{ $booking->customer->initials() }}</span>
                    @endif
                    <div>
                        <b>{{ $booking->customer->name }}</b>
                        <div class="muted">{{ $booking->customer->email }}</div>
                        <div class="muted">{{ $booking->customer->phone }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($booking->review)
            <div class="panel" style="margin-top:14px">
                <div class="panel-head"><h3>Review</h3><x-stars :value="$booking->review->rating" class="accent" /></div>
                <div class="panel-body"><p class="prose">{{ $booking->review->comment ?: 'No comment.' }}</p></div>
            </div>
        @endif
    </div>
</div>
