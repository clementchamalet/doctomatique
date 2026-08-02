/**
 * Practitioner details and booking module
 * Handles the display of info, slots, and booking
 */

// ========== STATE ==========
const state = {
    practitioner: null,
    selectedStructure: null,
    slots: [],
    selectedSlot: null,
    map: null
};

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', () => {
    loadPractitionerDetails();
});

// ========== LOADING DETAILS ==========
async function loadPractitionerDetails() {
    try {
        const params = new URLSearchParams();
        params.append('practitioner_id', window.PRACTITIONER_ID);
        if (window.STRUCTURE_ID) {
            params.append('structure_id', window.STRUCTURE_ID);
        }

        const response = await fetch(`../api/get_practitioner_details.php?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            state.practitioner = data.data.practitioner;
            displayPractitioner();
            
            // Pre-select the facility if specified
            if (window.STRUCTURE_ID) {
                document.getElementById('selectStructure').value = window.STRUCTURE_ID;
                onStructureChange();
            }
        } else {
            showError(data.error || 'Praticien non trouvé');
            setTimeout(() => window.location.href = 'search.php', 2000);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur lors du chargement des informations');
    }
}

// ========== DISPLAY ==========
function displayPractitioner() {
    const p = state.practitioner;

    // Name
    document.getElementById('practitionerName').textContent = p.name;
    document.title = `Dr ${p.name} - Doctomatique`;

    // Specialties
    const specialtiesText = p.specialties.map(s => s.name).join(', ');
    document.getElementById('practitionerSpecialties').textContent = specialtiesText;

    // Biography
    document.getElementById('practitionerBio').textContent = p.biography || 'Aucune présentation disponible.';

    // Facilities
    displayStructures();

    // Reviews
    displayReviews();

    // Display the content
    document.getElementById('loadingSpinner').style.display = 'none';
    document.getElementById('practitionerContent').style.display = 'block';
}

function displayStructures() {
    const container = document.getElementById('structuresList');
    const select = document.getElementById('selectStructure');
    
    container.innerHTML = '';
    
    state.practitioner.structures.forEach(structure => {
        // Facility card
        const card = document.createElement('div');
        card.className = 'structure-card';
        card.innerHTML = `
            <h4>${structure.name}</h4>
            <p class="structure-type">${structure.type}</p>
            <p class="structure-address">
                ${structure.address}<br>
            </p>
            <p class="structure-phone">Telephone : ${structure.phone}</p>
            <p class="structure-availability">
                ${structure.available_slots > 0 
                    ? `Creneau : ${structure.available_slots} creneaux disponibles`
                    : 'Aucun creneau disponible'}
            </p>
        `;
        container.appendChild(card);

        // Option in the select
        const option = document.createElement('option');
        option.value = structure.id;
        option.textContent = `${structure.name}`;
        if (structure.available_slots === 0) {
            option.disabled = true;
            option.textContent += ' (Aucun créneau)';
        }
        select.appendChild(option);
    });

    // Event listener for the facility change
    select.addEventListener('change', onStructureChange);
}

function displayReviews() {
    const container = document.getElementById('reviewsList');
    const section = document.getElementById('reviewsSection');
    
    if (state.practitioner.reviews.count === 0) {
        section.style.display = 'none';
        return;
    }

    container.innerHTML = '';
    
    state.practitioner.reviews.list.forEach(review => {
        const reviewCard = document.createElement('div');
        reviewCard.className = 'review-card';
        
        const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
        const date = new Date(review.date).toLocaleDateString('fr-FR');
        
        reviewCard.innerHTML = `
            <div class="review-header">
                <span class="review-author">${review.patient_firstname}</span>
                <span class="review-rating">${stars}</span>
            </div>
            <p class="review-comment">${review.comment || 'Aucun commentaire'}</p>
            <span class="review-date">${date}</span>
        `;
        
        container.appendChild(reviewCard);
    });
}

// ========== SLOTS ==========
async function onStructureChange() {
    const structureId = document.getElementById('selectStructure').value;
    
    if (!structureId) {
        document.getElementById('slotsContainer').style.display = 'none';
        return;
    }

    state.selectedStructure = state.practitioner.structures.find(s => s.id == structureId);
    document.getElementById('slotsContainer').style.display = 'block';
    
    await loadAvailableSlots(structureId);
}

async function loadAvailableSlots(structureId) {
    document.getElementById('loadingSlots').style.display = 'flex';
    document.getElementById('slotsList').innerHTML = '';
    document.getElementById('noSlots').style.display = 'none';

    try {
        const params = new URLSearchParams({
            practitioner_id: window.PRACTITIONER_ID,
            structure_id: structureId
        });

        const response = await fetch(`../api/get_available_slots.php?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            state.slots = data.data.slots_by_date;
            displaySlots();
        } else {
            showError(data.error);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur lors du chargement des creneaux');
    } finally {
        document.getElementById('loadingSlots').style.display = 'none';
    }
}

function displaySlots() {
    const container = document.getElementById('slotsList');
    container.innerHTML = '';

    if (state.slots.length === 0) {
        document.getElementById('noSlots').style.display = 'block';
        return;
    }

    state.slots.forEach(day => {
        const dayCard = document.createElement('div');
        dayCard.className = 'day-card';

        const dayLabel = day.is_today ? 'Aujourd\'hui' : 
                         day.is_tomorrow ? 'Demain' : 
                         `${getDayName(day.date)} ${day.day_number} ${getMonthName(day.date)}`;

        dayCard.innerHTML = `
            <h5 class="day-header">${dayLabel}</h5>
            <div class="slots-grid"></div>
        `;

        const slotsGrid = dayCard.querySelector('.slots-grid');

        day.slots.forEach(slot => {
            const slotBtn = document.createElement('button');
            slotBtn.className = 'slot-button';
            slotBtn.textContent = slot.start_time;
            slotBtn.onclick = () => selectSlot(slot, day);
            slotsGrid.appendChild(slotBtn);
        });

        container.appendChild(dayCard);
    });
}

// ========== BOOKING ==========
function selectSlot(slot, day) {
    if (!window.IS_LOGGED_IN) {
        if (confirm('Vous devez être connecté pour réserver. Voulez-vous vous connecter ?')) {
            window.location.href = 'auth.php';
        }
        return;
    }

    state.selectedSlot = { ...slot, date: day.date, day_label: day.day_name_fr };
    showBookingModal();
}

function showBookingModal() {
    const modal = document.getElementById('bookingModal');
    const summary = document.getElementById('bookingSummary');
    
    const dayLabel = state.selectedSlot.date === new Date().toISOString().split('T')[0] ? 'Aujourd\'hui' :
                     'Le ' + new Date(state.selectedSlot.date).toLocaleDateString('fr-FR', { 
                         weekday: 'long', 
                         day: 'numeric', 
                         month: 'long' 
                     });

    summary.innerHTML = `
        <div class="summary-item">
            <strong>Praticien :</strong> Dr ${state.practitioner.name}
        </div>
        <div class="summary-item">
            <strong>Lieu :</strong> ${state.selectedStructure.name}
        </div>
        <div class="summary-item">
            <strong>Date :</strong> ${dayLabel}
        </div>
        <div class="summary-item">
            <strong>Heure :</strong> ${state.selectedSlot.start_time} - ${state.selectedSlot.end_time}
        </div>
    `;

    document.getElementById('selectedSlotId').value = state.selectedSlot.id;
    document.getElementById('bookingForm').style.display = 'block';
    document.getElementById('bookingSuccess').style.display = 'none';
    
    modal.style.display = 'flex';

    // Event listeners
    document.querySelector('.modal-close').onclick = closeBookingModal;
    document.getElementById('btnCancelBooking').onclick = closeBookingModal;
    document.getElementById('bookingForm').onsubmit = handleBookingSubmit;
    
    // Close by clicking outside
    modal.onclick = (e) => {
        if (e.target === modal) closeBookingModal();
    };
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
    document.getElementById('bookingForm').reset();
}

async function handleBookingSubmit(e) {
    e.preventDefault();

    const slotId = document.getElementById('selectedSlotId').value;
    const reasonId = document.getElementById('bookingReason').value;
    const notes = document.getElementById('bookingNotes').value;

    const submitBtn = document.getElementById('btnConfirmBooking');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Reservation en cours...';

    try {
        const response = await fetch('../api/book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                slot_id: parseInt(slotId),
                reason_id: parseInt(reasonId),
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            showBookingSuccess();
        } else {
            alert(data.error || 'Erreur lors de la réservation');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmer le rendez-vous';
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la réservation');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirmer le rendez-vous';
    }
}

function showBookingSuccess() {
    document.getElementById('bookingForm').style.display = 'none';
    document.getElementById('bookingSuccess').style.display = 'block';
    
    document.getElementById('btnCloseSuccess').onclick = () => {
        window.location.href = 'appointments.php';
    };
}

// ========== UTILITIES ==========
function getDayName(dateStr) {
    const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const date = new Date(dateStr);
    return days[date.getDay()];
}

function getMonthName(dateStr) {
    const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    const date = new Date(dateStr);
    return months[date.getMonth()];
}

function showError(message) {
    alert(message);
}