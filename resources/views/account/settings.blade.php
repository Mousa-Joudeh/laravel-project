@extends('layouts.user')

@section('title', 'My Profile')

@push('styles')
    <style>
        .account-profile {
            max-width: 840px;
            margin-inline: auto;
            padding-block: 32px 40px;
        }

        .account-profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 22px;
            border-radius: 22px;
            background: var(--soft-2, #f5f4f2);
        }

        .account-avatar-picker {
            position: relative;
            flex: 0 0 96px;
        }

        .account-profile-avatar {
            display: grid;
            width: 96px;
            height: 96px;
            place-items: center;
            overflow: hidden;
            border-radius: 50%;
            background: var(--ink, #171717);
            color: #fff;
            font-size: 28px;
            font-weight: 700;
        }

        .account-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .account-profile-avatar [hidden] {
            display: none;
        }

        .account-avatar-input {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 29px;
            height: 29px;
            opacity: 0;
        }

        .account-avatar-button {
            position: absolute;
            right: 0;
            bottom: 0;
            display: grid;
            width: 29px;
            height: 29px;
            place-items: center;
            border: 2px solid #fff;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }

        .account-avatar-input:focus-visible + .account-avatar-button {
            outline: 2px solid var(--ink);
            outline-offset: 2px;
        }

        .account-profile-identity {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .account-profile-identity h1 {
            margin: 4px 0;
            font-size: 22px;
        }

        .account-profile-label {
            font-size: 11px;
            letter-spacing: .12em;
        }

        .account-profile-label::before {
            content: '●';
            margin-right: 6px;
            color: var(--accent);
        }

        .account-profile-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin: 14px 0 22px;
        }

        .account-profile-stat,
        .account-profile-panel {
            border: 1px solid var(--line);
        }

        .account-profile-stat {
            padding: 14px;
            border-radius: 12px;
        }

        .account-profile-stat strong {
            display: block;
            margin-bottom: 4px;
            font-size: 22px;
        }

        .account-profile-panel {
            padding: 18px;
            border-radius: 16px;
        }

        .account-password-form {
            margin-top: 14px;
        }

        .account-profile-panel h2 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .account-profile-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 18px;
        }

        .account-field-full {
            grid-column: 1 / -1;
        }

        .account-profile-fields label {
            display: block;
            margin-bottom: 6px;
        }

        .account-profile-fields .input,
        .account-profile-fields .select {
            width: 100%;
        }

        .account-profile-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .account-profile-message {
            margin-top: 10px;
        }

        .account-profile-error {
            display: block;
            margin-top: 4px;
            color: #a32218;
        }

        @media (max-width: 640px) {
            .account-profile-header {
                flex-wrap: wrap;
            }

            .account-profile-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .account-profile-fields {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    @php
        $avatarUrl = $user->avatarUrl();
        $cities = ['Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Salt', 'Madaba', 'Jerash', 'Karak', 'Mafraq', 'Ajloun'];
        $selectedCity = old('city', $user->city);

        if ($selectedCity && !in_array($selectedCity, $cities, true)) {
            $cities[] = $selectedCity;
        }
    @endphp

    <div class="container">
        <div class="account-profile">
            <form action="{{ route('account.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <header class="account-profile-header">
                    <div class="account-avatar-picker">
                        <div class="account-profile-avatar" aria-hidden="true">
                            @if ($avatarUrl)
                                <img id="account-avatar-preview" src="{{ $avatarUrl }}" alt="">
                            @else
                                <img id="account-avatar-preview" alt="" hidden>
                                <span id="account-avatar-initials">{{ $user->initials() }}</span>
                            @endif
                        </div>

                        <input id="account-avatar" name="avatar" type="file"
                               class="account-avatar-input"
                               accept="image/jpeg,image/png,image/webp"
                               @error('avatar', 'account') aria-describedby="account-avatar-error" @enderror>
                        <label for="account-avatar" class="account-avatar-button" aria-label="Choose profile photo">
                            <i class="bi bi-camera-fill" aria-hidden="true"></i>
                        </label>
                    </div>

                    <div class="account-profile-identity">
                        <span class="account-profile-label muted">MY PROFILE</span>
                        <h1>{{ $user->name }}</h1>
                        <p class="muted">
                            @if ($user->created_at)
                                Member since {{ $user->created_at->format('F Y') }} ·
                            @endif
                            {{ $user->email }}
                        </p>
                        @error('avatar', 'account')
                            <small id="account-avatar-error" class="account-profile-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <a href="{{ route('my-bookings.index') }}" class="btn btn-primary">
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        My Bookings
                    </a>
                </header>

                <section class="account-profile-stats" aria-label="Account statistics">
                    <div class="account-profile-stat">
                        <strong>{{ $stats['bookings'] }}</strong>
                        <span class="muted">bookings</span>
                    </div>
                    <div class="account-profile-stat">
                        <strong>{{ $stats['upcoming'] }}</strong>
                        <span class="muted">upcoming</span>
                    </div>
                    <div class="account-profile-stat">
                        <strong>{{ $stats['favorites'] }}</strong>
                        <span class="muted">favorites</span>
                    </div>
                    <div class="account-profile-stat">
                        <strong>{{ $stats['reviews'] }}</strong>
                        <span class="muted">reviews written</span>
                    </div>
                </section>

                <section class="account-profile-panel" aria-labelledby="personal-information">
                    <h2 id="personal-information">Personal information</h2>
                    <p class="muted">Studios see your name and phone when you book.</p>

                    @if (session('account_success'))
                        <p class="account-profile-message" role="status">{{ session('account_success') }}</p>
                    @endif

                    <div class="account-profile-fields">
                        <div>
                            <label for="name">Full name</label>
                            <input id="name" name="name" class="input" autocomplete="name"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name', 'account')
                                <small class="account-profile-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div>
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" class="input" autocomplete="email"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email', 'account')
                                <small class="account-profile-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div>
                            <label for="phone">Phone number</label>
                            <input id="phone" name="phone" type="tel" class="input" autocomplete="tel"
                                   value="{{ old('phone', $user->phone) }}">
                            @error('phone', 'account')
                                <small class="account-profile-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div>
                            <label for="city">City</label>
                            <select id="city" name="city" class="select">
                                <option value="">Select a city</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city }}" @selected($selectedCity === $city)>
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city', 'account')
                                <small class="account-profile-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="account-profile-actions">
                        <button type="submit" class="btn btn-dark">Save changes</button>
                    </div>
                </section>
            </form>

            <form action="{{ route('account.password.update') }}" method="POST"
                  class="account-profile-panel account-password-form" aria-labelledby="password-heading">
                @csrf
                @method('PATCH')

                <h2 id="password-heading">Password</h2>
                <p class="muted">Use at least 8 characters.</p>

                @if (session('password_success'))
                    <p class="account-profile-message" role="status">{{ session('password_success') }}</p>
                @endif

                <div class="account-profile-fields">
                    <div class="account-field-full">
                        <label for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password"
                               class="input" autocomplete="current-password" required>
                        @error('current_password', 'password')
                            <small class="account-profile-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div>
                        <label for="password">New password</label>
                        <input id="password" name="password" type="password"
                               class="input" autocomplete="new-password" minlength="8" required>
                        @error('password', 'password')
                            <small class="account-profile-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="input" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>

                <div class="account-profile-actions">
                    <button type="submit" class="btn btn-light">Change password</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('account-avatar');
            const preview = document.getElementById('account-avatar-preview');
            const initials = document.getElementById('account-avatar-initials');
            const originalSource = preview.getAttribute('src');
            let previewUrl = null;

            input.addEventListener('change', () => {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                }

                const file = input.files[0];

                if (file) {
                    previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    preview.hidden = false;
                    if (initials) initials.hidden = true;
                    return;
                }

                if (originalSource) {
                    preview.src = originalSource;
                } else {
                    preview.removeAttribute('src');
                    preview.hidden = true;
                    if (initials) initials.hidden = false;
                }
            });
        })();
    </script>
@endpush