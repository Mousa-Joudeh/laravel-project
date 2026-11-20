@extends('layouts.dashboard')

@section('title', $service->title)

@section('actions')
    <a href="{{ route('provider.services.edit', $service) }}" class="btn btn-dark btn-sm"><i class="bi bi-pencil"></i> Edit</a>
@endsection

@section('content')
    @if ($service->status === 'pending')
        <div class="notice"><i class="bi bi-hourglass-split"></i><div><b>Waiting for approval</b>The admin will review this service soon.</div></div>
    @elseif ($service->status === 'rejected')
        <div class="notice bad"><i class="bi bi-x-octagon"></i><div><b>Not approved</b>{{ $service->admin_note }} Edit it to send it again.</div></div>
    @endif

    <div class="dash-head">
        <div>
            <h1>{{ $service->title }}</h1>
            <p>{{ $service->specialtyLabel() }} · {{ $service->deliveryLabel() }}</p>
        </div>
        <div class="row">
            <x-status :value="$service->status" />
            @if ($service->status === 'approved' && $service->is_active)
                <a href="{{ route('services.show', $service) }}" target="_blank" class="btn btn-light btn-sm">View on website <i class="bi bi-box-arrow-up-right"></i></a>
            @endif
        </div>
    </div>

    <div class="dash-grid">
        <div class="panel">
            <div class="panel-body" style="display:grid;grid-template-columns:180px 1fr;gap:20px">
                <div class="media" style="aspect-ratio:1"><img src="{{ $service->imageUrl() }}" alt=""></div>
                <dl class="kv">
                    <dt>Price</dt><dd>JD {{ (float) $service->price }} {{ $service->priceSuffix() }}</dd>
                    <dt>Hours</dt><dd>{{ $service->min_hours }} – {{ $service->max_hours }}</dd>
                    <dt>Booked as</dt><dd>{{ ['core' => 'On its own', 'addon' => 'Add-on only', 'both' => 'On its own or add-on'][$service->kind] }}</dd>
                    <dt>Linked space</dt><dd>{{ $service->studio?->name ?? 'None' }}@if ($service->studio && $service->price_includes_studio) <span class="tag">price includes space</span>@endif</dd>
                    <dt>Team</dt><dd>{{ $service->resource_group ?: '–' }}</dd>
                    <dt>Bookings</dt><dd>{{ $bookingsCount }}</dd>
                </dl>
            </div>
            <div class="panel-body" style="border-top:1px solid var(--line)"><p class="prose">{{ $service->description }}</p></div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Latest reviews</h3></div>
            <div class="panel-body flush">
                @forelse ($reviews as $review)
                    <div class="list-item">
                        <div class="grow"><b>{{ $review->booking->customer->name }}</b><small>{{ \Illuminate\Support\Str::limit($review->comment, 80) }}</small></div>
                        <x-stars :value="$review->rating" class="accent" />
                    </div>
                @empty
                    <div class="panel-body muted">No reviews yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
