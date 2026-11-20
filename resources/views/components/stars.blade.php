@props(['value' => 0])

<span {{ $attributes->merge(['class' => 'stars']) }} aria-label="{{ $value }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        @if ($value >= $i)
            <i class="bi bi-star-fill"></i>
        @elseif ($value >= $i - 0.5)
            <i class="bi bi-star-half"></i>
        @else
            <i class="bi bi-star" style="opacity:.35"></i>
        @endif
    @endfor
</span>
