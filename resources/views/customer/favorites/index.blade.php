@extends('layouts.user')

@section('title', 'Favorites')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/studios.css') }}">
    <link rel="stylesheet" href="{{ asset('css/packages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/studio-companies.css') }}">
    <style>
        .favorites-section {
            margin: 0;
            padding: 0;
        }

        .favorites-section + .favorites-section {
            margin-top: 64px;
        }

        .favorites-section > h2.section-title {
            margin: 0 0 16px;
            font-size: 18px;
        }

        .favorites-section > h2.section-title .muted {
            font-weight: 400;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <header class="page-head">
            <h1>Your <span class="serif">favorites</span></h1>
            <p>Spaces, packages and services you saved for later.</p>
        </header>

        @if ($favorites->isEmpty())
            <p class="muted">Nothing saved yet. Tap the heart on a space, package or service to save it here.</p>
        @endif

        @if ($spaces->isNotEmpty())
            <section class="section-tight favorites-section" aria-labelledby="favorite-spaces">
                <h2 id="favorite-spaces" class="section-title">
                    Spaces <span class="muted">· {{ $spaces->count() }}</span>
                </h2>

                <div class="grid grid-4">
                    @foreach ($spaces as $favorite)
                        @include('customer.favorites.space-card', ['studio' => $favorite->favoritable])
                    @endforeach
                </div>
            </section>
        @endif
        @if ($packages->isNotEmpty())
            <section class="section-tight favorites-section" aria-labelledby="favorite-packages">
                <h2 id="favorite-packages" class="section-title">
                    Packages <span class="muted">· {{ $packages->count() }}</span>
                </h2>

                <div class="grid grid-4">
                    @foreach ($packages as $favorite)
                        @include('customer.packages.card', ['package' => $favorite->favoritable])
                    @endforeach
                </div>
            </section>
        @endif
        @if ($services->isNotEmpty())
            <section class="company-details favorites-section company-section" aria-labelledby="favorite-services">
                <h2 id="favorite-services" class="section-title">
                    Services <span class="muted">· {{ $services->count() }}</span>
                </h2>

                <div class="company-listing-grid">
                    @foreach ($services as $favorite)
                        @php($service = $favorite->favoritable)

                        <a class="company-listing-card company-service-card"
                        href="{{ route('services.show', $service->id) }}">
                            <img class="company-listing-image"
                                src="{{ $service->imageUrl() }}"
                                alt=""
                                loading="lazy">

                            <div class="company-listing-content">
                                @if ($service->specialty)
                                    <p class="company-service-specialty">{{ $service->specialtyLabel() }}</p>
                                @endif

                                <h3>{{ $service->title }}</h3>

                                @if ($service->description)
                                    <p class="muted">{{ \Illuminate\Support\Str::limit($service->description, 85) }}</p>
                                @endif

                                <div class="company-card-meta">
                                    <span class="muted">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        {{ $service->deliveryLabel() }}
                                    </span>

                                    <span>
                                        <strong>JD {{ $service->price + 0 }}</strong>
                                        <span class="muted">
                                            {{ $service->pricing_type === 'hourly' ? '/ hour' : $service->priceSuffix() }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/studios.js') }}" defer></script>
@endpush