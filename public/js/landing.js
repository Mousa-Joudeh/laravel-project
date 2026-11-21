

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

// ---------- film ribbon: each frame starts at a different place on the curve ----------
const ribbonFrames = document.querySelectorAll('.ribbon-frame');
ribbonFrames.forEach(function (frame, i) {
    frame.style.animationDelay = '-' + (i * 50 / ribbonFrames.length) + 's';
});

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