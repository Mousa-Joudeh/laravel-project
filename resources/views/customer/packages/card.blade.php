<article class="package-card">
    @include('partials.favorite-heart', ['type' => 'package', 'id' => $package->id])
    <a href="{{ route('packages.show', $package->id) }}" class="pk-media">
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

        @if ($package->relationLoaded('services') && $package->services->count() > 0)
            <div class="includes">
                @foreach ($package->services->take(2) as $service)
                    <span class="tag">{{ \Illuminate\Support\Str::limit($service->title, 24) }}</span>
                @endforeach
            </div>
        @endif

        <div class="card-foot">
            <span class="price">JD {{ number_format($package->price, 0) }} <small>package</small></span>
            <a href="{{ route('packages.show', $package->id) }}" class="btn btn-light btn-sm">Details</a>
        </div>
    </div>
</article>