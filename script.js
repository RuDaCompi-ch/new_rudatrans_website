/**
 * script.js
 * Diese Datei steuert die dynamischen Funktionalitäten der Seite.
 * - Abrufen von Daten über unsere api.php
 * - Rendern der dynamischen Grid-Karten
 * - Steuerung der Carousels (sowohl auf den Karten als auch im Modal)
 * - Befüllen und Steuern des Detail-Modals
 */

document.addEventListener('DOMContentLoaded', () => {
    // Wenn die Seite geladen ist, rufen wir unsere Daten ab
    fetchMods();

    // Event Listener für den Schließen-Button im Detail-Modal
    document.getElementById('close-modal').addEventListener('click', closeModal);
    
    // Event Listener für den Schließen-Button im Vollbild-Modal
    if(document.getElementById('close-fullscreen')) {
        document.getElementById('close-fullscreen').addEventListener('click', closeFullscreenModal);
    }
});

// Globale Variable, um alle Mod-Daten zu speichern, sobald sie geladen sind
let globalModsData = [];

/**
 * Ruft die Mod-Daten von unserem Backend ab
 */
async function fetchMods() {
    try {
        const response = await fetch('api.php');
        const result = await response.json();

        if (result.status === 'success') {
            globalModsData = result.data;
            renderModGrid(globalModsData);
        } else {
            document.getElementById('mod-grid').innerHTML = `<p class="loading-text">Fehler beim Laden: ${result.message}</p>`;
        }
    } catch (error) {
        console.error("Fetch Error:", error);
        document.getElementById('mod-grid').innerHTML = `<p class="loading-text">Netzwerkfehler beim Laden der Daten.</p>`;
    }
}

/**
 * Erstellt die HTML-Struktur für das Grid basierend auf den Mod-Daten
 */
function renderModGrid(mods) {
    const gridContainer = document.getElementById('mod-grid');
    gridContainer.innerHTML = ''; // Vorherigen Lade-Text löschen

    if (mods.length === 0) {
        gridContainer.innerHTML = '<p class="loading-text">Keine Mods gefunden.</p>';
        return;
    }

    mods.forEach(mod => {
        // Ein Wrapper für die spezifischen Carousel-Daten dieser Karte
        const cardState = {
            currentImageIndex: 0,
            images: mod.images
        };

        // Das Grundgerüst einer jeden Mod-Karte
        const card = document.createElement('div');
        card.className = 'mod-card';

        // Die Struktur zusammenbauen
        card.innerHTML = `
            <div class="card-title">${mod.title}</div>
            <div class="card-carousel">
                <img id="card-img-${mod.id}" src="${mod.images[0]}" alt="${mod.title}">
            </div>
            <button class="card-btn details-btn" data-id="${mod.id}">Details</button>
            <a href="${mod.download_url}" class="card-btn" target="_blank" download>Download</a>
        `;

        gridContainer.appendChild(card);

        // Event Listener für die kleinen Carousels in der Übersicht
        const imgElement = card.querySelector(`#card-img-${mod.id}`);
        
        // Auto-Rotation alle 5 Sekunden
        let autoRotateInterval = setInterval(() => {
            cardState.currentImageIndex = (cardState.currentImageIndex + 1) % cardState.images.length;
            imgElement.src = cardState.images[cardState.currentImageIndex];
        }, 5000);

        // Klick auf das Bild im Grid öffnet die Detailkarte (Modal) statt Vollbild
        imgElement.addEventListener('click', () => {
            openModal(mod);
        });

        // Event Listener für den Details-Button
        card.querySelector('.details-btn').addEventListener('click', () => openModal(mod));
    });
}

/**
 * Hilfsfunktion, um die Punkte im kleinen Karussell zu rendern (aktuell nur von modal genutzt)
 */
function generateDots(total, activeIndex) {
    // Diese Funktion wird vom Grid nicht mehr gebraucht, aber zur Sicherheit behalten
    let dotsHtml = '';
    const maxDots = Math.min(total, 5); 
    for(let i=0; i<maxDots; i++) {
        dotsHtml += (i === Math.min(activeIndex, 4)) ? '●' : '○';
    }
    return dotsHtml;
}

// ==========================================
// Modal Logik
// ==========================================

let modalState = {
    images: [],
    currentIndex: 0
};

/**
 * Öffnet das Modal und füllt es mit den spezifischen Mod-Daten
 */
function openModal(mod) {
    // 1. Daten ins DOM einsetzen
    document.getElementById('spec-version').innerText = mod.version;
    document.getElementById('spec-date').innerText = new Date(mod.version_date).toLocaleDateString('de-DE');
    document.getElementById('spec-compat').innerText = mod.ets2_compat;
    document.getElementById('spec-type').innerText = mod.mod_type;
    document.getElementById('spec-kind').innerText = mod.mod_kind;
    document.getElementById('spec-filename').innerText = mod.filename;
    document.getElementById('spec-note').innerText = mod.note;
    
    // HTML Text in den Beschreibungscontainer einfügen
    document.getElementById('modal-description').innerHTML = mod.description;
    
    // Download Link setzen
    document.getElementById('modal-download-btn').href = mod.download_url;

    // 2. Status-Button Logik (UPDATE / NEW)
    const statusBtn = document.getElementById('modal-status-btn');
    if (mod.status === 'NEW' || mod.status === 'UPDATE') {
        statusBtn.innerText = mod.status; // Setzt den Text
        statusBtn.style.display = 'flex'; // Knopf sichtbar machen
    } else {
        statusBtn.style.display = 'none'; // Knopf verstecken
    }

    // 3. Modal Carousel initialisieren
    modalState.images = mod.images;
    modalState.currentIndex = 0;
    renderModalCarousel();

    // 4. Modal CSS aktivieren (einblenden)
    document.getElementById('mod-modal').classList.add('active');
}

