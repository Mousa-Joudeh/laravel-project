// tell the layout that this page has the film strip (adds space on the left)
document.body.classList.add('has-film');

// ---------- hero photos: change every 5 seconds with a camera flash ----------
const heroImages = document.querySelectorAll('#hero-frame img');
const heroDots = document.querySelectorAll('.hero-dots button');
const flash = document.querySelector('.hero-flash');
let current = 0;

function showPhoto(index) {
    current = index;
    flash.classList.remove('go');
    void flash.offsetWidth; // restart the flash animation
    flash.classList.add('go');

    heroImages.forEach(function (img, i) {
        img.classList.toggle('on', i === index);
    });
    heroDots.forEach(function (dot, i) {
        dot.classList.toggle('on', i === index);
    });
}

heroDots.forEach(function (dot, i) {
    dot.addEventListener('click', function () {
        showPhoto(i);
    });
});

setInterval(function () {
    showPhoto((current + 1) % heroImages.length);
}, 5200);

// ---------- film strip: moves slowly and follows the scroll ----------
const film = document.querySelector('.filmstrip-track');
if (film) {
    film.innerHTML += film.innerHTML; // twice, so it loops
    let offset = 0;

    function moveFilm() {
        offset += 0.2;
        const half = film.scrollHeight / 2;
        film.style.transform = 'translateY(-' + ((offset + window.scrollY * 0.35) % half) + 'px)';
        requestAnimationFrame(moveFilm);
    }
    moveFilm();
}


const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('in');
            observer.unobserve(entry.target);
        }
    });
});

document.querySelectorAll('.reveal').forEach(function (element) {
    observer.observe(element);
});