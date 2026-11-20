@extends('layouts.dashboard')

@section('title', 'Profile Management')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Studio profile</h1>
            <p>This is your public page under “Studios” on SPACES.</p>
        </div>
        <x-status :value="$profile->status" />
    </div>

    @unless ($profile->isApproved())
        <div class="notice"><i class="bi bi-lock"></i><div><b>Editing is locked</b>You can edit your profile once the SPACES team approves your studio account.</div></div>
    @endunless

    <form method="POST" action="{{ route('provider.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <fieldset @disabled(! $profile->isApproved()) style="border:0;padding:0;margin:0;min-width:0">

        <div class="form-card">
            <div class="form-section">
                <div><h4>Brand</h4><p>Logo and cover photo shown on your studio page.</p></div>
                <div>
                    <div class="company-cover" style="height:170px;margin:0">
                        <img id="cover-preview" src="{{ $profile->coverUrl() ?? asset('images/placeholder.svg') }}" alt="">
                    </div>
                    <div class="row" style="margin-top:-36px;padding-left:16px;position:relative;z-index:2;align-items:flex-end">
                        <div class="company-head" style="margin:0;padding:0">
                            <div class="logo" style="width:84px;height:84px;border-radius:22px">
                                @if ($profile->logoUrl())
                                    <img id="logo-preview" src="{{ $profile->logoUrl() }}" alt="">
                                @else
                                    <span id="logo-preview" class="avatar" style="width:100%;height:100%;border-radius:0">{{ $profile->initials() }}</span>
                                @endif
                            </div>
                        </div>
                        <label class="btn btn-light btn-sm"><i class="bi bi-image"></i> Logo <input type="file" name="logo" accept="image/*" hidden data-avatar-input="#logo-preview"></label>
                        <label class="btn btn-light btn-sm"><i class="bi bi-card-image"></i> Cover <input type="file" name="cover" accept="image/*" hidden data-avatar-input="#cover-preview"></label>
                    </div>
                    @error('logo') <p class="error">{{ $message }}</p> @enderror
                    @error('cover') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-section">
                <div><h4>Company</h4><p>Tell customers who you are.</p></div>
                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="company_name">Company name</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name', $profile->company_name) }}" class="input @error('company_name') is-invalid @enderror" required>
                        @error('company_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label class="label" for="city">City</label>
                        <select id="city" name="city" class="select" required>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected(old('city', $profile->city) === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full">
                        <label class="label" for="tagline">Tagline</label>
                        <input id="tagline" name="tagline" value="{{ old('tagline', $profile->tagline) }}" class="input" maxlength="160" placeholder="One short line about your studio">
                    </div>
                    <div class="field full">
                        <label class="label" for="bio">About</label>
                        <textarea id="bio" name="bio" class="textarea" rows="5" maxlength="2000">{{ old('bio', $profile->bio) }}</textarea>
                    </div>
                    <div class="field">
                        <label class="label" for="instagram">Instagram</label>
                        <input id="instagram" name="instagram" value="{{ old('instagram', $profile->instagram) }}" class="input" placeholder="@yourstudio">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div><h4>Owner</h4><p>Only the SPACES team and your customers see this.</p></div>
                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="name">Your name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="input @error('name') is-invalid @enderror" required>
                    </div>
                    <div class="field">
                        <label class="label" for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input">
                    </div>
                    <div class="field full">
                        <label class="label">Email</label>
                        <input value="{{ $user->email }}" class="input" disabled>
                    </div>
                </div>
            </div>

            <div class="form-foot">
                <button class="btn btn-primary">Save profile</button>
            </div>
        </div>
        </fieldset>
    </form>
@endsection
