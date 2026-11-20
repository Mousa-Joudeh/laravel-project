<div class="form-card">
    <div class="form-section">
        <div><h4>The service</h4><p>What you offer and who does it.</p></div>
        <div class="form-grid">
            <div class="field full">
                <label class="label" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title', $service->title) }}" class="input @error('title') is-invalid @enderror" required maxlength="150" placeholder="e.g. Photographer with 2 cameras">
                @error('title') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="specialty">Specialty</label>
                <select id="specialty" name="specialty" class="select" required data-ai-fact>
                    @foreach (\App\Models\Service::SPECIALTIES as $key => $label)
                        <option value="{{ $key }}" @selected(old('specialty', $service->specialty) === $key)>{{ $label }}</option>
                    @endforeach
                    @if ($service->specialty && ! array_key_exists($service->specialty, \App\Models\Service::SPECIALTIES))
                        <option value="{{ $service->specialty }}" selected>{{ $service->specialtyLabel() }}</option>
                    @endif
                </select>
            </div>
            <div class="field">
                <label class="label" for="kind">How customers book it</label>
                <select id="kind" name="kind" class="select" required>
                    <option value="core" @selected(old('kind', $service->kind) === 'core')>On its own</option>
                    <option value="addon" @selected(old('kind', $service->kind) === 'addon')>Only as an add-on</option>
                    <option value="both" @selected(old('kind', $service->kind) === 'both')>Both</option>
                </select>
            </div>
            <div class="field full">
                <div class="row-between">
                    <label class="label" for="description">Description</label>
                    <button type="button" class="ai-btn" data-ai="service" data-target="#description" data-url="{{ route('provider.ai.describe') }}"><i class="bi bi-stars"></i> Write with AI</button>
                </div>
                <textarea id="description" name="description" class="textarea @error('description') is-invalid @enderror" rows="5" required minlength="30">{{ old('description', $service->description) }}</textarea>
                @error('description') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="form-section">
        <div><h4>Where</h4><p>The space this service works in. It reserves that space when booked, and it cannot be changed later.</p></div>
        <div class="form-grid">
            <div class="field full">
                <label class="label" for="studio_id">Space</label>
                <select id="studio_id" name="studio_id" class="select @error('studio_id') is-invalid @enderror" data-studio-select required @disabled($service->exists)>
                    <option value="">Choose a space</option>
                    @foreach ($studios as $studio)
                        <option value="{{ $studio->id }}" @selected(old('studio_id', $service->studio_id) == $studio->id)>{{ $studio->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field full" data-delivery>
                <span class="label">Delivery</span>
                <div class="segmented">
                    @foreach (['at_studio' => 'At the studio', 'at_client' => 'At the client', 'online' => 'Online'] as $value => $label)
                        <label><input type="radio" name="delivery_mode" value="{{ $value }}" @checked(old('delivery_mode', $service->delivery_mode) === $value)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="field full" data-studio-only hidden>
                <label class="switch"><input type="checkbox" name="price_includes_studio" value="1" @checked(old('price_includes_studio', $service->price_includes_studio))> The price already includes the space</label>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div><h4>Price & time</h4><p>Hourly or one fixed price.</p></div>
        <div class="form-grid">
            <div class="field">
                <span class="label">Pricing</span>
                <div class="segmented">
                    <label><input type="radio" name="pricing_type" value="hourly" @checked(old('pricing_type', $service->pricing_type) === 'hourly')><span>Per hour</span></label>
                    <label><input type="radio" name="pricing_type" value="fixed" @checked(old('pricing_type', $service->pricing_type) === 'fixed')><span>Fixed</span></label>
                </div>
            </div>
            <div class="field">
                <label class="label" for="price">Price (JD)</label>
                <input id="price" type="number" step="0.001" min="1" name="price" value="{{ old('price', $service->price) }}" class="input @error('price') is-invalid @enderror" required>
                @error('price') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="min_hours">Minimum hours</label>
                <input id="min_hours" type="number" min="1" max="24" name="min_hours" value="{{ old('min_hours', $service->min_hours) }}" class="input" required>
            </div>
            <div class="field">
                <label class="label" for="max_hours">Maximum hours</label>
                <input id="max_hours" type="number" min="1" max="24" name="max_hours" value="{{ old('max_hours', $service->max_hours) }}" class="input @error('max_hours') is-invalid @enderror" required>
                @error('max_hours') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field full">
                <label class="label" for="resource_group">Team name <span class="opt">optional</span></label>
                <input id="resource_group" name="resource_group" value="{{ old('resource_group', $service->resource_group) }}" class="input" placeholder="e.g. main-photographers">
                <span class="help">Services with the same team name cannot be booked at the same time (same people).</span>
            </div>
            @if ($service->exists)
                <div class="field full">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))> Active (customers can book it)</label>
                </div>
            @endif
        </div>
    </div>

    <div class="form-section">
        <div><h4>Photo</h4><p>One clear photo of the service in action.</p></div>
        <div>
            @if ($service->image)
                <div class="thumbs" style="margin:0 0 12px;grid-template-columns:repeat(auto-fill,140px)"><div class="thumb"><img src="{{ $service->imageUrl() }}" alt=""></div></div>
            @endif
            <label class="upload">
                <i class="bi bi-cloud-arrow-up"></i>
                <b>{{ $service->image ? 'Replace the photo' : 'Upload a photo' }}</b>
                <span class="help">JPG, PNG or WEBP · up to 4 MB</span>
                <input type="file" name="image" accept="image/*" data-preview="#service-photo">
            </label>
            <div class="thumbs" id="service-photo"></div>
            @error('image') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-foot">
        <a href="{{ route('provider.services.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $service->exists ? 'Save changes' : 'Send for approval' }}</button>
    </div>
</div>
