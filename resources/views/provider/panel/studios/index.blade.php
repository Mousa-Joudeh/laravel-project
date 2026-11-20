@extends('layouts.dashboard')

@section('title', 'Space Management')

@section('actions')
    <a href="{{ route('provider.studios.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add space</a>
@endsection

@section('content')
    <div class="dash-head">
        <div>
            <h1>Your spaces</h1>
            <p>New spaces are reviewed by the admin before they appear on the website.</p>
        </div>
    </div>

    <div class="panel">
        @if ($studios->isEmpty())
            <div class="panel-body">
                <x-empty icon="bi-house-door" title="No spaces yet" text="Add your first studio with photos, equipment and an hourly price.">
                    <a href="{{ route('provider.studios.create') }}" class="btn btn-primary btn-sm">Add a space</a>
                </x-empty>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Space</th><th>Type</th><th>Price</th><th>Capacity</th><th>Rating</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($studios as $studio)
                            <tr>
                                <td>
                                    <a href="{{ route('provider.studios.show', $studio) }}" class="cell-main">
                                        <img src="{{ $studio->coverUrl() }}" alt="">
                                        <div><b>{{ $studio->name }}</b><small>{{ $studio->city }} · {{ $studio->images->count() }} photos</small></div>
                                    </a>
                                </td>
                                <td>{{ $studio->type?->name }}<br><small class="muted">{{ $studio->category?->name }}</small></td>
                                <td>JD {{ (float) $studio->hourly_price }}<small class="muted"> /h</small></td>
                                <td>{{ $studio->capacity }} ppl</td>
                                <td><x-rating :avg="$studio->reviews_avg_rating" :count="$studio->reviews_count ?? 0" /></td>
                                <td>
                                    <x-status :value="$studio->status" />
                                    @if ($studio->status === 'approved' && ! $studio->is_active)
                                        <br><small class="muted">Hidden</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        @if ($studio->isPublished())
                                            <a href="{{ route('studios.show', $studio) }}" target="_blank" class="icon-btn" title="View on website"><i class="bi bi-box-arrow-up-right"></i></a>
                                        @endif
                                        <a href="{{ route('provider.studios.show', $studio) }}" class="icon-btn" title="Details"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('provider.studios.edit', $studio) }}" class="icon-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('provider.studios.destroy', $studio) }}" data-confirm="Delete {{ $studio->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn danger" title="Delete"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
