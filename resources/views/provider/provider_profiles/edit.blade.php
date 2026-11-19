@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1>Edit Provider Profile</h1>

        <form method="POST"
              action="{{ route('provider-profiles.update', $profile) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @foreach ([
                'company_name' => ['Company Name', 150],
                'tagline' => ['Tagline', 160],
                'city' => ['City', 100],
                'instagram' => ['Instagram', 120],
            ] as $field => [$label, $maxLength])
                <div class="mb-3">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>

                    <input id="{{ $field }}" name="{{ $field }}" type="text"
                           class="form-control"
                           value="{{ old($field, $profile->$field) }}"
                           maxlength="{{ $maxLength }}"
                           @required($field === 'company_name')>

                    @error($field)
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>

                <textarea id="bio" name="bio" class="form-control"
                          rows="4">{{ old('bio', $profile->bio) }}</textarea>

                @error('bio')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>

            @foreach (['logo' => 'Company Logo', 'cover' => 'Cover Image'] as $field => $label)
                <div class="mb-3">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>

                    @if ($profile->$field)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $profile->$field) }}"
                                 alt="Current {{ $label }}"
                                 width="160"
                                 class="img-fluid rounded">
                        </div>
                    @endif

                    <input id="{{ $field }}" name="{{ $field }}" type="file"
                           class="form-control"
                           accept="image/jpeg,image/png,image/webp">

                    @error($field)
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Save Changes</button>

            <a href="{{ route('provider-profiles.show', $profile) }}">Cancel</a>
        </form>
    </div>
@endsection