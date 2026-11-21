// ---------- filters: apply as soon as one is changed ----------
const filtersForm = document.getElementById('filters-form');
if (filtersForm) {
    filtersForm.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('change', function () {
            filtersForm.submit();
        });
    });
}

// ---------- space cards: left / right arrows over the photos ----------
document.querySelectorAll('.slider').forEach(function (slider) {
    const slides = slider.querySelector('.slides');
    const total = slides.querySelectorAll('img').length;
    const prev = slider.querySelector('.prev');
    const next = slider.querySelector('.next');
    let index = 0;

    function show() {
        slides.style.transform = 'translateX(-' + (index * 100) + '%)';
    }

    if (prev) {
        prev.addEventListener('click', function () {
            index = index > 0 ? index - 1 : total - 1;
            show();
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            index = index < total - 1 ? index + 1 : 0;
            show();
        });
    }
});

// ---------- details page: click a small photo to show it big ----------
const mainPhoto = document.getElementById('main-photo');
document.querySelectorAll('.thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
        mainPhoto.src = thumb.dataset.src;
        document.querySelectorAll('.thumb').forEach(function (t) {
            t.classList.remove('on');
        });
        thumb.classList.add('on');
    });
});
// ---------- details page: add-ons change the price in the book card ----------
const bookCard = document.getElementById('book-card');
if (bookCard) {
    const basePrice = parseFloat(bookCard.dataset.base);

    document.querySelectorAll('.addon-check').forEach(function (check) {
        check.addEventListener('change', function () {
            let addonsPrice = 0;
            document.querySelectorAll('.addon-check:checked').forEach(function (checked) {
                addonsPrice += parseFloat(checked.dataset.price);
            });

            document.getElementById('card-addons').hidden = addonsPrice === 0;
            document.getElementById('card-addons-price').textContent = '+ JD ' + addonsPrice;
            document.getElementById('card-total').textContent = 'JD ' + (basePrice + addonsPrice);
        });
    });
}