@extends('layouts.dashboard')

@section('title', $studio->name)

@section('actions')
    <a href="{{ route('provider.studios.edit', $studio) }}" class="btn btn-dark btn-sm"><i class="bi bi-pencil"></i> Edit</a>
@endsection

@section('content')
    @if ($studio->status === 'pending')
        <div class="notice"><i class="bi bi-hourglass-split"></i><div><b>Waiting for approval</b>The admin will check the details and photos soon.</div></div>
    @elseif ($studio->status === 'rejected')
        <div class="notice bad"><i class="bi bi-x-octagon"></i><div><b>Not approved</b>{{ $studio->admin_note ?: 'Please review the details.' }} Edit the space to send it again.</div></div>
    @endif

    <div class="dash-head">
        <div>
            <h1>{{ $studio->name }}</h1>
            <p>{{ $studio->type?->name }} · {{ $studio->address }}, {{ $studio->city }}</p>
        </div>
        <div class="row">
            <x-status :value="$studio->status" />
            @if ($studio->isPublished())
                <a href="{{ route('studios.show', $studio) }}" target="_blank" class="btn btn-light btn-sm">View on website <i class="bi bi-box-arrow-up-right"></i></a>
            @endif
        </div>
    </div>

    <div class="thumbs" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin:0 0 14px">
        @foreach ($studio->images as $image)
            <div class="thumb" style="aspect-ratio:4/3"><img src="{{ $image->url() }}" alt=""></div>
        @endforeach
    </div>

    <div class="dash-grid">
        <div class="panel">
            <div class="panel-head"><h3>Details</h3></div>
            <div class="panel-body">
                <dl class="kv">
                    <dt>Category</dt><dd>{{ $studio->category?->name }}</dd>
                    <dt>Price</dt><dd>JD {{ (float) $studio->hourly_price }} per hour</dd>
                    <dt>Capacity</dt><dd>{{ $studio->capacity }} people · {{ $studio->area_sqm ? (int) $studio->area_sqm . ' m²' : '–' }}</dd>
                    <dt>Facilities</dt><dd>{{ implode(', ', $studio->facilities ?? []) ?: '–' }}</dd>
                    <dt>Description</dt><dd class="prose" style="font-weight:400">{{ $studio->description }}</dd>
                </dl>

                <h4 style="font-size:13px;margin:20px 0 10px">Equipment</h4>
                @if (! empty($studio->included_equipment))
                    <div class="equip-list">
                        @foreach ($studio->included_equipment as $item)
                            <div class="equip"><span class="qty">×{{ $item['qty'] }}</span><div><strong>{{ $item['item'] }}</strong><small>{{ $item['detail'] }}</small></div></div>
                        @endforeach
                    </div>
                @else
                    <p class="muted">Empty space, no equipment listed.</p>
                @endif
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-head"><h3>Linked</h3></div>
                <div class="panel-body flush">
                    @forelse ($studio->services as $service)
                        <a href="{{ route('provider.services.show', $service) }}" class="list-item"><i class="bi bi-person-video2 muted"></i><div class="grow"><b>{{ $service->title }}</b><small>Service</small></div><x-status :value="$service->status" /></a>
                    @empty
                    @endforelse
                    @foreach ($studio->packages as $package)
                        <a href="{{ route('provider.packages.show', $package) }}" class="list-item"><i class="bi bi-box2-heart muted"></i><div class="grow"><b>{{ $package->name }}</b><small>Package</small></div></a>
                    @endforeach
                    @if ($studio->services->isEmpty() && $studio->packages->isEmpty())
                        <div class="panel-body muted">No services or packages use this space yet.</div>
                    @endif
                </div>
            </div>

            <div class="panel" style="margin-top:14px">
                <div class="panel-head"><h3>Recent bookings</h3></div>
                <div class="panel-body flush">
                    @forelse ($bookings as $booking)
                        <a href="{{ route('provider.bookings.show', $booking) }}" class="list-item">
                            <span class="date-tile"><small>{{ $booking->starts_at->format('M') }}</small><b>{{ $booking->starts_at->format('d') }}</b></span>
                            <div class="grow"><b>{{ $booking->customer->name }}</b><small>{{ $booking->reference }}</small></div>
                            <x-status :value="$booking->status" />
                        </a>
                    @empty
                        <div class="panel-body muted">No bookings yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
