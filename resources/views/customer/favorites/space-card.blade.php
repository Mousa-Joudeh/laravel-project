<article class="space-card">
    @include('partials.favorite-heart', ['type' => 'studio', 'id' => $studio->id])

    <div class="media slider">
        <a href="{{ route('studios.show', $studio->id) }}" class="slides">
            @forelse ($studio->images as $image)
                <img src="{{ $image->url() }}" alt="{{ $studio->name }}" loading="lazy">
            @empty
                <img src="{{ asset('images/placeholder.svg') }}" alt="">
            @endforelse
        </a>

        @if ($studio->type)
            <span class="tag tag-float">
                <i class="bi {{ $studio->type->icon }}" aria-hidden="true"></i>
                {{ $studio->type->name }}
            </span>
        @endif

        @if ($studio->images->count() > 1)
            <button type="button" class="slide-btn prev" aria-label="Previous photo">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="slide-btn next" aria-label="Next photo">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    <div class="card-body">
        <div class="card-line">
            <h3 class="card-title">
                <a href="{{ route('studios.show', $studio->id) }}">{{ $studio->name }}</a>
            </h3>
        </div>

        <div class="card-line">
            <span class="muted">
                <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $studio->city }}
            </span>
            <span class="price">JD {{ $studio->hourly_price + 0 }} <small>/ hour</small></span>
        </div>
    </div>
</article>