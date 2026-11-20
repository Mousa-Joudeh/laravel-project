@props(['icon' => 'bi-camera', 'title' => 'Nothing here yet', 'text' => null])

<div class="empty">
    <div class="empty-icon"><i class="bi {{ $icon }}"></i></div>
    <h4>{{ $title }}</h4>
    @if ($text)
        <p>{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
