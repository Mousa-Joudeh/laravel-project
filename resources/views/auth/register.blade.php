@extends('layouts.user')

@section('title', 'Create an account')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    @php
        $isStudioOwner = old('account_type') === 'studio_owner';
    @endphp

    <div class="auth-page">
        <div class="auth-photo" aria-hidden="true">
            <div class="auth-photo-copy">
                <p class="auth-photo-title">Find a space for <span class="serif">your next project.</span></p>
                <p>Book a creative space or list your own studio on SPACES.</p>
            </div>
        </div>

        <section class="auth-panel" aria-labelledby="register-title">
            <div class="auth-panel-content">
                <h1 id="register-title">Create your account</h1>
                <p class="auth-intro">
                    Already a member? <a href="{{ route('login') }}">Log in</a>
                </p>

                <form id="register-form" class="auth-form" method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="auth-account-types" role="group" aria-label="Account type">
                        <label>
                            <input type="radio" name="account_type" value="customer" @checked(!$isStudioOwner)>
                            <span>I want to book</span>
                        </label>

                        <label>
                            <input type="radio" name="account_type" value="studio_owner" @checked($isStudioOwner)>
                            <span>I own a studio</span>
                        </label>
                    </div>

                    @error('account_type')
                        <p class="auth-error" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="auth-field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                               autocomplete="name" maxlength="255" required
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name')
                            <p id="name-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <fieldset id="studio-fields" class="auth-studio-fields" aria-label="Studio information"
                              @if (!$isStudioOwner) hidden disabled @endif>
                        <div class="auth-field">
                            <label for="company-name">Studio / company name</label>
                            <input id="company-name" name="company_name" type="text" value="{{ old('company_name') }}"
                                   autocomplete="organization" maxlength="150" required
                                   @error('company_name') aria-invalid="true" aria-describedby="company-name-error" @enderror>
                            @error('company_name')
                                <p id="company-name-error" class="auth-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="city">City</label>

                            <select id="city" name="city" autocomplete="address-level2" required
                                    @error('city') aria-invalid="true" aria-describedby="city-error" @enderror>
                                <option value="" disabled @selected(!old('city'))>Choose a city</option>

                                @foreach (['Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Salt', 'Madaba', 'Jerash', 'Karak', 'Mafraq', 'Ajloun'] as $city)
                                    <option value="{{ $city }}" @selected(old('city') === $city)>
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>

                            @error('city')
                                <p id="city-error" class="auth-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="auth-review-note">Studio applications are reviewed before approval.</p>
                    </fieldset>

                    <div class="auth-field-row">
                        <div class="auth-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   autocomplete="email" maxlength="255" required
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            @error('email')
                                <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="phone">Phone <span class="muted">(optional)</span></label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                                   autocomplete="tel" maxlength="25" placeholder="+962 7..."
                                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                            @error('phone')
                                <p id="phone-error" class="auth-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="auth-field-row">
                        <div class="auth-field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password"
                                   autocomplete="new-password" minlength="8" required
                                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            @error('password')
                                <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="password-confirmation">Confirm password</label>
                            <input id="password-confirmation" name="password_confirmation" type="password"
                                   autocomplete="new-password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">Create account</button>

                    <div id="google-signup" @if ($isStudioOwner) hidden @endif>
                        <a href="{{ route('auth.google.redirect') }}" class="btn btn-light btn-lg btn-block">
                            <i class="bi bi-google" aria-hidden="true"></i>
                            Continue with Google
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const registerForm = document.getElementById('register-form');
        const accountTypes = registerForm.querySelectorAll('input[name="account_type"]');
        const studioFields = document.getElementById('studio-fields');
        const googleSignup = document.getElementById('google-signup');

        function updateAccountType() {
            const selectedType = registerForm.querySelector('input[name="account_type"]:checked');
            const isStudioOwner = selectedType.value === 'studio_owner';

            studioFields.hidden = !isStudioOwner;
            studioFields.disabled = !isStudioOwner;
            googleSignup.hidden = isStudioOwner;
        }

        accountTypes.forEach(function (accountType) {
            accountType.addEventListener('change', updateAccountType);
        });

        updateAccountType();
    </script>
@endpush