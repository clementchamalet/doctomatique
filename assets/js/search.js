const state = {
    practitioners: [],
    userLocation: null,
    map: null,
    markers: [],
    filters: {
        specialty: null,
        city: '',
        specialtyText: ''
    }
};

window.practitionerIcon = null;
window.userIcon = null;

let autocompleteTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    setupEventListeners();
    loadInitialFiltersFromURL();
    performSearch();
});

function initMap() {
    state.map = L.map('map').setView([48.8566, 2.3522], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(state.map);

    window.practitionerIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    window.userIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
}

function loadInitialFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const specialty = urlParams.get('specialty');
    const city = urlParams.get('city');

    if (specialty) {
        document.getElementById('filterSpecialty').value = specialty;
        state.filters.specialtyText = specialty;
    }

    if (city) {
        document.getElementById('filterCity').value = city;
        state.filters.city = city;
    }
}

function setupEventListeners() {
    const specialtyInput = document.getElementById('filterSpecialty');
    const cityInput = document.getElementById('filterCity');
    const btnSearch = document.getElementById('btnSearch');

    if (specialtyInput) {
        specialtyInput.addEventListener('input', (e) => {
            clearTimeout(autocompleteTimeout);
            showAutocompleteSpecialties(e.target.value);
            autocompleteTimeout = setTimeout(() => {
                state.filters.specialtyText = e.target.value;
                performSearch();
            }, 300);
        });
    }

    if (cityInput) {
        cityInput.addEventListener('input', (e) => {
            clearTimeout(autocompleteTimeout);
            showAutocompleteCities(e.target.value);
            autocompleteTimeout = setTimeout(() => {
                state.filters.city = e.target.value;
                performSearch();
            }, 300);
        });
    }

    if (btnSearch) {
        btnSearch.addEventListener('click', performSearch);
    }

    // Fermer autocomplete si click ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.autocomplete-wrapper')) {
            closeAllAutocompletes();
        }
    });
}

async function showAutocompleteSpecialties(query) {
    const list = document.getElementById('autocompleteSpecialties');
    if (!list) return;

    if (!query) {
        // Afficher toutes les spécialités
        const response = await fetch('../api/autocomplete.php?type=specialties');
        const data = await response.json();
        if (data.success) {
            displaySpecialtyAutocomplete(data.data.results, query);
        }
        return;
    }

    const response = await fetch(`../api/autocomplete.php?type=specialties&query=${encodeURIComponent(query)}`);
    const data = await response.json();
    if (data.success) {
        displaySpecialtyAutocomplete(data.data.results, query);
    }
}

async function showAutocompleteCities(query) {
    const list = document.getElementById('autocompleteCities');
    if (!list) return;

    if (!query) {
        // Afficher toutes les villes
        const response = await fetch('../api/autocomplete.php?type=cities');
        const data = await response.json();
        if (data.success) {
            displayCityAutocomplete(data.data.results, query);
        }
        return;
    }

    const response = await fetch(`../api/autocomplete.php?type=cities&query=${encodeURIComponent(query)}`);
    const data = await response.json();
    if (data.success) {
        displayCityAutocomplete(data.data.results, query);
    }
}

function displaySpecialtyAutocomplete(specialties, query) {
    const list = document.getElementById('autocompleteSpecialties');
    if (!list) return;

    list.innerHTML = '';
    if (specialties.length === 0) {
        list.style.display = 'none';
        return;
    }

    specialties.forEach(spec => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = spec.nom;
        item.addEventListener('click', () => {
            document.getElementById('filterSpecialty').value = spec.nom;
            state.filters.specialty = spec.id;
            state.filters.specialtyText = spec.nom;
            list.style.display = 'none';
            performSearch();
        });
        list.appendChild(item);
    });

    list.style.display = 'block';
}

function displayCityAutocomplete(cities, query) {
    const list = document.getElementById('autocompleteCities');
    if (!list) return;

    list.innerHTML = '';
    if (cities.length === 0) {
        list.style.display = 'none';
        return;
    }

    cities.forEach(city => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = city;
        item.addEventListener('click', () => {
            document.getElementById('filterCity').value = city;
            state.filters.city = city;
            list.style.display = 'none';
            performSearch();
        });
        list.appendChild(item);
    });

    list.style.display = 'block';
}

function closeAllAutocompletes() {
    const specialties = document.getElementById('autocompleteSpecialties');
    const cities = document.getElementById('autocompleteCities');
    if (specialties) specialties.style.display = 'none';
    if (cities) cities.style.display = 'none';
}

