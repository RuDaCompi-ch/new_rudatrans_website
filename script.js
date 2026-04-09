/**
 * script.js
 * Diese Datei steuert die dynamischen Funktionalitäten der Seite.
 */

document.addEventListener('DOMContentLoaded', () => {
    fetchMods();
    document.getElementById('close-modal').addEventListener('click', closeModal);
    if(document.getElementById('close-fullscreen')) {
        document.getElementById('close-fullscreen').addEventListener('click', closeFullscreenModal);
    }
});

let globalModsData = [];

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
        document.getElementById('mod-grid').innerHTML = `<p class="loading-text">Netzwerkfehler beim Laden.</p>`;
    }
}

// Helper Funktion für visuelles Overlay Banner 
function updateRibbon(element, badgeStr) {
    if (!element) return;
    if (badgeStr === 'NEW') {
        element.style.display = 'block';
        element.className = 'badge-ribbon ribbon-new';
        element.innerText = 'NEW';
    } else if (badgeStr === 'UPDATE') {
        element.style.display = 'block';
        element.className = 'badge-ribbon ribbon-update';
        element.innerText = 'UPDATE';
    } else {
        element.style.display = 'none';
        element.className = 'badge-ribbon';
        element.innerText = '';
    }
}

function renderModGrid(mods) {
    const gridContainer = document.getElementById('mod-grid');
    gridContainer.innerHTML = '';

    if (mods.length === 0) {
        gridContainer.innerHTML = '<p class="loading-text">Keine Mods gefunden.</p>';
        return;
    }

    mods.forEach(mod => {
        const cardState = { currentImageIndex: 0, images: mod.images };
        const card = document.createElement('div');
        card.className = 'mod-card';

        // Ribbon in card-carousel packen. card-carousel braucht position: relative
        card.innerHTML = `
            <div class="card-title">${mod.title}</div>
            <div class="card-carousel" style="position: relative; overflow: hidden;">
                <div id="grid-ribbon-${mod.id}" class="badge-ribbon" style="display: none;"></div>
                <img id="card-img-${mod.id}" src="${mod.images[0].image_url}" alt="${mod.title}">
            </div>
            <button class="card-btn details-btn" data-id="${mod.id}">Details</button>
            <a href="${mod.download_url}" class="card-btn" target="_blank" download>Download</a>
        `;

        gridContainer.appendChild(card);

        const imgElement = card.querySelector(`#card-img-${mod.id}`);
        const ribbonEl = card.querySelector(`#grid-ribbon-${mod.id}`);
        
        // Ribbon fest an die Karte heften basierend auf dem Mod-Status
        updateRibbon(ribbonEl, mod.status);

        let autoRotateInterval = setInterval(() => {
            cardState.currentImageIndex = (cardState.currentImageIndex + 1) % cardState.images.length;
            imgElement.src = cardState.images[cardState.currentImageIndex].image_url;
        }, 5000);

        imgElement.addEventListener('click', () => openModal(mod));
        card.querySelector('.details-btn').addEventListener('click', () => openModal(mod));
    });
}

// Modal Logik
let modalState = { images: [], currentIndex: 0 };

