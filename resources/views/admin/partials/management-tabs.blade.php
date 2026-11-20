<div class="mgmt-tabs" role="navigation" aria-label="Status filters">
    @foreach($tabs as $value => $tab)
        <a class="mgmt-tab {{ $selected === (string) $value ? 'is-active' : '' }}"
           href="{{ route($route, array_merge(request()->except('page', $param), $value === '' ? [] : [$param => $value])) }}">
            {{ $tab[0] }} <span>{{ $tab[1] }}</span>
        </a>
    @endforeach
</div>
