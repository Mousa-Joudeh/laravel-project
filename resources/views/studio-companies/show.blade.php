@extends('layouts.user')

@section('title', $profile->company_name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/studios.css') }}">
    <link rel="stylesheet" href="{{ asset('css/packages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/studio-companies.css') }}">
@endpush

@section('content')
    <div class="container company-details">
        <header>
            <img
                class="company-cover"
                src="{{ $profile->coverUrl() ?? $spaces->first()?->coverUrl() ?? asset('images/placeholder.svg') }}"
                alt=""
            >

            <div class="company-heading">
                <div class="company-logo" aria-hidden="true">
                    @if ($profile->logoUrl())
                        <img src="{{ $profile->logoUrl() }}" alt="">
                    @else
                        {{ $profile->initials() }}
                    @endif
                </div>

                <div class="company-identity">
                    <h1>{{ $profile->company_name }}</h1>

                    <ul class="company-meta">
                        @if ($profile->city)
                            <li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $profile->city }}</li>
                        @endif

                        @if ($profile->instagram)
                            <li><i class="bi bi-instagram" aria-hidden="true"></i> {{ $profile->instagram }}</li>
                        @endif

                        <li><i class="bi bi-patch-check" aria-hidden="true"></i> Verified studio</li>
                    </ul>
                </div>

                @if ($spaces->isNotEmpty())
                    <a class="btn btn-dark" href="#company-spaces">See spaces</a>
                @endif
            </div>
        </header>

        <div class="company-overview">
            @if ($profile->bio || $profile->tagline)
                <p>{{ $profile->bio ?: $profile->tagline }}</p>
            @endif

            <ul class="company-totals" aria-label="Published listings">
                <li><strong>{{ $spaces->count() }}</strong> Spaces</li>
                <li><strong>{{ $services->count() }}</strong> Services</li>
                <li><strong>{{ $packages->count() }}</strong> Packages</li>
            </ul>
        </div>

        <section id="company-spaces" class="company-section">
            <h2>Spaces</h2>

            <div class="company-listing-grid">
                @forelse ($spaces as $space)
                    @include('customer.favorites.space-card', ['studio' => $space])
                @empty
                    <p class="muted">No spaces published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="company-section">
            <h2>Services</h2>

            <div class="company-listing-grid">
                @forelse ($services as $service)
                    <a class="company-listing-card company-service-card" href="{{ route('services.show', $service->id) }}" aria-label="{{ $service->title }}">
                        <img class="company-listing-image" src="{{ $service->imageUrl() }}" alt="" loading="lazy">

                        <div class="company-listing-content">
                            <p class="company-service-specialty">{{ $service->specialtyLabel() }}</p>
                            <h3>{{ $service->title }}</h3>

                            @if ($service->description)
                                <p class="muted">{{ \Illuminate\Support\Str::limit($service->description, 100) }}</p>
                            @endif

                            <div class="company-card-meta">
                                <span class="muted">{{ $service->deliveryLabel() }}</span>
                                <span><strong>JD {{ $service->price + 0 }}</strong> {{ $service->priceSuffix() }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="muted">No services published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="company-section">
            <h2>Packages</h2>

            <div class="company-listing-grid">
                @forelse ($packages as $package)
                    @include('customer.packages.card', ['package' => $package])
                @empty
                    <p class="muted">No packages published yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/studios.js') }}"></script>
@endpush