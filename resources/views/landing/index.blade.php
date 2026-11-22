@extends('layouts.user')

@section('title', 'Book studios, spaces and photo packages')

@push('styles')
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
@endpush

@section('content')

    
    

    
    <section class="hero">
                {{-- film ribbon that curves behind the hero text --}}
        <div class="film-ribbon" aria-hidden="true">
            @for ($round = 1; $round <= 3; $round++)
                @foreach ($topSpaces as $studio)
                    <div class="ribbon-frame"><img src="{{ $studio->cover_url }}" alt=""></div>
                @endforeach
                @foreach ($topPackages as $package)
                    <div class="ribbon-frame"><img src="{{ $package->cover_url }}" alt=""></div>
                @endforeach
            @endfor
        </div>
        <div class="container hero-grid">
            <div>
                <span class="pill reveal">
                    <span class="pill-dot"><i class="bi bi-camera-fill"></i></span>
                    {{ $stats['spaces'] }} studios ready to book across Jordan
                    <span class="rec"></span>
                </span>

                <h1 class="hero-title reveal">
                   Find the <span class="serif accent">space</span>.<br>
                    Frame the <span class="serif accent">story</span>.
                </h1>

                <p class="hero-sub reveal">
                    Photography studios, podcast rooms, show kitchens and ready-made photo packages,
                    booked in a minute with the time that suits you.
                </p>

                <form action="{{ route('studios.index') }}" method="GET" class="hero-search reveal">
                    <label class="hs-field">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" placeholder="Studio name, e.g. Echo Podcast">
                    </label>
                    <label class="hs-field">
                        <i class="bi bi-geo-alt"></i>
                        <select name="city">
                            <option value="">Any city</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}">{{ $city }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <div class="hero-quick reveal">
                    @foreach ($studioTypes->take(5) as $type)
                        <a href="{{ route('studios.index', ['type' => $type->id]) }}">{{ $type->name }}</a>
                    @endforeach
                </div>

                <div class="hero-stats reveal">
                    <div><strong>{{ $stats['spaces'] }}</strong><span>bookable spaces</span></div>
                    <div><strong>{{ $stats['studios'] }}</strong><span>verified studios</span></div>
                    <div><strong>{{ $stats['bookings'] }}+</strong><span>sessions completed</span></div>
                    <div><strong>{{ $stats['rating'] }} <span class="accent">★</span></strong><span>average rating</span></div>
                </div>
            </div>

            <div class="hero-visual reveal">
                <div class="hero-frame" id="hero-frame">
                    <img src="{{ asset('images/hero/hero-1.jpg') }}" alt="A photographer in a studio" class="on">
                    <img src="{{ asset('images/hero/hero-3.jpg') }}" alt="A podcast studio">
                    <img src="{{ asset('images/hero/hero-2.jpg') }}" alt="A photographer directing a model">
                    <div class="hero-flash"></div>

                    {{-- camera viewfinder --}}
                    <div class="vf">
                        <span class="c tl"></span><span class="c tr"></span><span class="c bl"></span><span class="c br"></span>
                        <span class="vf-rec">REC</span>
                        <span class="vf-bat">▮▮▮ 86%</span>
                        <span class="vf-focus"></span>
                        <span class="vf-iso"><span>ISO 200</span><span>f/2.8</span><span>1/125</span><span>RAW</span></span>
                    </div>
                </div>

                {{-- animated camera --}}
                <div class="camera-badge">
                    <svg viewBox="0 0 120 86" fill="none">
                        <rect class="cam-button" x="22" y="6" width="16" height="8" rx="3" fill="#141413"/>
                        <rect x="6" y="14" width="108" height="66" rx="14" fill="#141413"/>
                        <path d="M42 14l6-9h24l6 9" fill="#141413"/>
                        <rect x="6" y="30" width="108" height="6" fill="#2a2927"/>
                        <circle cx="60" cy="46" r="25" fill="#2a2927"/>
                        <circle cx="60" cy="46" r="20" fill="#0d0d0c" stroke="#3c3a37" stroke-width="2"/>
                        <g class="cam-aperture">
                            <circle cx="60" cy="46" r="15" fill="#ff5b1a"/>
                            <path d="M60 31 L66 44 M75 46 L62 52 M60 61 L54 48 M45 46 L58 40 M70.6 35.4 L63.5 47 M49.4 56.6 L56.5 45" stroke="#141413" stroke-width="1.6" stroke-linecap="round"/>
                            <circle cx="60" cy="46" r="5" fill="#141413"/>
                        </g>
                        <circle cx="53" cy="39" r="3" fill="#fff" opacity=".55"/>
                        <rect class="cam-flash" x="84" y="18" width="18" height="8" rx="2" fill="#ffd2bd"/>
                        <circle cx="100" cy="66" r="3" fill="#ff5b1a"/>
                    </svg>
                    <span class="cap">READY · 24 EXP</span>
                </div>

                <div class="hero-dots">
                    <button type="button" class="on"></button>
                    <button type="button"></button>
                    <button type="button"></button>
                </div>
            </div>
        </div>
    </section>

    
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <div>
                    <span class="eyebrow"><b>01</b> — Ready-made packages</span>
                    <h2 class="section-title">Top <span class="serif">packages</span></h2>
                </div>
                <a href="{{ route('packages.index') }}" class="link-more">View more <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="grid grid-3">
                @forelse ($topPackages as $package)
                    <article class="package-card reveal">
                        @include('partials.favorite-heart', ['type' => 'package', 'id' => $package->id])
                        <a href="{{ route('packages.show', $package->id) }}" class="media vf-hover">
                            <img src="{{ $package->cover_url }}" alt="{{ $package->name }}">
                            <span class="tag tag-float">{{ ucfirst($package->type) }}</span>
                        </a>
                        <div class="card-body">
                            <div class="card-line">
                                <h3 class="card-title"><a href="{{ route('packages.show', $package->id) }}">{{ $package->name }}</a></h3>
                                @if ($package->reviews_total > 0)
                                    <span class="rating"><i class="bi bi-star-fill"></i> {{ $package->rating }} <small>({{ $package->reviews_total }})</small></span>
                                @else
                                    <span class="rating muted"><i class="bi bi-stars"></i> New</span>
                                @endif
                            </div>
                            <div class="studio-line"><i class="bi bi-camera"></i> {{ $package->studio->name }}</div>
                            <div class="card-foot">
                                <span class="price">JD {{ number_format($package->price, 0) }} <small>package</small></span>
                                <a href="{{ route('packages.show', $package->id) }}" class="btn btn-light btn-sm">Details</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="muted">No packages yet.</p>
                @endforelse
            </div>
        </div>
    </section>

   
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <div>
                    <span class="eyebrow"><b>02</b> — Most loved rooms</span>
                    <h2 class="section-title">Top <span class="serif">spaces</span></h2>
                </div>
                <a href="{{ route('studios.index') }}" class="link-more">View more <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="marquee">
                <div class="marquee-track">
                    {{-- the list is written twice so the loop never jumps --}}
                    @for ($copy = 1; $copy <= 2; $copy++)
                        @foreach ($topSpaces as $studio)
                            <article class="space-card">
                                @include('partials.favorite-heart', ['type' => 'studio', 'id' => $studio->id])
                                <a href="{{ route('studios.show', $studio->id) }}" class="media vf-hover">
                                    <img src="{{ $studio->cover_url }}" alt="{{ $studio->name }}">
                                    @if ($studio->type)
                                        <span class="tag tag-float"><i class="bi {{ $studio->type->icon }}"></i> {{ $studio->type->name }}</span>
                                    @endif
                                    <span class="exif">{{ $studio->capacity }} PPL · {{ strtoupper($studio->city) }}</span>
                                </a>
                                <div class="card-body">
                                    <div class="card-line">
                                        <h3 class="card-title"><a href="{{ route('studios.show', $studio->id) }}">{{ $studio->name }}</a></h3>
                                        @if ($studio->reviews_total > 0)
                                            <span class="rating"><i class="bi bi-star-fill"></i> {{ $studio->rating }} <small>({{ $studio->reviews_total }})</small></span>
                                        @else
                                            <span class="rating muted"><i class="bi bi-stars"></i> New</span>
                                        @endif
                                    </div>
                                    <div class="card-line">
                                        <span class="muted"><i class="bi bi-geo-alt"></i> {{ $studio->city }}</span>
                                        <span class="price">JD {{ number_format($studio->hourly_price, 0) }} <small>/ hour</small></span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @endfor
                </div>
            </div>
        </div>
    </section>

    
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <div>
                    <span class="eyebrow"><b>03</b> — Browse by type</span>
                    <h2 class="section-title">Popular <span class="serif">categories</span></h2>
                </div>
                <a href="{{ route('studios.index') }}" class="link-more">All types <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="types-grid">
                @foreach ($studioTypes as $type)
                    <a href="{{ route('studios.index', ['type' => $type->id]) }}" class="type-card reveal">
                        @if ($type->image)
                            <img src="{{ asset('storage/' . $type->image) }}" alt="">
                        @endif
                        <span class="type-icon"><i class="bi {{ $type->icon }}"></i></span>
                        <span class="type-arrow"><i class="bi bi-arrow-up-right"></i></span>
                        <div class="type-body">
                            <h4>{{ $type->name }}</h4>
                            <small>{{ $type->studios_count }} spaces</small>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

   
    @if ($testimonials->count() > 0)
        <section class="section">
            <div class="container">
                <div class="section-head reveal">
                    <div>
                        <span class="eyebrow"><b>04</b> — From real bookings</span>
                        <h2 class="section-title">Creatives <span class="serif">say</span></h2>
                    </div>
                </div>

                <div class="grid grid-3">
                    @foreach ($testimonials as $review)
                        <figure class="quote-card reveal">
                            <span class="stars">
                                @for ($i = 1; $i <= $review->rating; $i++)
                                    <i class="bi bi-star-fill"></i>
                                @endfor
                            </span>
                            <p>{{ $review->comment }}</p>
                            <figcaption class="who">
                                @if ($review->booking->customer->avatar)
                                    <img src="{{ asset('storage/' . $review->booking->customer->avatar) }}" class="avatar" alt="">
                                @else
                                    <span class="avatar">{{ strtoupper(substr($review->booking->customer->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <b>{{ $review->booking->customer->name }}</b>
                                    <small>{{ $review->booking->studio->name }}</small>
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection

@push('scripts')
    <script src="{{ asset('js/landing.js') }}"></script>
@endpush