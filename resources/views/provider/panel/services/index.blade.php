@extends('layouts.dashboard')

@section('title', 'Service Management')

@section('actions')
    <a href="{{ route('provider.services.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add service</a>
@endsection

@section('content')
    <div class="dash-head">
        <div>
            <h1>Your services</h1>
            <p>Photographers, crews, engineers and editors. New services wait for admin approval.</p>
        </div>
    </div>

    <div class="panel">
        @if ($services->isEmpty())
            <div class="panel-body">
                <x-empty icon="bi-person-video2" title="No services yet" text="Offer a photographer, a sound engineer or an editing service.">
                    <a href="{{ route('provider.services.create') }}" class="btn btn-primary btn-sm">Add a service</a>
                </x-empty>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Service</th><th>Kind</th><th>Where</th><th>Price</th><th>Rating</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td>
                                    <a href="{{ route('provider.services.show', $service) }}" class="cell-main">
                                        <img src="{{ $service->imageUrl() }}" alt="">
                                        <div><b>{{ $service->title }}</b><small>{{ $service->specialtyLabel() }}</small></div>
                                    </a>
                                </td>
                                <td><span class="tag">{{ ['core' => 'Main', 'addon' => 'Add-on', 'both' => 'Main + add-on'][$service->kind] }}</span></td>
                                <td>{{ $service->deliveryLabel() }}@if ($service->studio)<br><small class="muted">{{ $service->studio->name }}</small>@endif</td>
                                <td>JD {{ (float) $service->price }} <small class="muted">{{ $service->priceSuffix() }}</small></td>
                                <td><x-rating :avg="$ratings->get($service->id)?->avg" :count="$ratings->get($service->id)?->cnt ?? 0" /></td>
                                <td><x-status :value="$service->status" />@unless ($service->is_active)<br><small class="muted">Off</small>@endunless</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('provider.services.show', $service) }}" class="icon-btn" title="Details"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('provider.services.edit', $service) }}" class="icon-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('provider.services.destroy', $service) }}" data-confirm="Delete {{ $service->title }}?">
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
