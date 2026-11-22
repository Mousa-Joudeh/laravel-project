(function () {
    'use strict';

    const target = document.getElementById('booking-calendar-qr');
    if (!target || typeof QRCode === 'undefined') return;

    const calendarUrl = target.dataset.calendarUrl;
    if (!calendarUrl) return;

    try {
        new QRCode(target, {
            text: calendarUrl,
            width: 144,
            height: 144,
            colorDark: '#0b0b0b',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
        const link = target.closest('.ticket-qr-link');
        if (link) link.classList.add('is-ready');
    } catch (error) {
        // The calendar link remains usable if QR generation fails.
        console.warn('Could not display calendar QR code.', error);
    }
})();
