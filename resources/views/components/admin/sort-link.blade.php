@props(['name', 'label', 'sort', 'direction'])
<a class="sp-sort-link {{ $sort === $name ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => $name, 'direction' => $sort === $name && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}">
    {{ $label }}
    <i class="bi {{ $sort === $name ? ($direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up' }}"></i>
</a>
