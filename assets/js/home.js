let autocompleteTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();
});

function setupEventListeners() {
    const specialtyInput = document.getElementById('champPraticien');
    const cityInput = document.getElementById('champLocalisation');
    const btnSearch = document.querySelector('.btn-search');

    if (specialtyInput) {
        specialtyInput.addEventListener('input', (e) => {
            clearTimeout(autocompleteTimeout);
            showAutocompleteSpecialties(e.target.value);
            autocompleteTimeout = setTimeout(() => {
                // Pas d'envoi auto
            }, 300);
        });

        specialtyInput.addEventListener('focus', () => {
            showAutocompleteSpecialties(specialtyInput.value);
        });
    }

    if (cityInput) {
        cityInput.addEventListener('input', (e) => {
            clearTimeout(autocompleteTimeout);
            showAutocompleteCities(e.target.value);
            autocompleteTimeout = setTimeout(() => {
                // Pas d'envoi auto
            }, 300);
        });

        cityInput.addEventListener('focus', () => {
            showAutocompleteCities(cityInput.value);
        });
    }

    if (btnSearch) {
        btnSearch.addEventListener('click', (e) => {
            e.preventDefault();
            redirectToSearch();
        });

        specialtyInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') redirectToSearch();
        });
        cityInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') redirectToSearch();
        });
    }

    // Fermer autocomplete si click ailleurs
    document.addEventListener('click', (e) => {
        const wrappers = document.querySelectorAll('.input-wrapper');
        let isInWrapper = false;
        wrappers.forEach(wrapper => {
            if (wrapper.contains(e.target)) {
                isInWrapper = true;
            }
        });
        if (!isInWrapper) {
            closeAllAutocompletes();
        }
    });
}

async function showAutocompleteSpecialties(query) {
    const inputElement = document.getElementById('champPraticien');
    if (!inputElement) return;

    const wrapper = inputElement.closest('.input-wrapper');
    let list = wrapper?.querySelector('.autocomplete-list');
    
    if (!list) {
        list = document.createElement('div');
        list.className = 'autocomplete-list';
        wrapper?.appendChild(list);
    }

    if (!query || query.length < 1) {
        const response = await fetch('api/autocomplete.php?type=specialties');
        const data = await response.json();
        if (data.success) {
            displaySpecialtyAutocomplete(data.data.results, query, list);
        }
        return;
    }

    try {
        const response = await fetch(`api/autocomplete.php?type=specialties&query=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Erreur réseau');
        const data = await response.json();
        if (data.success) {
            displaySpecialtyAutocomplete(data.data.results, query, list);
        }
    } catch (error) {
        console.error('Erreur autocomplete:', error);
    }
}

async function showAutocompleteCities(query) {
    const inputElement = document.getElementById('champLocalisation');
    if (!inputElement) return;

    const wrapper = inputElement.closest('.input-wrapper');
    let list = wrapper?.querySelector('.autocomplete-list');
    
    if (!list) {
        list = document.createElement('div');
        list.className = 'autocomplete-list';
        wrapper?.appendChild(list);
    }

    if (!query || query.length < 1) {
        const response = await fetch('api/autocomplete.php?type=cities');
        const data = await response.json();
        if (data.success) {
            displayCityAutocomplete(data.data.results, query, list);
        }
        return;
    }

    try {
        const response = await fetch(`api/autocomplete.php?type=cities&query=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Erreur réseau');
        const data = await response.json();
        if (data.success) {
            displayCityAutocomplete(data.data.results, query, list);
        }
    } catch (error) {
        console.error('Erreur autocomplete:', error);
    }
}

function displaySpecialtyAutocomplete(specialties, query, list) {
    list.innerHTML = '';
    if (specialties.length === 0) {
        list.style.display = 'none';
        return;
    }

    specialties.forEach(spec => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = spec.nom || spec.name;
        item.addEventListener('click', () => {
            document.getElementById('champPraticien').value = spec.nom || spec.name;
            list.style.display = 'none';
        });
        list.appendChild(item);
    });

    list.style.display = 'block';
}

function displayCityAutocomplete(cities, query, list) {
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
            document.getElementById('champLocalisation').value = city;
            list.style.display = 'none';
        });
        list.appendChild(item);
    });

    list.style.display = 'block';
}

function closeAllAutocompletes() {
    const lists = document.querySelectorAll('.autocomplete-list');
    lists.forEach(list => {
        list.style.display = 'none';
    });
}

function redirectToSearch() {
    const specialtyInput = document.getElementById('champPraticien');
    const cityInput = document.getElementById('champLocalisation');

    const specialty = specialtyInput?.value || '';
    const city = cityInput?.value || '';

    const params = new URLSearchParams();
    if (specialty) params.append('specialty', specialty);
    if (city) params.append('city', city);

    const url = `pages/search.php${params.toString() ? '?' + params.toString() : ''}`;
    window.location.href = url;
}