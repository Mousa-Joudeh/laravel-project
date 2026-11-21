@extends('layouts.user')

@section('title', $studio->name)

@push('styles')
    <link href="{{ asset('css/studios.css') }}" rel="stylesheet">
    <link href="{{ asset('css/packages.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container">

        <div class="page-head">
            <a href="{{ route('studios.index') }}" class="back"><i class="bi bi-arrow-left"></i> All spaces</a>
            <div class="head-row">
                <div>
                    <h1>{{ $studio->name }}</h1>
                    <div class="detail-meta">
                        @if ($reviewsTotal > 0)
                            <span class="rating"><i class="bi bi-star-fill"></i> {{ $rating }} <small>({{ $reviewsTotal }} reviews)</small></span>
                        @endif
                        <span><i class="bi bi-geo-alt"></i> {{ $studio->address }}, {{ $studio->city }}</span>
                        @if ($studio->type)
                            <span><i class="bi {{ $studio->type->icon }}"></i> {{ $studio->type->name }}</span>
                        @endif
                    </div>
                </div>

                {{-- buttons for the owner provider --}}
                @if (Auth::check() && Auth::id() === $studio->provider_id)
                    <div class="head-actions">
                        <a href="{{ route('studioimages.index', ['studio_id' => $studio->id]) }}" class="btn btn-light btn-sm"><i class="bi bi-images"></i> Photos</a>
                        <a href="{{ route('studios.edit', $studio->id) }}" class="btn btn-dark btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                    </div>
                @endif

                {{-- buttons for the admin --}}
                @if (Auth::check() && Auth::user()->role === 'admin')
                    <div class="head-actions">
                        <span class="badge badge-{{ $studio->status }}">{{ ucfirst($studio->status) }}</span>
                        <a href="{{ route('studios.edit', $studio->id) }}" class="btn btn-dark btn-sm">Approve / Reject</a>
                    </div>
                @endif
            </div>
        </div>

       
        <div class="gallery">
            <div class="gallery-main">
                @if ($studio->images->count() > 0)
                    <img src="{{ asset('storage/' . $studio->images->first()->image_path) }}" alt="{{ $studio->name }}" id="main-photo">
                @else
                    <img src="{{ asset('images/placeholder.svg') }}" alt="" id="main-photo">
                @endif
            </div>
            @if ($studio->images->count() > 1)
                <div class="gallery-thumbs">
                    @foreach ($studio->images as $image)
                        <button type="button" class="thumb {{ $loop->first ? 'on' : '' }}" data-src="{{ asset('storage/' . $image->image_path) }}">
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="detail-grid">
            <div>
               
                <div class="highlights">
                    <div class="highlight"><i class="bi bi-people"></i><strong>{{ $studio->capacity }}</strong><span>people max</span></div>
                    <div class="highlight"><i class="bi bi-bounding-box"></i><strong>{{ $studio->area_sqm ? (int) $studio->area_sqm . ' m²' : '-' }}</strong><span>floor area</span></div>
                    <div class="highlight"><i class="bi bi-tag"></i><strong>{{ $studio->category->name }}</strong><span>category</span></div>
                    <div class="highlight"><i class="bi bi-cash"></i><strong>JD {{ number_format($studio->hourly_price, 0) }}</strong><span>per hour</span></div>
                </div>

                <section class="detail-section">
                    <h3>About this space</h3>
                    <p class="prose">{{ $studio->description }}</p>
                </section>

                
                <section class="detail-section">
                    <h3>Equipment included</h3>
                    @if (!empty($studio->included_equipment))
                        <div class="equip-list">
                            @foreach ($studio->included_equipment as $item)
                                <div class="equip">
                                    <span class="qty">×{{ $item['qty'] ?? 1 }}</span>
                                    <div>
                                        <strong>{{ $item['item'] ?? '' }}</strong>
                                        <small>{{ $item['detail'] ?? '' }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">This is an empty space: bring your own equipment.</p>
                    @endif
                </section>
                                {{-- ========== add-ons ========== --}}
                @if ($addons->count() > 0)
                    <section class="detail-section">
                        <h3>Add-ons</h3>
                        <p class="muted addons-note">Add any of these to your booking. The price is added to your total.</p>
                        <div class="addon-list">
                            @foreach ($addons as $addon)
                                <label class="addon">
                                    <input type="checkbox" class="addon-check" name="addons[]" value="{{ $addon->id }}" form="booking-form" data-price="{{ $addon->price }}" data-pricing="{{ $addon->pricing_type }}">
                                    <span class="addon-name">{{ $addon->title }}</span>
                                    <span class="price">JD {{ number_format($addon->price, 0) }} <small>{{ $addon->pricing_type === 'hourly' ? '/ hour' : 'fixed' }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endif

                
                @if (!empty($studio->facilities))
                    <section class="detail-section">
                        <h3>Facilities</h3>
                        <div class="facilities">
                            @foreach ($studio->facilities as $facility)
                                <span><i class="bi bi-check2"></i> {{ $facility }}</span>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- ========== services ========== --}}
                @if ($studio->services->count() > 0)
                    <section class="detail-section">
                        <h3>Services in this space</h3>
                        <div class="service-list">
                            @foreach ($studio->services as $service)
                                <div class="service-row">
                                    <div>
                                        <strong>{{ $service->title }}</strong>
                                        <small>{{ ucfirst(str_replace('_', ' ', $service->specialty)) }}</small>
                                    </div>
                                    <span class="price">JD {{ number_format($service->price, 0) }} <small>{{ $service->pricing_type === 'hourly' ? '/ hour' : 'fixed' }}</small></span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

               
                @if ($studio->provider->providerProfile)
                    <section class="detail-section">
                        <h3>Hosted by</h3>
                        <div class="provider-card">
                            @if ($studio->provider->providerProfile->logo)
                                <img src="{{ asset('storage/' . $studio->provider->providerProfile->logo) }}" alt="" class="provider-logo">
                            @endif
                            <div>
                                <strong>{{ $studio->provider->providerProfile->company_name }}</strong>
                                <small>{{ $studio->provider->providerProfile->tagline }}</small>
                            </div>
                        </div>
                    </section>
                @endif

               
                <section class="detail-section">
                    <h3>Ratings & reviews</h3>

                    @if ($reviewsTotal > 0)
                        <div class="rating-head">
                            <span class="rating-big">{{ $rating }}</span>
                            <div>
                                <span class="stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= round($rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </span>
                                <small class="muted">{{ $reviewsTotal }} reviews from real bookings</small>
                            </div>
                        </div>

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
                                            @for ($i = 1; $i <= $review->rating; $i++)
                                                <i class="bi bi-star-fill"></i>
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
                            <p>Be the first to book and share how it went.</p>
                        </div>
                    @endif
                </section>
            </div>

            
            <aside>
                   <div class="book-card" id="book-card" data-base="{{ $studio->hourly_price }}">
                    <div class="price-big">JD {{ number_format($studio->hourly_price, 0) }} <small>/ hour</small></div>
                    <div class="facts">
                        <div><span>Capacity</span><b>Up to {{ $studio->capacity }} people</b></div>
                        <div><span>City</span><b>{{ $studio->city }}</b></div>
                        <div><span>Category</span><b>{{ $studio->category->name }}</b></div>
                    </div>
                    <div class="facts card-addons" id="card-addons" hidden>
                        <div><span>Add-ons</span><b id="card-addons-price">+ JD 0</b></div>
                        <div><span>Total for 1 hour</span><b id="card-total">JD {{ number_format($studio->hourly_price, 0) }}</b></div>
                    </div>
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg btn-block"><i class="bi bi-calendar2-plus"></i> Book now</a>
                        <p class="note">You will log in first.</p>
                    @else
                        @if (Auth::user()->role === 'customer')
                         <button type="button" class="btn btn-primary btn-lg btn-block" id="open-booking"><i class="bi bi-calendar2-plus"></i> Book now</button>
                            @include('studios.booking-modal')
                        @else
                            <button type="button" class="btn btn-light btn-lg btn-block" disabled>Bookings are for customers</button>
                        @endif
                    @endguest
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/studios.js') }}"></script>
    <script src="{{ asset('js/booking-modal.js') }}"></script>
    @endpush