async function performSearch() {
    const params = new URLSearchParams();
    
    if (state.filters.specialtyText) {
        params.append('search', state.filters.specialtyText);
    }

    showLoading();

    try {
        const response = await fetch(`../api/search_practitioners.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error(`Erreur serveur: ${response.status}`);
        }
        
        const data = await response.json();

        if (data.success) {
            state.practitioners = data.data.practitioners;
            
            // Filtrer par spécialité (si le texte contient la spécialité)
            if (state.filters.specialtyText) {
                state.practitioners = state.practitioners.filter(p => {
                    if (!p.specialties || p.specialties.length === 0) return false;
                    const specialtyLower = state.filters.specialtyText.toLowerCase();
                    return p.specialties.some(s => {
                        const specName = typeof s === 'string' ? s : s.name;
                        return specName.toLowerCase().includes(specialtyLower);
                    });
                });
            }
            
            // Filtrer par ville
            if (state.filters.city) {
                state.practitioners = state.practitioners.filter(p => {
                    if (!p.structures || p.structures.length === 0) return false;
                    const cityLower = state.filters.city.toLowerCase();
                    return p.structures.some(s => s.city && s.city.toLowerCase().includes(cityLower));
                });
            }
            
            displayResults();
            updateMap();
            updateResultsCount();
        } else {
            showError(data.error || 'Erreur lors de la recherche');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError(error.message || 'Une erreur est survenue');
    } finally {
        hideLoading();
    }
}

function updateResultsCount() {
    const countSpan = document.getElementById('resultsCount');
    if (countSpan) {
        countSpan.textContent = `${state.practitioners.length} résultat${state.practitioners.length > 1 ? 's' : ''}`;
    }
}

function displayResults() {
    const container = document.getElementById('practitionersList');
    if (!container) return;

    container.innerHTML = '';

    if (state.practitioners.length === 0) {
        container.innerHTML = '<p class="no-results">Aucun praticien trouvé</p>';
        return;
    }

    state.practitioners.forEach(practitioner => {
        const card = document.createElement('div');
        card.className = 'practitioner-card';
        
        const structure = practitioner.structures && practitioner.structures[0];

        if (structure) {
            card.onclick = () => {
                window.location.href = `practitioner.php?id=${practitioner.id}&structure=${structure.id}`;
            };
            card.style.cursor = 'pointer';
        }

        const photoUrl = '../assets/images/default-doctor.png';

        const rating = practitioner.reviews && practitioner.reviews.average
            ? `<span class="rating">★ ${practitioner.reviews.average} (${practitioner.reviews.count} avis)</span>`
            : '';

        const specialtiesText = practitioner.specialties
            ? practitioner.specialties.map(s => typeof s === 'string' ? s : s.name).join(', ')
            : 'Non spécifiée';

        card.innerHTML = `
            <div class="card-header">
                <img src="${photoUrl}" alt="Photo" class="practitioner-photo">
                <div class="card-title">
                    <h3>${escapeHtml(practitioner.name)}</h3>
                    ${rating}
                </div>
            </div>
            <div class="card-body">
                <p><strong class="specialty">${escapeHtml(specialtiesText)}</strong></p>
                ${structure ? `
                    <div class="structure-info">
                        <p><strong>${escapeHtml(structure.name)}</strong></p>
                        <p class="address">${escapeHtml(structure.address)}, ${escapeHtml(structure.city)}</p>
                    </div>
                ` : '<p class="no-structure">Localisation non disponible</p>'}
            </div>
            <div class="card-footer">
                <button class="btn-primary" onclick="event.stopPropagation(); window.location.href='practitioner.php?id=${practitioner.id}${structure ? '&structure=' + structure.id : ''}'" >
                    Voir le profil
                </button>
            </div>
        `;

        container.appendChild(card);
    });
}

function updateMap() {
    state.markers.forEach(marker => marker.remove());
    state.markers = [];

    if (state.practitioners.length === 0) return;

    const bounds = [];

    state.practitioners.forEach(practitioner => {
        const structure = practitioner.structures && practitioner.structures[0];
        
        if (!structure || !structure.latitude || !structure.longitude) return;

        const { latitude, longitude } = structure;

        const marker = L.marker([latitude, longitude], { icon: window.practitionerIcon })
            .addTo(state.map);

        const specialtiesText = practitioner.specialties
            ? practitioner.specialties.map(s => typeof s === 'string' ? s : s.name).join(', ')
            : 'Spécialité non spécifiée';

        marker.bindPopup(`
            <div class="map-popup">
                <h4>${escapeHtml(practitioner.name)}</h4>
                <p><strong>${escapeHtml(specialtiesText)}</strong></p>
                <p>${escapeHtml(structure.name)}</p>
                <p class="small">${escapeHtml(structure.address)}</p>
                <a href="practitioner.php?id=${practitioner.id}&structure=${structure.id}">Voir le profil</a>
            </div>
        `);

        state.markers.push(marker);
        bounds.push([latitude, longitude]);
    });

    if (bounds.length > 0) {
        const group = new L.featureGroup(state.markers);
        state.map.fitBounds(group.getBounds().pad(0.1));
    }
}

function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'block';
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'none';
}

function showError(message) {
    console.error('Erreur:', message);
    alert(message);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}