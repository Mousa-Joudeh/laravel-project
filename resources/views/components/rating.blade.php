@props(['avg' => null, 'count' => 0])

@if ($count > 0)
    <span {{ $attributes->merge(['class' => 'rating']) }}>
        <i class="bi bi-star-fill"></i> {{ number_format((float) $avg, 1) }} <small>({{ $count }})</small>
    </span>
@else
    <span {{ $attributes->merge(['class' => 'rating new']) }}><i class="bi bi-stars"></i> New</span>
@endif
