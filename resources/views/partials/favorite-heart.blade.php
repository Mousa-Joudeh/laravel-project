
@php
    $models = [
        'studio' => 'App\Models\Studio',
        'package' => 'App\Models\Package',
        'service' => 'App\Models\Service',
    ];

    $favorite = null;
    if (Auth::check()) {
        $favorite = Auth::user()->favorites()
            ->where('favoritable_type', $models[$type])
            ->where('favoritable_id', $id)
            ->first();
    }
@endphp

<div class="fav-wrap">
    @guest
        
        <a href="{{ route('login') }}" class="fav-btn" title="Save to favorites"><i class="bi bi-heart"></i></a>
    @endguest

    @auth
        @if (Auth::user()->role == 'customer')
            @if ($favorite)
                <form method="POST" action="{{ route('favorites.destroy', $favorite->id) }}">
                    @csrf
                    @method('DELETE')
                    <button class="fav-btn saved" title="Remove from favorites"><i class="bi bi-heart-fill"></i></button>
                </form>
            @else
                <form method="POST" action="{{ route('favorites.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="id" value="{{ $id }}">
                    <button class="fav-btn" title="Save to favorites"><i class="bi bi-heart"></i></button>
                </form>
            @endif
        @endif
    @endauth
</div>