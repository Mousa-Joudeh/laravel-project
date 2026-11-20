@extends('layouts.user')

@section('title', $profile->company_name)

@push('styles')
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
                    <article class="company-listing-card company-space-card">
                        <div class="company-space-photo slider">
                            <a class="slides" href="{{ route('studios.show', $space->id) }}"
                            aria-label="View {{ $space->name }}">
                                @forelse ($space->images as $image)
                                    <img src="{{ $image->url() }}" alt="" loading="lazy">
                                @empty
                                    <img src="{{ $space->coverUrl() }}" alt="" loading="lazy">
                                @endforelse
                            </a>

                            @if ($space->type)
                                <span class="company-card-label">{{ $space->type->name }}</span>
                            @endif

                            @if ($space->images->count() > 1)
                                <button class="slide-btn prev" type="button" aria-label="Previous photo">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </button>
                                <button class="slide-btn next" type="button" aria-label="Next photo">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>

                        <div class="company-listing-content">
                            <h3><a href="{{ route('studios.show', $space->id) }}">{{ $space->name }}</a></h3>
                            <div class="company-card-meta">
                                <span class="muted">
                                    @if ($space->city)
                                        <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $space->city }}
                                    @endif
                                </span>
                                <span><strong>JD {{ $space->hourly_price + 0 }}</strong> / hour</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="muted">No spaces published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="company-section">
            <h2>Services</h2>

            <div class="company-listing-grid">
                @forelse ($services as $service)
                    <article class="company-listing-card company-service-card">
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
                    </article>
                @empty
                    <p class="muted">No services published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="company-section">
            <h2>Packages</h2>

            <div class="company-listing-grid">
                @forelse ($packages as $package)
                    <article class="company-listing-card company-package-card">
                        <div class="company-package-photo">
                            <img class="company-listing-image" src="{{ $package->coverUrl() }}" alt="" loading="lazy">
                            <span class="company-card-label">{{ $package->typeLabel() }}</span>
                        </div>

                        <div class="company-listing-content">
                            <h3>{{ $package->name }}</h3>

                            @if ($package->studio)
                                <p class="muted">
                                    <i class="bi bi-camera" aria-hidden="true"></i> {{ $package->studio->name }}
                                </p>
                            @endif

                            <p class="company-package-price"><strong>JD {{ $package->price + 0 }}</strong></p>
                        </div>
                    </article>
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