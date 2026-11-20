<dialog id="sp-user-dialog" class="sp-um-dialog" aria-label="User management">

    <section id="sp-user-view-panel" class="sp-um-panel">
        <div class="sp-um-header">
            <div><h2>User details</h2><p>Account information and management</p></div>
            <button type="button" class="sp-um-close" data-um-close aria-label="Close dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sp-um-body">
            <div class="sp-um-profile">
                <div class="sp-um-avatar" id="sp-view-avatar">U</div>
                <div class="sp-um-profile-info">
                    <h3 id="sp-view-name"></h3>
                    <p id="sp-view-email"></p>
                    <div class="sp-um-badges">
                        <span class="sp-badge" id="sp-view-role"></span>
                        <span class="sp-badge" id="sp-view-status"></span>
                    </div>
                </div>
            </div>
            <div class="sp-um-detail-grid">
                <div class="sp-um-detail"><span>Phone</span><strong id="sp-view-phone"></strong></div>
                <div class="sp-um-detail"><span>City</span><strong id="sp-view-city"></strong></div>
                <div class="sp-um-detail" id="sp-view-company-wrap" hidden>
                    <span>Company</span><strong id="sp-view-company"></strong>
                </div>
                <div class="sp-um-detail"><span>Email verification</span><strong id="sp-view-verified"></strong></div>
                <div class="sp-um-detail"><span>Joined</span><strong id="sp-view-joined"></strong></div>
                <div class="sp-um-detail"><span>Last updated</span><strong id="sp-view-updated"></strong></div>
                <div class="sp-um-detail"><span>User ID</span><strong id="sp-view-id"></strong></div>
            </div>
        </div>
        <div class="sp-um-footer">
            <button type="button" class="sp-um-copy" id="sp-copy-email">
                <i class="bi bi-copy"></i> <span>Copy email</span>
            </button>
            <div class="sp-um-actions">
                <button type="button" id="sp-view-delete" class="sp-btn sp-btn-danger" data-um-switch="delete">
                    <i class="bi bi-trash3"></i> Delete
                </button>
                <button type="button" class="sp-btn sp-btn-primary" data-um-switch="edit">
                    <i class="bi bi-pencil"></i> Edit user
                </button>
            </div>
        </div>
    </section>

    <form id="sp-user-edit-panel" class="sp-um-panel" method="POST" action="#" hidden>
        @csrf
        @method('PUT')
        <div class="sp-um-header">
            <div><h2>Edit user</h2><p>Update account information and access</p></div>
            <button type="button" class="sp-um-close" data-um-close aria-label="Close dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sp-um-body">
            <div class="sp-um-alert" id="sp-edit-error" role="alert" hidden></div>
            <div class="sp-um-form-grid">
                <div class="sp-um-field">
                    <label for="sp-edit-name">Full name <span>*</span></label>
                    <input id="sp-edit-name" name="name" type="text" class="sp-input" maxlength="255" required>
                    <small data-edit-error="name"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-email">Email address <span>*</span></label>
                    <input id="sp-edit-email" name="email" type="email" class="sp-input" maxlength="255" required>
                    <small data-edit-error="email"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-phone">Phone number</label>
                    <input id="sp-edit-phone" name="phone" type="tel" class="sp-input" maxlength="25">
                    <small data-edit-error="phone"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-city">City</label>
                    <div class="sp-city-dropdown" id="sp-edit-city-dropdown">
                        <input id="sp-edit-city" name="city" type="text" class="sp-input"
                               placeholder="Select or search city" maxlength="100" autocomplete="off"
                               role="combobox" aria-autocomplete="list" aria-expanded="false"
                               aria-controls="sp-edit-city-options">
                        <button type="button" class="sp-city-arrow" id="sp-edit-city-toggle"
                                aria-label="Show cities" tabindex="-1"><i class="bi bi-chevron-down"></i></button>
                        <div class="sp-city-options" id="sp-edit-city-options" role="listbox" hidden></div>
                    </div>
                    <small data-edit-error="city"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-role">Account role <span>*</span></label>
                    <select id="sp-edit-role" name="role" class="sp-select" required>
                        <option value="customer">Customer</option>
                        <option value="provider">Provider</option>
                        <option value="admin">Administrator</option>
                    </select>
                    <small data-edit-error="role"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-status">Account status <span>*</span></label>
                    <select id="sp-edit-status" name="status" class="sp-select" required>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <small data-edit-error="status"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-password">New password</label>
                    <input id="sp-edit-password" name="password" type="password" class="sp-input"
                           placeholder="Leave blank to keep current" minlength="8" autocomplete="new-password">
                    <small data-edit-error="password"></small>
                </div>
                <div class="sp-um-field">
                    <label for="sp-edit-password-confirmation">Confirm new password</label>
                    <input id="sp-edit-password-confirmation" name="password_confirmation" type="password"
                           class="sp-input" placeholder="Repeat new password" minlength="8"
                           autocomplete="new-password">
                </div>
            </div>
            <p class="sp-um-note">Leave password fields empty to keep the current password.</p>
        </div>
        <div class="sp-um-footer">
            <button type="button" class="sp-btn sp-btn-secondary" data-um-switch="view">Back to details</button>
            <button type="submit" id="sp-edit-submit" class="sp-btn sp-btn-primary">
                <i class="bi bi-check-lg"></i> Save changes
            </button>
        </div>
    </form>

    <form id="sp-user-delete-panel" class="sp-um-panel" method="POST" action="#" hidden>
        @csrf
        @method('DELETE')
        <div class="sp-um-header">
            <div><h2>Delete user?</h2><p>Please confirm this action</p></div>
            <button type="button" class="sp-um-close" data-um-close aria-label="Close dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sp-um-body">
            <div class="sp-um-delete-icon"><i class="bi bi-trash3"></i></div>
            <p class="sp-um-delete-message">
                Are you sure you want to delete <strong id="sp-delete-name"></strong>?
            </p>
            <p class="sp-um-note">
                The account will be removed from the active list (soft delete).
                You cannot restore it from this panel.
            </p>
            <div class="sp-um-alert" id="sp-delete-error" role="alert" hidden></div>
        </div>
        <div class="sp-um-footer">
            <button type="button" class="sp-btn sp-btn-secondary" data-um-switch="view">Cancel</button>
            <button type="submit" id="sp-delete-submit" class="sp-btn sp-um-delete-button">
                <i class="bi bi-trash3"></i> Delete user
            </button>
        </div>
    </form>
</dialog>
