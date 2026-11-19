
@extends('layouts.admin')

@section('title', 'Create User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/users.css') }}">
@endpush

@section('content')

<div class="admin-page-heading">
    <div>
        <span class="admin-kicker">MANAGEMENT / USERS / CREATE</span>
        <h1>Create user</h1>
        <p>Create a new account on SPACE.</p>
    </div>

    <a href="{{ route('admin.users.index') }}"
       class="sp-btn sp-btn-secondary">
        <i class="bi bi-arrow-left"></i>
        Back to users
    </a>
</div>

@if($errors->any())
    <div class="sp-alert sp-alert-danger sp-form-notice">
        Please check the highlighted fields.
    </div>
@endif

<form action="{{ route('users.store') }}"
      method="POST"
      class="sp-stack">

    @csrf

    <section class="sp-card">

        <div class="sp-card-header">
            <div>
                <h2 class="sp-card-title">Account information</h2>
                <p class="sp-page-subtitle">
                    Basic personal and contact information.
                </p>
            </div>
        </div>

        <div class="sp-card-body">

            <div class="sp-form-grid">

                <div class="sp-field">
                    <label class="sp-label" for="name">
                        Full name <span class="sp-required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="sp-input"
                        value="{{ old('name') }}"
                        placeholder="Enter full name"
                        maxlength="255"
                        autocomplete="name"
                        required
                    >

                    @error('name')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="email">
                        Email address <span class="sp-required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="sp-input"
                        value="{{ old('email') }}"
                        placeholder="name@example.com"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >

                    @error('email')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="phone">
                        Phone number
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        id="phone"
                        class="sp-input"
                        value="{{ old('phone') }}"
                        placeholder="07XXXXXXXX"
                        maxlength="25"
                        autocomplete="tel"
                    >

                    @error('phone')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="city">
                        City
                    </label>

                    <input
                        type="text"
                        name="city"
                        id="city"
                        class="sp-input"
                        value="{{ old('city') }}"
                        placeholder="Amman"
                        maxlength="100"
                    >

                    @error('city')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>
        </div>

    </section>

    <section class="sp-card">

        <div class="sp-card-header">
            <div>
                <h2 class="sp-card-title">Role & access</h2>
                <p class="sp-page-subtitle">
                    Define account permissions and status.
                </p>
            </div>
        </div>

        <div class="sp-card-body">

            <div class="sp-form-grid">

                <div class="sp-field">
                    <label class="sp-label" for="role">
                        Account role <span class="sp-required">*</span>
                    </label>

                    <select name="role"
                            id="role"
                            class="sp-select"
                            required>

                        <option value="customer"
                            @selected(old('role', 'customer') === 'customer')>
                            Customer
                        </option>

                        <option value="provider"
                            @selected(old('role') === 'provider')>
                            Provider
                        </option>

                        <option value="admin"
                            @selected(old('role') === 'admin')>
                            Administrator
                        </option>
                    </select>

                    @error('role')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="status">
                        Account status <span class="sp-required">*</span>
                    </label>

                    <select name="status"
                            id="status"
                            class="sp-select"
                            required>

                        <option value="active"
                            @selected(old('status', 'active') === 'active')>
                            Active
                        </option>

                        <option value="suspended"
                            @selected(old('status') === 'suspended')>
                            Suspended
                        </option>
                    </select>

                    @error('status')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>

        </div>

    </section>

    <section class="sp-card">

        <div class="sp-card-header">
            <div>
                <h2 class="sp-card-title">Security</h2>
                <p class="sp-page-subtitle">
                    Set the initial login password.
                </p>
            </div>
        </div>

        <div class="sp-card-body">

            <div class="sp-form-grid">

                <div class="sp-field">
                    <label class="sp-label" for="password">
                        Password <span class="sp-required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="sp-input"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <span class="sp-field-help">
                        Minimum 8 characters.
                    </span>

                    @error('password')
                        <span class="sp-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="password_confirmation">
                        Confirm password <span class="sp-required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="sp-input"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

            </div>

        </div>

    </section>

    <div class="sp-form-actions">

        <span class="sp-field-help">
            Fields marked * are required.
        </span>

        <div class="sp-inline">
            <a href="{{ route('admin.users.index') }}"
               class="sp-btn sp-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="sp-btn sp-btn-primary">
                <i class="bi bi-check-lg"></i>
                Create user
            </button>
        </div>

    </div>

</form>

@endsection
