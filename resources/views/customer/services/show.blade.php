@extends('layouts.user')

@section('title', $service->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/studios.css') }}">
    <link rel="stylesheet" href="{{ asset('css/packages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/service-details.css') }}">
@endpush

@section('content')
    @php
        $studio = $service->studio;
        $profile = $studio?->provider?->providerProfile;

        $companyUrl = $profile?->isApproved() && $profile->slug
            ? route('studio-companies.show', ['slug' => $profile->slug])
            : null;

        $photos = collect([$service->image ? $service->imageUrl() : null])
            ->merge($studio?->images->sortBy('sort_order')->map(fn ($image) => $image->url()) ?? [])
            ->filter()
            ->unique()
            ->take(5)
            ->values();

        $packages = $service->packages()
            ->where('packages.is_active', true)
            ->whereHas('studio', fn ($query) => $query
                ->where('is_active', true)
                ->where('status', 'approved'))
            ->with([
                'images' => fn ($query) => $query->orderBy('sort_order'),
                'studio.images' => fn ($query) => $query
                    ->orderByDesc('is_cover')
                    ->orderBy('sort_order'),
            ])
            ->orderBy('packages.name')
            ->get();

        if ($packages->isNotEmpty()) {
            $packageRatings = \Illuminate\Support\Facades\DB::table('booking_items as bi')
                ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
                ->join('reviews as r', 'r.booking_id', '=', 'b.id')
                ->whereIn('bi.package_id', $packages->modelKeys())
                ->whereNull('bi.deleted_at')
                ->whereNull('b.deleted_at')
                ->whereNull('r.deleted_at')
                ->selectRaw('bi.package_id, AVG(r.rating) as average, COUNT(DISTINCT r.booking_id) as total')
                ->groupBy('bi.package_id')
                ->get()
                ->keyBy('package_id');

            foreach ($packages as $package) {
                $stats = $packageRatings->get($package->id);

                $package->cover_url = $package->images->first()?->url()
                    ?? $package->studio?->images->first()?->url()
                    ?? asset('images/placeholder.svg');

                $package->rating = round((float) ($stats->average ?? 0), 1);
                $package->reviews_total = (int) ($stats->total ?? 0);
            }
        }

        $reviewQuery = \App\Models\Review::query()
            ->whereHas('booking.items', fn ($query) => $query->where('service_id', $service->id));

        $reviewCount = (clone $reviewQuery)->count();
        $averageRating = (clone $reviewQuery)->avg('rating');
        $reviews = $reviewQuery->with('booking.customer')->latest()->limit(6)->get();

        $price = rtrim(rtrim(number_format((float) $service->price, 3, '.', ','), '0'), '.');
        $hourly = $service->pricing_type === 'hourly';
        $session = null;

        if ($service->min_hours && $service->max_hours) {
            $session = $service->min_hours === $service->max_hours
                ? $service->min_hours . ' h'
                : $service->min_hours . '–' . $service->max_hours . ' hours';
        }

        $bookingLabel = [
            'core' => 'Bookable on its own',
            'addon' => 'Add-on to a space or package',
            'both' => 'On its own or as an add-on',
        ][$service->kind] ?? null;

        $canBookService = $studio !== null && $service->kind !== 'addon';
        $showBookingModal = $canBookService && auth()->user()?->role === 'customer';
    @endphp

    <div class="container service-detail">
        <header class="service-detail-header">
            <nav class="service-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>

                @if ($companyUrl)
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <a href="{{ $companyUrl }}">{{ $profile->company_name }}</a>
                @endif

                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span aria-current="page">{{ $service->title }}</span>
            </nav>

            <div class="service-title-row">
                <div>
                    @if ($service->specialty)
                        <span class="service-specialty">{{ $service->specialtyLabel() }}</span>
                    @endif

                    <h1>{{ $service->title }}</h1>

                    <div class="service-meta">
                        @if ($reviewCount)
                            <span>
                                <i class="bi bi-star-fill" aria-hidden="true"></i>
                                {{ number_format($averageRating, 1) }} ({{ $reviewCount }})
                            </span>
                        @endif

                        @if ($service->deliveryLabel())
                            <span><i class="bi bi-pin-map" aria-hidden="true"></i> {{ $service->deliveryLabel() }}</span>
                        @endif

                        @if ($companyUrl)
                            <a href="{{ $companyUrl }}">
                                <i class="bi bi-building" aria-hidden="true"></i> {{ $profile->company_name }}
                            </a>
                        @endif
                    </div>
                </div>

                @if (auth()->guest() || auth()->user()->role === 'customer')
                    <div class="service-save">
                        @include('partials.favorite-heart', ['type' => 'service', 'id' => $service->id])
                        <span>Save</span>
                    </div>
                @endif
            </div>
        </header>

        @if ($photos->isNotEmpty())
            <div class="service-gallery service-gallery--{{ min($photos->count(), 3) }}">
                @foreach ($photos->take(3) as $photo)
                    <button type="button" data-gallery-src="{{ $photo }}"
                            aria-label="Open photo {{ $loop->iteration }}">
                        <img src="{{ $photo }}" alt="{{ $service->title }} photo {{ $loop->iteration }}">

                        @if ($photos->count() > 1 && ($loop->first || $loop->last))
                            <span class="service-gallery-more {{ $loop->first ? 'service-gallery-more-mobile' : '' }}">
                                <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                                All {{ $photos->count() }} photos
                            </span>
                        @endif
                    </button>
                @endforeach

                @foreach ($photos->skip(3) as $photo)
                    <span hidden data-gallery-src="{{ $photo }}"></span>
                @endforeach
            </div>

            <div class="lightbox" role="dialog" aria-label="Service photos">
                <button type="button" class="lb-btn lb-close" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
                <button type="button" class="lb-btn lb-prev" aria-label="Previous photo">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <img alt="{{ $service->title }}">
                <button type="button" class="lb-btn lb-next" aria-label="Next photo">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
                <span class="lb-count"></span>
            </div>
        @endif

        <div class="service-detail-grid">
            <div>
                @if ($service->description)
                    <section class="service-section">
                        <h2>About this service</h2>
                        <p>{{ $service->description }}</p>
                    </section>
                @endif

                <section class="service-section">
                    <h2>Details</h2>

                    <dl class="service-facts">
                        @if ($service->specialty)
                            <dt>Specialty</dt>
                            <dd>{{ $service->specialtyLabel() }}</dd>
                        @endif

                        @if ($service->deliveryLabel())
                            <dt>Where</dt>
                            <dd>
                                {{ $service->deliveryLabel() }}
                                @if ($studio && $service->delivery_mode === 'at_studio')
                                    · <a href="{{ route('studios.show', $studio->id) }}">{{ $studio->name }}</a>
                                @endif
                            </dd>
                        @endif

                        <dt>Pricing</dt>
                        <dd>JD {{ $price }} {{ $hourly ? 'per hour' : 'fixed price' }}</dd>

                        @if ($session)
                            <dt>Session</dt>
                            <dd>{{ $session }}</dd>
                        @endif

                        @if ($studio && $service->delivery_mode === 'at_studio')
                            <dt>Studio fee</dt>
                            <dd>
                                @if ($service->price_includes_studio)
                                    Included in the price
                                @else
                                    JD {{ $studio->hourly_price + 0 }} per hour, added to the booking
                                @endif
                            </dd>
                        @endif

                        @if ($bookingLabel)
                            <dt>Booking</dt>
                            <dd>{{ $bookingLabel }}</dd>
                        @endif
                    </dl>
                </section>

                @if ($packages->isNotEmpty())
                    <section class="service-section">
                        <h2>Part of these packages</h2>

                        <div class="service-related-grid">
                            @foreach ($packages as $package)
                                @include('customer.packages.card', ['package' => $package])
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($companyUrl)
                    <section class="service-section">
                        <h2>Offered by</h2>

                        <a class="service-provider-card" href="{{ $companyUrl }}">
                            <span class="service-provider-logo">
                                @if ($profile->logoUrl())
                                    <img src="{{ $profile->logoUrl() }}" alt="" loading="lazy">
                                @else
                                    {{ $profile->initials() }}
                                @endif
                            </span>

                            <span class="service-provider-name">
                                <strong>{{ $profile->company_name }}</strong>
                                @if ($profile->tagline)
                                    <span>{{ $profile->tagline }}</span>
                                @endif
                            </span>

                            <span>View studio <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                        </a>
                    </section>
                @endif

                <section class="service-section" id="reviews">
                    <h2>Ratings &amp; reviews</h2>

                    @if ($reviewCount)
                        <p class="service-rating">
                            <strong>{{ number_format($averageRating, 1) }}</strong>
                            <span><i class="bi bi-star-fill" aria-hidden="true"></i> {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</span>
                        </p>

                        <div class="reviews">
                            @foreach ($reviews as $review)
                                @php
                                    $customer = $review->booking?->customer;
                                    $customerName = $customer?->name ?? 'Customer';
                                @endphp

                                <article class="review">
                                    <div class="review-head">
                                        @if ($customer?->avatar)
                                            <img src="{{ asset('storage/' . $customer->avatar) }}"
                                                class="avatar"
                                                alt=""
                                                loading="lazy">
                                        @else
                                            <span class="avatar" aria-hidden="true">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($customerName, 0, 1)) }}
                                            </span>
                                        @endif

                                        <div>
                                            <strong>{{ $customerName }}</strong>
                                            <small>{{ $review->created_at?->format('M Y') }}</small>
                                        </div>

                                        <span class="stars" aria-label="{{ $review->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= $review->rating; $star++)
                                                <i class="bi bi-star-fill" aria-hidden="true"></i>
                                            @endfor
                                        </span>
                                    </div>

                                    @if ($review->comment)
                                        <p>{{ $review->comment }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p>No reviews yet.</p>
                    @endif
                </section>
            </div>

            <aside aria-label="Price and session">
                <div class="service-book-card">
                    <p class="service-price">
                        JD {{ $price }} <small>{{ $hourly ? '/ hour' : 'fixed price' }}</small>
                    </p>

                    @if ($reviewCount)
                        <p><i class="bi bi-star-fill" aria-hidden="true"></i> {{ number_format($averageRating, 1) }} ({{ $reviewCount }})</p>
                    @endif

                    <dl class="service-facts">
                        @if ($service->deliveryLabel())
                            <dt>Where</dt>
                            <dd>{{ $service->deliveryLabel() }}</dd>
                        @endif

                        @if ($session)
                            <dt>Session</dt>
                            <dd>{{ $session }}</dd>
                        @endif
                    </dl>

                    @if ($service->kind === 'addon')
                        <p>Add this service when booking a space or package.</p>
                    @elseif ($canBookService)
                        @guest
                            <a href="{{ route('services.booking.continue', ['service' => $service->id]) }}" class="btn btn-primary btn-lg btn-block">
                                <i class="bi bi-calendar2-plus" aria-hidden="true"></i>
                                Book now
                            </a>
                            <p class="service-book-note">Log in to book this service.</p>
                        @else
                            @if ($showBookingModal)
                                <button type="button" class="btn btn-primary btn-lg btn-block" id="open-booking">
                                    <i class="bi bi-calendar2-plus" aria-hidden="true"></i>
                                    Book now
                                </button>
                            @else
                                <button type="button" class="btn btn-light btn-lg btn-block" disabled>
                                    Bookings are for customers
                                </button>
                            @endif
                        @endguest
                    @endif
                </div>
            </aside>
        </div>
    </div>

    @if ($showBookingModal)
        @include('services.booking-modal', ['service' => $service])
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/spaces.js') }}" defer></script>

    @if ($showBookingModal)
        <script src="{{ asset('js/booking-modal.js') }}" defer></script>
    @endif
@endpush