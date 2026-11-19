
<dialog
    id="sp-create-user-dialog"
    class="sp-create-dialog"
    aria-labelledby="sp-create-title"
>
    <form
        id="sp-create-user-form"
        method="POST"
        action="{{ route('users.store') }}"
    >
        @csrf

        <div class="sp-create-header">
            <div>
                <h2 id="sp-create-title">Create user</h2>
                <p>Add a new account to SPACE.</p>
            </div>

            <button
                type="button"
                class="sp-create-close"
                data-close-create-user
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sp-create-body">

            <div
                id="sp-create-user-error"
                class="sp-create-error"
                role="alert"
                hidden
            ></div>

            <div class="sp-create-grid">

                <div class="sp-create-field">
                    <label for="create-name">
                        Full name <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="create-name"
                        name="name"
                        class="sp-input"
                        placeholder="Enter full name"
                        maxlength="255"
                        autocomplete="name"
                        required
                    >

                    <small data-error-for="name"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-email">
                        Email address <span>*</span>
                    </label>

                    <input
                        type="email"
                        id="create-email"
                        name="email"
                        class="sp-input"
                        placeholder="name@example.com"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >

                    <small data-error-for="email"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-phone">Phone number</label>

                    <input
                        type="tel"
                        id="create-phone"
                        name="phone"
                        class="sp-input"
                        placeholder="07XXXXXXXX"
                        maxlength="25"
                        autocomplete="tel"
                    >

                    <small data-error-for="phone"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-city">City</label>

                    <div class="sp-city-dropdown" id="sp-city-dropdown">
                        <div class="sp-city-control">
                            <input
                                type="text"
                                id="create-city"
                                name="city"
                                class="sp-input"
                                placeholder="Select or search city"
                                maxlength="100"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="sp-city-options"
                            >

                            <button
                                type="button"
                                class="sp-city-arrow"
                                id="sp-city-toggle"
                                aria-label="Show cities"
                                tabindex="-1"
                            >
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>

                        <div
                            class="sp-city-options"
                            id="sp-city-options"
                            role="listbox"
                            hidden
                        ></div>
                    </div>

                    <small data-error-for="city"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-role">
                        Account role <span>*</span>
                    </label>

                    <select
                        id="create-role"
                        name="role"
                        class="sp-select"
                        required
                    >
                        <option value="customer">Customer</option>
                        <option value="provider">Provider</option>
                        <option value="admin">Administrator</option>
                    </select>

                    <small data-error-for="role"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-status">
                        Account status <span>*</span>
                    </label>

                    <select
                        id="create-status"
                        name="status"
                        class="sp-select"
                        required
                    >
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>

                    <small data-error-for="status"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-password">
                        Password <span>*</span>
                    </label>

                    <input
                        type="password"
                        id="create-password"
                        name="password"
                        class="sp-input"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <small data-error-for="password"></small>
                </div>

                <div class="sp-create-field">
                    <label for="create-confirm-password">
                        Confirm password <span>*</span>
                    </label>

                    <input
                        type="password"
                        id="create-confirm-password"
                        name="password_confirmation"
                        class="sp-input"
                        placeholder="Repeat password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

            </div>
        </div>

        <div class="sp-create-footer">
            <span class="sp-create-note">
                * Required fields
            </span>

            <div class="sp-create-actions">
                <button
                    type="button"
                    class="sp-btn sp-btn-secondary"
                    data-close-create-user
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="sp-create-user-submit"
                    class="sp-btn sp-btn-primary"
                >
                    <i class="bi bi-check-lg"></i>
                    Create user
                </button>
            </div>
        </div>

    </form>
</dialog>
