<div class="modal" id="booking-modal"
     data-min="{{ $service->min_hours }}"
     data-max="{{ $service->max_hours }}"
     data-price="{{ $service->price }}"
     data-pricing="{{ $service->pricing_type }}"
     data-slots-url="{{ route('studios.slots', $service->studio_id) }}">

    <form action="{{ route('services.bookings.store', $service->id) }}" method="POST" class="modal-box">
        @csrf
        <input type="hidden" name="studio_id" value="{{ $service->studio_id }}">
        <input type="hidden" name="service_id" value="{{ $service->id }}">
        <input type="hidden" name="mode" value="service">
        <input type="hidden" name="starts_at" id="starts-at">
        <input type="hidden" name="ends_at" id="ends-at">
        <input type="hidden" name="total_price" id="total-price">

        <div class="modal-head">
            <div>
                <span class="eyebrow"><b>●</b> Booking</span>
                <h3>{{ $service->title }}</h3>
                <p class="muted">
                    JD {{ number_format($service->price, 0) }}
                    {{ $service->pricing_type === 'hourly' ? '/ hour' : '' }}
                    · {{ $service->studio->name }}
                </p>
            </div>
            <button type="button" class="close-btn" id="close-booking"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="modal-body">
            {{-- 1. date + day --}}
            <div class="step">
                <span class="step-label"><span class="n">1</span> Booking date</span>
                <div class="date-strip" id="date-strip"></div>
                <div class="date-row">
                    <input type="date" class="input" id="booking-date">
                    <span class="day-chip" id="day-name"><i class="bi bi-calendar-event"></i> Day</span>
                </div>
            </div>

            {{-- 2. duration --}}
            <div class="step">
                <div class="step-row">
                    <span class="step-label"><span class="n">2</span> Duration</span>
                    <div class="stepper">
                        <button type="button" id="hours-minus"><i class="bi bi-dash"></i></button>
                        <span id="hours-text">{{ $service->min_hours }} hours</span>
                        <button type="button" id="hours-plus"><i class="bi bi-plus"></i></button>
                    </div>
                </div>
                <small class="muted">From {{ $service->min_hours }} to {{ $service->max_hours }} hours.</small>
            </div>

            {{-- 3. time --}}
            <div class="step">
                <span class="step-label"><span class="n">3</span> Available time</span>
                <div class="slot-grid" id="slot-grid">
                    <div class="slot-empty">Pick a date first.</div>
                </div>
            </div>
        </div>

        <div class="modal-foot">
            <div class="sum-row"><span>{{ $service->title }}</span><span id="sum-package">JD 0</span></div>
            <div class="sum-row" id="sum-addons-row" hidden><span>Add-ons</span><span id="sum-addons">JD 0</span></div>
            <div class="sum-total">
                <div>
                    <small class="muted" id="sum-when">Pick a date and a time</small>
                    <strong id="sum-total">JD 0</strong>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" id="confirm-booking" disabled><i class="bi bi-check2"></i> Confirm booking</button>
            </div>
            <p class="note"><i class="bi bi-shield-check"></i> Pay at the studio. Free cancellation up to 24 hours before.</p>
        </div>
    </form>
</div>