function openModal(mod) {
    document.getElementById('spec-version').innerText = mod.version;
    document.getElementById('spec-date').innerText = new Date(mod.version_date).toLocaleDateString('de-DE');
    document.getElementById('spec-compat').innerText = mod.ets2_compat;
    document.getElementById('spec-type').innerText = mod.mod_type;
    document.getElementById('spec-kind').innerText = mod.mod_kind;
    document.getElementById('spec-filename').innerText = mod.filename;
    document.getElementById('spec-note').innerText = mod.note;
    document.getElementById('modal-description').innerHTML = mod.description;
    document.getElementById('modal-download-btn').href = mod.download_url;

    // Der alte modale Status Button wird versteckt, da Badges jetzt als Overlay in der Bildecke sind
    const statusBtn = document.getElementById('modal-status-btn');
    if (statusBtn) statusBtn.style.display = 'none';

    // Das Banner fest im Modal verankern (statisches Overlay für das Hauptbild des Mods)
    let ribbonEl = document.getElementById('modal-ribbon');
    if (!ribbonEl) {
        const cContainer = document.querySelector('.carousel-main-image-container');
        cContainer.style.position = 'relative'; 
        cContainer.style.overflow = 'hidden';
        
        ribbonEl = document.createElement('div');
        ribbonEl.id = 'modal-ribbon';
        ribbonEl.className = 'badge-ribbon';
        ribbonEl.style.display = 'none';
        cContainer.appendChild(ribbonEl);
    }
    updateRibbon(ribbonEl, mod.status);

    modalState.images = mod.images;
    modalState.currentIndex = 0;
    renderModalCarousel();

    document.getElementById('mod-modal').classList.add('active');
}

function closeModal() { document.getElementById('mod-modal').classList.remove('active'); }

function renderModalCarousel() {
    const mainImg = document.getElementById('modal-main-image');
    mainImg.src = modalState.images[modalState.currentIndex].image_url;

    const thumbsContainer = document.getElementById('modal-thumbnails');
    const total = modalState.images.length;
    const half = Math.floor(total / 2);

    if (thumbsContainer.children.length !== total) {
        thumbsContainer.innerHTML = '';
        modalState.images.forEach((imgObj, index) => {
            const thumb = document.createElement('div');
            let diff = index - modalState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;
            
            thumb.className = `thumbnail-box ${index === modalState.currentIndex ? 'active' : ''}`;
            thumb.style.backgroundImage = `url(${imgObj.image_url})`;
            thumb.style.order = diff;
            
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
    
    // Klick ins Main Image öffnet Vollbild
    mainImg.onclick = () => {
        openFullscreenModal({ images: modalState.images });
        fullscreenState.currentIndex = modalState.currentIndex;
        renderFullscreenCarousel();
    };
}

document.querySelector('#mod-modal .prev-btn').addEventListener('click', () => {
    modalState.currentIndex = (modalState.currentIndex - 1 + modalState.images.length) % modalState.images.length;
    renderModalCarousel();
});
document.querySelector('#mod-modal .next-btn').addEventListener('click', () => {
    modalState.currentIndex = (modalState.currentIndex + 1) % modalState.images.length;
    renderModalCarousel();
});

// Vollbild Modal Frontend (Keine Ribbons hier, laut Anforderung)
let fullscreenState = { images: [], currentIndex: 0 };
function openFullscreenModal(mod) {
    fullscreenState.images = mod.images;
    fullscreenState.currentIndex = 0;
    renderFullscreenCarousel();
    document.getElementById('fullscreen-modal').classList.add('active');
}
function closeFullscreenModal() { document.getElementById('fullscreen-modal').classList.remove('active'); }

function renderFullscreenCarousel() {
    const mainImg = document.getElementById('fullscreen-main-image');
    mainImg.src = fullscreenState.images[fullscreenState.currentIndex].image_url;

    const fsThumbsContainer = document.getElementById('fullscreen-thumbnails');
    if (!fsThumbsContainer) return;

    const total = fullscreenState.images.length;
    const half = Math.floor(total / 2);

    if (fsThumbsContainer.children.length !== total) {
        fsThumbsContainer.innerHTML = '';
        fullscreenState.images.forEach((imgObj, index) => {
            const thumb = document.createElement('div');
            let diff = index - fullscreenState.currentIndex;
            if (diff > half) diff -= total;
            if (diff < -Math.floor((total - 1) / 2)) diff += total;
            
            thumb.className = `thumbnail-box ${index === fullscreenState.currentIndex ? 'active' : ''}`;
            thumb.style.backgroundImage = `url(${imgObj.image_url})`;
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
