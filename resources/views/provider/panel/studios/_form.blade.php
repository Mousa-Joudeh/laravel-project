{{-- shared by create and edit --}}
@php
    $equipment = old('equipment', $studio->included_equipment ?: [['item' => '', 'qty' => 1, 'detail' => '']]);
    $checked = old('facilities', $studio->facilities ?? []);
@endphp

<div class="form-card">
    <div class="form-section">
        <div><h4>Basics</h4><p>The name and type customers search for.</p></div>
        <div class="form-grid">
            <div class="field full">
                <label class="label" for="name">Space name</label>
                <input id="name" name="name" value="{{ old('name', $studio->name) }}" class="input @error('name') is-invalid @enderror" required maxlength="150" placeholder="e.g. Daylight Portrait Room">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="studio_type_id">Studio type</label>
                <select id="studio_type_id" name="studio_type_id" class="select @error('studio_type_id') is-invalid @enderror" required data-ai-fact>
                    <option value="">Choose a type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(old('studio_type_id', $studio->studio_type_id) == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('studio_type_id') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="select @error('category_id') is-invalid @enderror" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $studio->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <span class="help">Equipped = equipment is included on site.</span>
            </div>
            <div class="field full">
                <div class="row-between">
                    <label class="label" for="description">Description</label>
                    <button type="button" class="ai-btn" data-ai="space" data-target="#description" data-url="{{ route('provider.ai.describe') }}"><i class="bi bi-stars"></i> Write with AI</button>
                </div>
                <textarea id="description" name="description" class="textarea @error('description') is-invalid @enderror" rows="6" required minlength="40">{{ old('description', $studio->description) }}</textarea>
                @error('description') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="form-section">
        <div><h4>Location & size</h4><p>Where it is and how many people fit.</p></div>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="city">City</label>
                <select id="city" name="city" class="select" required>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(old('city', $studio->city) === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="address">Address</label>
                <input id="address" name="address" value="{{ old('address', $studio->address) }}" class="input @error('address') is-invalid @enderror" required>
                @error('address') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="capacity">Max people</label>
                <input id="capacity" type="number" name="capacity" min="1" value="{{ old('capacity', $studio->capacity) }}" class="input @error('capacity') is-invalid @enderror" required>
                @error('capacity') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="area_sqm">Area (m²) <span class="opt">optional</span></label>
                <input id="area_sqm" type="number" step="0.01" name="area_sqm" value="{{ old('area_sqm', $studio->area_sqm) }}" class="input">
            </div>
            <div class="field">
                <label class="label" for="hourly_price">Hourly price (JD)</label>
                <input id="hourly_price" type="number" step="0.001" min="1" name="hourly_price" value="{{ old('hourly_price', $studio->hourly_price) }}" class="input @error('hourly_price') is-invalid @enderror" required>
                @error('hourly_price') <p class="error">{{ $message }}</p> @enderror
            </div>
            @if ($studio->exists && $studio->status === 'approved')
                <div class="field" style="align-content:end">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $studio->is_active))> Visible on the website</label>
                </div>
            @elseif ($studio->exists)
                <input type="hidden" name="is_active" value="1">
            @endif
        </div>
    </div>

    <div class="form-section">
        <div><h4>Equipment</h4><p>Cameras, microphones, lights… Customers see the quantity of each item.</p></div>
        <div data-repeater>
            <div data-repeater-list>
                @foreach ($equipment as $i => $row)
                    <div class="repeater-row">
                        <input name="equipment[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" class="input" placeholder="Item, e.g. Cameras">
                        <input name="equipment[{{ $i }}][qty]" type="number" min="1" value="{{ $row['qty'] ?? 1 }}" class="input" aria-label="Quantity">
                        <input name="equipment[{{ $i }}][detail]" value="{{ $row['detail'] ?? '' }}" class="input detail" placeholder="Model / detail">
                        <button type="button" class="icon-btn danger" data-repeater-remove aria-label="Remove"><i class="bi bi-x-lg"></i></button>
                    </div>
                @endforeach
            </div>
            <template>
                <div class="repeater-row">
                    <input name="equipment[__i__][item]" class="input" placeholder="Item, e.g. Microphones">
                    <input name="equipment[__i__][qty]" type="number" min="1" value="1" class="input" aria-label="Quantity">
                    <input name="equipment[__i__][detail]" class="input detail" placeholder="Model / detail">
                    <button type="button" class="icon-btn danger" data-repeater-remove aria-label="Remove"><i class="bi bi-x-lg"></i></button>
                </div>
            </template>
            <button type="button" class="btn btn-soft btn-sm" data-repeater-add style="margin-top:10px"><i class="bi bi-plus"></i> Add equipment</button>
            <p class="help" style="margin-top:6px">Leave it empty for an empty space.</p>
        </div>
    </div>

    <div class="form-section">
        <div><h4>Facilities</h4><p>What else is on site.</p></div>
        <div class="chip-group">
            @foreach ($facilities as $facility)
                <label class="chip">
                    <input type="checkbox" name="facilities[]" value="{{ $facility }}" @checked(in_array($facility, $checked))>
                    <span>{{ $facility }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="form-section">
        <div><h4>Photos</h4><p>Up to 10 photos of this exact space. The first one is the cover.</p></div>
        <div>
            @if ($studio->exists && $studio->images->isNotEmpty())
                <div class="thumbs" style="margin:0 0 12px">
                    @foreach ($studio->images as $image)
                        <div class="thumb">
                            <img src="{{ $image->url() }}" alt="">
                            @if ($image->is_cover)
                                <span class="tag tag-dark cover-badge" style="height:18px;font-size:10px">Cover</span>
                            @endif
                            <div class="thumb-actions">
                                @unless ($image->is_cover)
                                    <button type="submit" form="cover-{{ $image->id }}" title="Make cover"><i class="bi bi-star"></i></button>
                                @endunless
                                <button type="submit" form="delete-image-{{ $image->id }}" title="Delete photo"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <label class="upload">
                <i class="bi bi-cloud-arrow-up"></i>
                <b>Drop photos here or click to upload</b>
                <span class="help">JPG, PNG or WEBP · up to 4 MB each</span>
                <input type="file" name="images[]" accept="image/*" multiple data-preview="#new-photos" @if (! $studio->exists) required @endif>
            </label>
            <div class="thumbs" id="new-photos"></div>
            @error('images') <p class="error">{{ $message }}</p> @enderror
            @error('images.*') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-foot">
        <a href="{{ $studio->exists ? route('provider.studios.show', $studio) : route('provider.studios.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $studio->exists ? 'Save changes' : 'Send for approval' }}</button>
    </div>
</div>
