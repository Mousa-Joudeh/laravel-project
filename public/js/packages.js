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

