@extends('layouts.dashboard')

@section('title', $package->name)

@section('actions')
    <a href="{{ route('provider.packages.edit', $package) }}" class="btn btn-dark btn-sm"><i class="bi bi-pencil"></i> Edit</a>
@endsection

@section('content')
    <div class="dash-head">
        <div>
            <h1>{{ $package->name }}</h1>
            <p>{{ $package->typeLabel() }} package · JD {{ number_format($package->price, 0) }} · {{ $package->min_hours }}–{{ $package->max_hours }} hours</p>
        </div>
        @if ($package->is_active)
            <a href="{{ route('packages.show', $package) }}" target="_blank" class="btn btn-light btn-sm">View on website <i class="bi bi-box-arrow-up-right"></i></a>
        @endif
    </div>

    <div class="thumbs" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin:0 0 14px">
        @foreach ($package->images as $image)
            <div class="thumb" style="aspect-ratio:4/3"><img src="{{ $image->url() }}" alt=""></div>
        @endforeach
    </div>

    <div class="dash-grid">
        <div class="panel">
            <div class="panel-head"><h3>Inside this package</h3></div>
            <div class="panel-body">
                <ul class="includes-list">
                    @if ($package->studio)
                        <li><i class="bi bi-house-door"></i> <span class="grow"><b>{{ $package->studio->name }}</b> · space</span></li>
                    @endif
                    @foreach ($package->services as $service)
                        <li><i class="bi bi-person-video2"></i> <span class="grow"><b>{{ $service->title }}</b> · {{ $service->specialtyLabel() }}</span> <x-status :value="$service->status" /></li>
                    @endforeach
                </ul>
                <p class="prose" style="margin-top:16px">{{ $package->description }}</p>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head"><h3>Performance</h3></div>
            <div class="panel-body">
                <dl class="kv">
                    <dt>Bookings</dt><dd>{{ $bookingsCount }}</dd>
                    <dt>Status</dt><dd>{{ $package->is_active ? 'Active' : 'Off' }}</dd>
                    <dt>Runs</dt><dd>{{ $package->start_date?->format('j M Y') ?? 'Now' }} → {{ $package->end_date?->format('j M Y') ?? 'no end' }}</dd>
                </dl>
                <h4 style="font-size:13px;margin:18px 0 8px">Latest reviews</h4>
                @forelse ($reviews as $review)
                    <div class="list-item" style="padding:10px 0">
                        <div class="grow"><b>{{ $review->booking->customer->name }}</b><small>{{ \Illuminate\Support\Str::limit($review->comment, 70) }}</small></div>
                        <x-stars :value="$review->rating" class="accent" />
                    </div>
                @empty
                    <p class="muted">No reviews yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
