// ---------- list page: filters apply as soon as one is changed ----------
const filtersForm = document.getElementById('filters-form');
if (filtersForm) {
    filtersForm.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('change', function () {
            filtersForm.submit();
        });
    });
}

// ---------- details page: click a small photo to make it big ----------
const mainPhoto = document.getElementById('main-photo');
document.querySelectorAll('.pk-small').forEach(function (small) {
    small.addEventListener('click', function () {
        const big = mainPhoto.src;
        mainPhoto.src = small.src;
        small.src = big;
    });
});

// ---------- the booking window ----------
const modal = document.getElementById('booking-modal');

if (modal) {
    const minHours = parseInt(modal.dataset.min);
    const maxHours = parseInt(modal.dataset.max);
    const price = parseFloat(modal.dataset.price);
    const pricing = modal.dataset.pricing; // fixed or hourly
    const slotsUrl = modal.dataset.slotsUrl;

    let hours = minHours;
    let date = '';
    let time = '';

    const dateInput = document.getElementById('booking-date');
    const dateStrip = document.getElementById('date-strip');
    const slotGrid = document.getElementById('slot-grid');
    const confirmButton = document.getElementById('confirm-booking');

    // open and close
    document.getElementById('open-booking').addEventListener('click', function () {
        modal.classList.add('open');
        if (date === '') {
            setDate(toDateText(new Date()));
        }
    });
    document.getElementById('close-booking').addEventListener('click', function () {
        modal.classList.remove('open');
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.classList.remove('open');
        }
    });

    // a date as "2026-11-25"
    function toDateText(day) {
        const month = String(day.getMonth() + 1).padStart(2, '0');
        const dayNumber = String(day.getDate()).padStart(2, '0');
        return day.getFullYear() + '-' + month + '-' + dayNumber;
    }

    // the next 14 days as buttons
    for (let i = 0; i < 14; i++) {
        const day = new Date();
        day.setDate(day.getDate() + i);

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.date = toDateText(day);
        button.innerHTML = '<small>' + day.toLocaleDateString('en-GB', { weekday: 'short' }) + '</small><b>' + day.getDate() + '</b>';
        button.addEventListener('click', function () {
            setDate(button.dataset.date);
        });
        dateStrip.appendChild(button);
    }

    dateInput.min = toDateText(new Date());
    dateInput.addEventListener('change', function () {
        if (dateInput.value) {
            setDate(dateInput.value);
        }
    });

    function setDate(value) {
        date = value;
        dateInput.value = value;

        dateStrip.querySelectorAll('button').forEach(function (button) {
            button.classList.toggle('on', button.dataset.date === value);
        });

        const dayName = new Date(value + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long' });
        document.getElementById('day-name').innerHTML = '<i class="bi bi-calendar-event"></i> ' + dayName;

        loadSlots();
    }

    // duration
    function setHours(value) {
        hours = Math.max(minHours, Math.min(maxHours, value));
        document.getElementById('hours-text').textContent = hours + ' hours';
        if (date !== '') {
            loadSlots();
        }
        updateSummary();
    }
    document.getElementById('hours-minus').addEventListener('click', function () {
        setHours(hours - 1);
    });
    document.getElementById('hours-plus').addEventListener('click', function () {
        setHours(hours + 1);
    });

    // free times from the server
    function loadSlots() {
        time = '';
        slotGrid.innerHTML = '<div class="slot-empty">Loading...</div>';
        updateSummary();

        fetch(slotsUrl + '?date=' + date + '&hours=' + hours)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                slotGrid.innerHTML = '';

                if (data.slots.length === 0) {
                    slotGrid.innerHTML = '<div class="slot-empty">No free times on this day. Try another date.</div>';
                    return;
                }

                data.slots.forEach(function (slotTime) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'slot';
                    button.textContent = slotTime;
                    button.addEventListener('click', function () {
                        slotGrid.querySelectorAll('.slot').forEach(function (other) {
                            other.classList.remove('on');
                        });
                        button.classList.add('on');
                        time = slotTime;
                        updateSummary();
                    });
                    slotGrid.appendChild(button);
                });
            });
    }

    // add-ons
    document.querySelectorAll('.addon-check').forEach(function (check) {
        check.addEventListener('change', updateSummary);
    });

    // prices + the hidden fields sent to the booking
    function updateSummary() {
        let packagePrice = price;
        if (pricing === 'hourly') {
            packagePrice = price * hours;
        }

        let addonsPrice = 0;
        document.querySelectorAll('.addon-check:checked').forEach(function (check) {
            const addonPrice = parseFloat(check.dataset.price);
            if (check.dataset.pricing === 'hourly') {
                addonsPrice += addonPrice * hours;
            } else {
                addonsPrice += addonPrice;
            }
        });

        const total = packagePrice + addonsPrice;

        document.getElementById('sum-package').textContent = 'JD ' + packagePrice;
        document.getElementById('sum-addons').textContent = 'JD ' + addonsPrice;
        document.getElementById('sum-addons-row').hidden = addonsPrice === 0;
        document.getElementById('sum-total').textContent = 'JD ' + total;
        document.getElementById('total-price').value = total;

        if (date !== '' && time !== '') {
            const startHour = parseInt(time.split(':')[0]);
            const endHour = String(startHour + hours).padStart(2, '0');

            document.getElementById('starts-at').value = date + ' ' + time;
            document.getElementById('ends-at').value = date + ' ' + endHour + ':00';
            document.getElementById('sum-when').textContent = date + ' · ' + time + ' · ' + hours + 'h';
            confirmButton.disabled = false;
        } else {
            document.getElementById('sum-when').textContent = 'Pick a date and a time';
            confirmButton.disabled = true;
        }
    }

    updateSummary();
}