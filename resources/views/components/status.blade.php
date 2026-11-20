@props(['value'])

<span {{ $attributes->merge(['class' => 'badge badge-' . $value]) }}>{{ ucfirst(str_replace('_', ' ', $value)) }}</span>