/**
 * Schließt das Modal
 */
function closeModal() {
    document.getElementById('mod-modal').classList.remove('active');
}

/**
 * Aktualisiert alle Bilder und Anzeigen im großen Modal-Karussell
 */
function renderModalCarousel() {
    const mainImg = document.getElementById('modal-main-image');
    mainImg.src = modalState.images[modalState.currentIndex];

    // Thumbnails generieren oder aktualisieren (um Scroll-Position nicht zu verlieren)
    const thumbsContainer = document.getElementById('modal-thumbnails');
    // Helper für die Berechnung der exakten Ordnung für den Unendlichkeits-Effekt
    const total = modalState.images.length;
    const half = Math.floor(total / 2);

    if (thumbsContainer.children.length !== total) {
        thumbsContainer.innerHTML = '';
        modalState.images.forEach((imgSrc, index) => {
            const thumb = document.createElement('div');
            // Berechne Distanz für order
            let diff = index - modalState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;
            
            thumb.className = `thumbnail-box ${index === modalState.currentIndex ? 'active' : ''}`;
            thumb.style.backgroundImage = `url(${imgSrc})`;
            thumb.style.order = diff; // Hier passiert die Magie!
            
            thumb.addEventListener('click', () => {
                modalState.currentIndex = index;
                renderModalCarousel();
            });
            thumbsContainer.appendChild(thumb);
        });
    } else {
        Array.from(thumbsContainer.children).forEach((thumb, index) => {
            let diff = index - modalState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;

            thumb.className = `thumbnail-box ${index === modalState.currentIndex ? 'active' : ''}`;
            thumb.style.order = diff;
        });
    }
    
    // Klick auf das große Bild im Modal öffnet Vollbild
    mainImg.onclick = () => {
        openFullscreenModal({ images: modalState.images }); // Übergibt dynamisch die aktuellen Bilder
        fullscreenState.currentIndex = modalState.currentIndex; // Übernimmt den aktuellen Index
        renderFullscreenCarousel(); // Rendert nochmal für korrekten Start
    };
}

// Event Listener für Modal Navigation Left
document.querySelector('#mod-modal .prev-btn').addEventListener('click', () => {
    modalState.currentIndex = (modalState.currentIndex - 1 + modalState.images.length) % modalState.images.length;
    renderModalCarousel();
});

// Event Listener für Modal Navigation Right
document.querySelector('#mod-modal .next-btn').addEventListener('click', () => {
    modalState.currentIndex = (modalState.currentIndex + 1) % modalState.images.length;
    renderModalCarousel();
});

// ==========================================
// Vollbild Modal Logik
// ==========================================
let fullscreenState = { images: [], currentIndex: 0 };

function openFullscreenModal(mod) {
    fullscreenState.images = mod.images;
    fullscreenState.currentIndex = 0;
    renderFullscreenCarousel();
    document.getElementById('fullscreen-modal').classList.add('active');
}

function closeFullscreenModal() {
    document.getElementById('fullscreen-modal').classList.remove('active');
}

function renderFullscreenCarousel() {
    const mainImg = document.getElementById('fullscreen-main-image');
    mainImg.src = fullscreenState.images[fullscreenState.currentIndex];

    const fsThumbsContainer = document.getElementById('fullscreen-thumbnails');
    if (!fsThumbsContainer) return;

    const total = fullscreenState.images.length;
    const half = Math.floor(total / 2);

    if (fsThumbsContainer.children.length !== total) {
        fsThumbsContainer.innerHTML = '';
        fullscreenState.images.forEach((imgSrc, index) => {
            const thumb = document.createElement('div');
            let diff = index - fullscreenState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;
            
            thumb.className = `thumbnail-box ${index === fullscreenState.currentIndex ? 'active' : ''}`;
            thumb.style.backgroundImage = `url(${imgSrc})`;
            thumb.style.order = diff;
            
            thumb.addEventListener('click', () => {
                fullscreenState.currentIndex = index;
                renderFullscreenCarousel();
            });
            fsThumbsContainer.appendChild(thumb);
        });
    } else {
        Array.from(fsThumbsContainer.children).forEach((thumb, index) => {
            let diff = index - fullscreenState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;

            thumb.className = `thumbnail-box ${index === fullscreenState.currentIndex ? 'active' : ''}`;
            thumb.style.order = diff;
        });
    }
}

// Event Listener Fullscreen Navbar
if(document.getElementById('fs-prev')) {
    document.getElementById('fs-prev').addEventListener('click', () => {
        fullscreenState.currentIndex = (fullscreenState.currentIndex - 1 + fullscreenState.images.length) % fullscreenState.images.length;
        renderFullscreenCarousel();
    });
    document.getElementById('fs-next').addEventListener('click', () => {
        fullscreenState.currentIndex = (fullscreenState.currentIndex + 1) % fullscreenState.images.length;
        renderFullscreenCarousel();
    });
}
