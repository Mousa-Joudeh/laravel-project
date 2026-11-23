@extends('layouts.user')

@section('title', $package->name)

@push('styles')
    <link href="{{ asset('css/studios.css') }}" rel="stylesheet">
    <link href="{{ asset('css/packages.css') }}" rel="stylesheet">
@endpush

@section('content')
    @php $profile = $package->studio->provider->providerProfile; @endphp

    <div class="container">

        
        <div class="page-head">
            <nav class="crumbs">
                <a href="{{ url('/') }}">Home</a> <i class="bi bi-chevron-right"></i>
                <a href="{{ route('packages.index') }}">Packages</a> <i class="bi bi-chevron-right"></i>
                <span>{{ $package->name }}</span>
            </nav>
            <span class="tag tag-accent">{{ ucfirst($package->type) }} package</span>
            <h1 class="pk-title">{{ $package->name }}</h1>
            <div class="detail-meta">
                @if ($package->reviews_total > 0)
                    <span class="rating"><i class="bi bi-star-fill"></i> {{ $package->rating }} <small>({{ $package->reviews_total }})</small></span>
                @endif
                @if ($profile)
                    <span><i class="bi bi-building"></i> {{ $profile->company_name }}</span>
                @endif
                <span><i class="bi bi-geo-alt"></i> {{ $package->studio->name }}, {{ $package->studio->city }}</span>
            </div>
        </div>

        
        <div class="pk-gallery">
            <img src="{{ $photos[0] }}" alt="{{ $package->name }}" class="pk-main" id="main-photo">
            @if (count($photos) > 1)
                <div class="pk-side">
                    @foreach (array_slice($photos, 1, 2) as $photo)
                        <img src="{{ $photo }}" alt="" class="pk-small">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="detail-grid">
            <div>
                
                <div class="highlights">
                    <div class="highlight"><i class="bi bi-clock"></i><strong>{{ $package->min_hours }}–{{ $package->max_hours }}h</strong><span>session length</span></div>
                    <div class="highlight"><i class="bi bi-person-video2"></i><strong>{{ $package->services->count() }}</strong><span>services included</span></div>
                    <div class="highlight"><i class="bi bi-house-door"></i><strong>Yes</strong><span>studio included</span></div>
                    <div class="highlight"><i class="bi bi-receipt"></i><strong>{{ $package->pricing_type === 'fixed' ? 'Fixed' : 'Hourly' }}</strong><span>one clear price</span></div>
                </div>

                <section class="detail-section">
                    <h3>About this package</h3>
                    <p class="prose">{{ $package->description }}</p>
                </section>

                
                <section class="detail-section">
                    <h3>What is included</h3>
                    <ul class="includes-list">
                        <li>
                            <i class="bi bi-house-door"></i>
                            <a href="{{ route('studios.show', $package->studio->id) }}" class="grow"><b>{{ $package->studio->name }}</b> <span class="muted">· the space, {{ $package->studio->city }}</span></a>
                            <span class="tag">Included</span>
                        </li>
                        @foreach ($package->services as $service)
                            <li>
                                <i class="bi bi-person-video2"></i>
                                <span class="grow"><b>{{ $service->title }}</b> <span class="muted">· {{ ucfirst(str_replace('_', ' ', $service->specialty)) }}</span></span>
                                <span class="tag">Included</span>
                            </li>
                        @endforeach
                        <li><i class="bi bi-images"></i> <span class="grow">Edited photos delivered in a private online gallery</span></li>
                    </ul>
                </section>

                
                @if ($profile)
                    <section class="detail-section">
                        <h3>Offered by</h3>
                        <div class="provider-card">
                            @if ($profile->logo)
                                <img src="{{ asset('storage/' . $profile->logo) }}" alt="" class="provider-logo">
                            @endif
                            <div class="grow">
                                <strong>{{ $profile->company_name }}</strong>
                                <small>{{ $profile->tagline }}</small>
                            </div>
                            <a href="{{ route('studios.show', $package->studio->id) }}" class="btn btn-light btn-sm">View studio <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </section>
                @endif

                
                <section class="detail-section">
                    <h3>Ratings & reviews</h3>

                    @if ($package->reviews_total > 0)
                        <div class="rating-head">
                            <span class="rating-big">{{ $package->rating }}</span>
                            <div>
                                <span class="stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= round($package->rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </span>
                                <small class="muted">{{ $package->reviews_total }} reviews from real bookings</small>
                            </div>
                        </div>
                    @endif

                    @if (Auth::check() && Auth::user()->role === 'customer')
                        <p class="note-line"><i class="bi bi-info-circle"></i> You can rate this after one of your bookings here is completed. <a href="{{ route('reviews.create') }}" class="accent">Write a review</a></p>
                    @endif

                    @if ($reviews->count() > 0)
                        <div class="reviews">
                            @foreach ($reviews as $review)
                                <article class="review">
                                    <div class="review-head">
                                        @if ($review->booking->customer->avatar)
                                            <img src="{{ asset('storage/' . $review->booking->customer->avatar) }}" class="avatar" alt="">
                                        @else
                                            <span class="avatar">{{ strtoupper(substr($review->booking->customer->name, 0, 1)) }}</span>
                                        @endif
                                        <div>
                                            <strong>{{ $review->booking->customer->name }}</strong>
                                            <small>{{ $review->created_at->format('M Y') }}</small>
                                        </div>
                                        <span class="stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                            @endfor
                                        </span>
                                    </div>
                                    <p>{{ $review->comment }}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">
                            <i class="bi bi-chat-heart"></i>
                            <h4>No reviews yet</h4>
                        </div>
                    @endif
                </section>
            </div>

            
            <aside>
                <div class="book-card">
                    <div class="price-big">JD {{ number_format($package->price, 0) }} <small>/ package</small></div>
                    @if ($package->reviews_total > 0)
                        <span class="rating"><i class="bi bi-star-fill"></i> {{ $package->rating }} <small>({{ $package->reviews_total }})</small></span>
                    @endif

                    <div class="facts">
                        <div><span>Type</span><b>{{ ucfirst($package->type) }}</b></div>
                        <div><span>Studio</span><b>{{ $package->studio->name }}</b></div>
                        <div><span>Session</span><b>{{ $package->min_hours }} to {{ $package->max_hours }} hours</b></div>
                        <div><span>Services</span><b>{{ $package->services->count() }} included</b></div>
                    </div>

                    @guest
                        <a href="{{ route('packages.booking.continue', ['package' => $package->id]) }}" class="btn btn-primary btn-lg btn-block"><i class="bi bi-calendar2-plus"></i> Book now</a>
                        <p class="note">You will log in first.</p>
                    @else
                        @if (Auth::user()->role === 'customer')
                            <button type="button" class="btn btn-primary btn-lg btn-block" id="open-booking"><i class="bi bi-calendar2-plus"></i> Book now</button>
                        @else
                            <button type="button" class="btn btn-light btn-lg btn-block" disabled>Bookings are for customers</button>
                        @endif
                    @endguest

                    <p class="note"><i class="bi bi-shield-check"></i> Pay at the studio · free cancellation up to 24h before</p>
                </div>
            </aside>
        </div>

    
        @if ($morePackages->count() > 0)
            <section class="more-section">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><b>●</b> Also popular</span>
                        <h2 class="section-title">More <span class="serif">{{ $package->type }}</span> packages</h2>
                    </div>
                    <a href="{{ route('packages.index', ['types' => [$package->type]]) }}" class="link-more">View more <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="grid grid-3">
                    @foreach ($morePackages as $item)
                        @include('customer.packages.card', ['package' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- the booking window (customers only) --}}
    @if (Auth::check() && Auth::user()->role === 'customer')
        @include('customer.packages.booking-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/packages.js') }}"></script>
        <script src="{{ asset('js/booking-modal.js') }}"></script>
@endpush