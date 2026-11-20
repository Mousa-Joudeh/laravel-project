@php $chosen = old('services', $package->exists ? $package->services->pluck('id')->all() : []); @endphp

<div class="form-card">
    <div class="form-section">
        <div><h4>The package</h4><p>A clear name and what the day looks like.</p></div>
        <div class="form-grid">
            <div class="field full">
                <label class="label" for="name">Package name</label>
                <input id="name" name="name" value="{{ old('name', $package->name) }}" class="input @error('name') is-invalid @enderror" required maxlength="150" placeholder="e.g. Golden Hour Wedding">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="type">Package type</label>
                <select id="type" name="type" class="select" required data-ai-fact>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $package->type) === $key)>{{ $label }}</option>
                    @endforeach
                    @if ($package->type && ! array_key_exists($package->type, $types))
                        <option value="{{ $package->type }}" selected>{{ $package->typeLabel() }}</option>
                    @endif
                </select>
            </div>
            <div class="field">
                <label class="label" for="price">Price (JD, fixed)</label>
                <input id="price" type="number" step="0.001" min="1" name="price" value="{{ old('price', $package->price) }}" class="input @error('price') is-invalid @enderror" required>
                @error('price') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field full">
                <div class="row-between">
                    <label class="label" for="description">Description</label>
                    <button type="button" class="ai-btn" data-ai="package" data-target="#description" data-url="{{ route('provider.ai.describe') }}"><i class="bi bi-stars"></i> Write with AI</button>
                </div>
                <textarea id="description" name="description" class="textarea @error('description') is-invalid @enderror" rows="5" required minlength="40">{{ old('description', $package->description) }}</textarea>
                @error('description') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="form-section">
        <div><h4>What is inside</h4><p>Pick the space, then the services of that space to bundle with it. Everything shares the same booking time.</p></div>
        <div class="stack">
            <div class="field">
                <label class="label" for="studio_id">Space</label>
                <select id="studio_id" name="studio_id" class="select @error('studio_id') is-invalid @enderror" data-ai-fact required @disabled($package->exists)>
                    <option value="">Choose a space</option>
                    @foreach ($studios as $studio)
                        <option value="{{ $studio->id }}" @selected(old('studio_id', $package->studio_id) == $studio->id)>{{ $studio->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <span class="label">Services included</span>
                @forelse ($services as $service)
                    <label class="check">
                        <input type="checkbox" name="services[]" value="{{ $service->id }}" data-label="{{ $service->title }}" @checked(in_array($service->id, $chosen))>
                        {{ $service->title }} <span class="count">JD {{ (float) $service->price }} {{ $service->priceSuffix() }}</span>
                    </label>
                @empty
                    <p class="help">You have no services yet. <a href="{{ route('provider.services.create') }}" class="accent">Add one</a>.</p>
                @endforelse
                @error('services') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="form-section">
        <div><h4>Time & dates</h4><p>How long the session can be, and when the offer runs.</p></div>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="min_hours">Minimum hours</label>
                <input id="min_hours" type="number" min="1" max="24" name="min_hours" value="{{ old('min_hours', $package->min_hours) }}" class="input" required>
            </div>
            <div class="field">
                <label class="label" for="max_hours">Maximum hours</label>
                <input id="max_hours" type="number" min="1" max="24" name="max_hours" value="{{ old('max_hours', $package->max_hours) }}" class="input @error('max_hours') is-invalid @enderror" required>
                @error('max_hours') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="start_date">Starts <span class="opt">optional</span></label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $package->start_date?->format('Y-m-d')) }}" class="input">
            </div>
            <div class="field">
                <label class="label" for="end_date">Ends <span class="opt">optional</span></label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $package->end_date?->format('Y-m-d')) }}" class="input @error('end_date') is-invalid @enderror">
                @error('end_date') <p class="error">{{ $message }}</p> @enderror
            </div>
            @if ($package->exists)
                <div class="field full">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))> Active on the website</label>
                </div>
            @endif
        </div>
    </div>

    <div class="form-section">
        <div><h4>Photos</h4><p>Photos from real sessions of this package.</p></div>
        <div>
            @if ($package->exists && $package->images->isNotEmpty())
                <div class="thumbs" style="margin:0 0 12px">
                    @foreach ($package->images as $image)
                        <div class="thumb">
                            <img src="{{ $image->url() }}" alt="">
                            <div class="thumb-actions">
                                <button type="submit" form="delete-image-{{ $image->id }}" title="Delete photo"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <label class="upload">
                <i class="bi bi-cloud-arrow-up"></i>
                <b>Drop photos here or click to upload</b>
                <span class="help">Up to 8 photos · 4 MB each</span>
                <input type="file" name="images[]" accept="image/*" multiple data-preview="#package-photos" @if (! $package->exists) required @endif>
            </label>
            <div class="thumbs" id="package-photos"></div>
            @error('images') <p class="error">{{ $message }}</p> @enderror
            @error('images.*') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-foot">
        <a href="{{ route('provider.packages.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $package->exists ? 'Save changes' : 'Create package' }}</button>
    </div>
</div>
