// Funkcja do ładowania komponentów HTML
function loadComponent(id, file, callback) {
    const target = document.getElementById(id);
    if (!target) return;

    fetch(file)
        .then(res => {
            if (!res.ok) throw new Error(`Nie udało się załadować ${file}`);
            return res.text();
        })
        .then(html => {
            target.innerHTML = html;
            lucide.createIcons();
            if (callback) callback();
        })
        .catch(err => console.error(err));
}

// Inicjalizacja wszystkich sekcji dynamicznych
function initDynamicSections() {
    // Sekcja O nas (tylko na głównej)
    loadComponent('o-nas', 'o_nas.html');

    // Sekcja Wydarzenia 2025 (tylko na głównej)
    loadComponent('wydarzenia', 'wydarzenia_2025.html', () => {
        if (typeof buildGallery === 'function') buildGallery();
    });

    // Sekcja Film Orzech
    loadComponent('film-orzech', 'film_sekcja.html');

    // Sekcja Dekalog Orzecha
    loadComponent('dekalog-orzecha', 'dekalog_sekcja.html');

    // Sekcja Kalendarium 2026 (na głównej i v2)
    loadComponent('kalendarium', 'kalendarium_2026.html');

    // Sekcja Działalność
    loadComponent('dzialalnosc', 'dzialalnosc.html');

    // Sekcja 1.5% Podatku
    loadComponent('podatek', 'podatek_sekcja.html');
}

// Inicjalizacja przy ładowaniu strony
document.addEventListener('DOMContentLoaded', () => {
    initDynamicSections();
    lucide.createIcons();
    if (typeof buildGallery === 'function') buildGallery();
});

// ── Lightbox ─────────────────────────────────────────────
const lightbox = document.getElementById('lightbox');
const lbImg = document.getElementById('lightbox-img');
const lbCaption = document.getElementById('lb-caption');
const lbCounter = document.getElementById('lb-counter');
const lbClose = document.getElementById('lb-close');
const lbPrev = document.getElementById('lb-prev');
const lbNext = document.getElementById('lb-next');

let galleryItems = [];
let currentIndex = 0;

// Lista plików w galerii
const galleryFiles = [
    { src: 'images/galeria_1.jpg', alt: 'Wydarzenie 1' },
];

function buildGallery() {
    const galleryContainer = document.getElementById('dynamic-gallery');
    if (!galleryContainer) return;

    galleryContainer.innerHTML = '';

    galleryFiles.forEach((file, i) => {
        const item = document.createElement('div');
        item.className = 'gallery-item bg-gray-200';
        item.dataset.index = i;
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        item.setAttribute('aria-label', `Otwórz zdjęcie: ${file.alt}`);

        item.innerHTML = `<img src="${file.src}" data-full="${file.src}" alt="${file.alt}">`;

        item.addEventListener('click', () => openLightbox(i));
        item.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') openLightbox(i); });

        galleryContainer.appendChild(item);
    });

    galleryItems = Array.from(document.querySelectorAll('.gallery-item'));
}

function openLightbox(index) {
    if (!galleryItems.length) return;
    currentIndex = index;
    const img = galleryItems[index].querySelector('img');
    if (lbImg) {
        lbImg.src = img.dataset.full || img.src;
        lbImg.alt = img.alt;
    }
    if (lbCaption) lbCaption.textContent = img.alt;
    if (lbCounter) lbCounter.textContent = (index + 1) + ' / ' + galleryItems.length;
    if (lightbox) lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (lbClose) lbClose.focus();
}

function closeLightbox() {
    if (lightbox) lightbox.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => { if (lbImg) lbImg.src = ''; }, 350);
}

function showPrev() {
    if (!galleryItems.length) return;
    currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
    openLightbox(currentIndex);
}

function showNext() {
    if (!galleryItems.length) return;
    currentIndex = (currentIndex + 1) % galleryItems.length;
    openLightbox(currentIndex);
}

// Event Listeners
if (lbClose) lbClose.addEventListener('click', closeLightbox);
if (lbPrev) lbPrev.addEventListener('click', showPrev);
if (lbNext) lbNext.addEventListener('click', showNext);
if (lightbox) lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });

document.addEventListener('keydown', e => {
    if (!lightbox || !lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') showPrev();
    if (e.key === 'ArrowRight') showNext();
});

// Kopiowanie KRS do schowka
function copyKRS() {
    const krs = "0000086185";
    navigator.clipboard.writeText(krs).then(() => {
        const msg = document.getElementById('copy-msg');
        if (msg) {
            msg.classList.remove('opacity-0');
            setTimeout(() => msg.classList.add('opacity-0'), 2000);
        }
    }).catch(err => {
        const textArea = document.createElement("textarea");
        textArea.value = krs;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            const msg = document.getElementById('copy-msg');
            if (msg) {
                msg.classList.remove('opacity-0');
                setTimeout(() => msg.classList.add('opacity-0'), 2000);
            }
        } catch (err) { console.error('Błąd kopiowania', err); }
        document.body.removeChild(textArea);
    });
}

// Obsługa menu mobilnego
const mobileBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
const mobileLinks = document.querySelectorAll('.mobile-link');

if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    mobileLinks.forEach(link => link.addEventListener('click', () => mobileMenu.classList.add('hidden')));
}

// Nav Glass Effects
const nav = document.querySelector('nav');
if (nav) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            nav.classList.add('shadow-md', 'border-gray-200');
            nav.classList.remove('border-transparent');
        } else {
            nav.classList.remove('shadow-md', 'border-gray-200');
            nav.classList.add('border-transparent');
        }
    });
}
