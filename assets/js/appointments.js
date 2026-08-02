/**
 * Patient appointment management module
 * Displaying, filtering, and cancelling appointments
 */

// ========== STATE ==========
const state = {
    appointments: [],
    filteredAppointments: [],
    currentFilter: 'all',
    appointmentToCancel: null
};

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', () => {
    loadAppointments();
    attachEventListeners();
});

// ========== LOADING APPOINTMENTS ==========
async function loadAppointments(filter = 'all') {
    showLoading();

    try {
        const url = filter !== 'all' 
            ? `../api/get_user_appointments.php?status=${filter}`
            : '../api/get_user_appointments.php';

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            state.appointments = data.data.appointments;
            updateCounts(data.data.stats);
            applyFilter(state.currentFilter);
        } else {
            showError(data.error || 'Erreur lors du chargement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Une erreur est survenue');
    } finally {
        hideLoading();
    }
}

// ========== DISPLAY ==========
function applyFilter(filter) {
    state.currentFilter = filter;

    // Update the active buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.filter === filter) {
            btn.classList.add('active');
        }
    });

    // Filter the appointments
    switch (filter) {
        case 'upcoming':
            state.filteredAppointments = state.appointments.filter(a => 
                !a.is_past && a.status !== 'AnnulÃ©'
            );
            break;
        case 'past':
            state.filteredAppointments = state.appointments.filter(a => 
                a.is_past || a.status === 'TerminÃ©'
            );
            break;
        case 'cancelled':
            state.filteredAppointments = state.appointments.filter(a => 
                a.status === 'AnnulÃ©'
            );
            break;
        default:
            state.filteredAppointments = state.appointments;
    }

    displayAppointments();
}

function displayAppointments() {
    const container = document.getElementById('appointmentsList');
    const noAppointments = document.getElementById('noAppointments');

    container.innerHTML = '';

    if (state.filteredAppointments.length === 0) {
        noAppointments.style.display = 'flex';
        return;
    }

    noAppointments.style.display = 'none';

    state.filteredAppointments.forEach(appointment => {
        const card = createAppointmentCard(appointment);
        container.appendChild(card);
    });
}

function createAppointmentCard(apt) {
    const card = document.createElement('div');
    card.className = `appointment-card ${apt.is_past ? 'past' : ''} ${apt.status === 'Annulé' ? 'cancelled' : ''}`;

    // Status badge
    const statusBadge = getStatusBadge(apt);

    // Formatted date
    const dateLabel = apt.is_today ? 'Aujourd\'hui' :
                     apt.is_tomorrow ? 'Demain' :
                     new Date(apt.datetime).toLocaleDateString('fr-FR', {
                         weekday: 'long',
                         day: 'numeric',
                         month: 'long',
                         year: 'numeric'
                     });

    card.innerHTML = `
        <div class="appointment-header">
            <div class="appointment-date">
                <div class="date-day">${new Date(apt.datetime).getDate()}</div>
                <div class="date-month">${getMonthShort(apt.datetime)}</div>
            </div>
            <div class="appointment-info">
                <div class="practitioner-info">
                    <img src="${photoUrl}" alt="Photo" class="practitioner-photo-small">
                    <div>
                        <h3>Dr ${apt.practitioner.name}</h3>
                        <p class="specialties">${apt.practitioner.specialties}</p>
                    </div>
                </div>
                ${statusBadge}
            </div>
        </div>

        <div class="appointment-details">
            <div class="detail-row">
                <span class="icon">ðŸ“…</span>
                <span><strong>${dateLabel}</strong> Ã  ${apt.start_time}</span>
            </div>
            <div class="detail-row">
                <span class="icon">ðŸ“</span>
                <span>
                    ${apt.structure.name}<br>
                    <small>${apt.structure.address}, ${apt.structure.city}</small>
                </span>
            </div>
            <div class="detail-row">
                <span class="icon">ðŸ“‹</span>
                <span>Motif : ${apt.reason}</span>
            </div>
            ${apt.notes ? `
            <div class="detail-row">
                <span class="icon">ðŸ’¬</span>
                <span><em>${apt.notes}</em></span>
            </div>
            ` : ''}
        </div>

        <div class="appointment-actions">
            ${apt.can_cancel ? `
                <button class="btn-cancel" onclick="showCancelModal(${apt.id})">
                    Annuler le rendez-vous
                </button>
            ` : ''}
            <a href="practitioner.php?id=${apt.practitioner.id || apt.practitioner_id}&structure=${apt.structure.id}" 
               class="btn-view-practitioner">
                Voir le praticien
            </a>
        </div>
    `;

    return card;
}

function getStatusBadge(apt) {
    if (apt.status === 'Annulé') {
        return '<span class="status-badge cancelled">Annulé</span>';
    } else if (apt.is_past) {
        return '<span class="status-badge past">Passé</span>';
    } else if (apt.is_today) {
        return '<span class="status-badge today">Aujourd\'hui</span>';
    } else if (apt.is_tomorrow) {
        return '<span class="status-badge upcoming">Demain</span>';
    } else {
        return '<span class="status-badge upcoming">À venir</span>';
    }
}

function updateCounts(stats) {
    document.getElementById('countAll').textContent = stats.total;
    document.getElementById('countUpcoming').textContent = stats.upcoming;
    document.getElementById('countPast').textContent = stats.past;
    document.getElementById('countCancelled').textContent = stats.cancelled;
}

// ========== CANCELLATION ==========
window.showCancelModal = function(appointmentId) {
    const appointment = state.appointments.find(a => a.id === appointmentId);
    if (!appointment) return;

    state.appointmentToCancel = appointment;

    const summary = document.getElementById('cancelSummary');
    summary.innerHTML = `
        <div class="summary-item">
            <strong>Praticien :</strong> Dr ${appointment.practitioner.name}
        </div>
        <div class="summary-item">
            <strong>Date :</strong> ${new Date(appointment.datetime).toLocaleDateString('fr-FR', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            })}
        </div>
        <div class="summary-item">
            <strong>Heure :</strong> ${appointment.start_time}
        </div>
        <div class="summary-item">
            <strong>Lieu :</strong> ${appointment.structure.name}, ${appointment.structure.city}
        </div>
    `;

    document.getElementById('cancelModal').style.display = 'flex';
};

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
    state.appointmentToCancel = null;
}

async function confirmCancel() {
    if (!state.appointmentToCancel) return;

    const confirmBtn = document.getElementById('btnConfirmCancel');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Annulation...';

    try {
        const response = await fetch('../api/cancel_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                appointment_id: state.appointmentToCancel.id
            })
        });

        const data = await response.json();

        if (data.success) {
            closeCancelModal();
            showCancelSuccessModal();
            // Reload the appointments after a short delay
            setTimeout(() => loadAppointments(), 1500);
        } else {
            alert(data.error || 'Erreur lors de l\'annulation');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Oui, annuler';
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Oui, annuler';
    }
}

function showCancelSuccessModal() {
    document.getElementById('cancelSuccessModal').style.display = 'flex';
}

function closeCancelSuccessModal() {
    document.getElementById('cancelSuccessModal').style.display = 'none';
}

// ========== EVENTS ==========
function attachEventListeners() {
    // Filters
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            applyFilter(btn.dataset.filter);
        });
    });

    // Cancellation modal
    document.getElementById('btnCloseCancelModal').addEventListener('click', closeCancelModal);
    document.getElementById('btnConfirmCancel').addEventListener('click', confirmCancel);
    document.getElementById('btnCloseCancelSuccess').addEventListener('click', closeCancelSuccessModal);

    // Close by clicking outside
    document.getElementById('cancelModal').addEventListener('click', (e) => {
        if (e.target.id === 'cancelModal') closeCancelModal();
    });

    document.getElementById('cancelSuccessModal').addEventListener('click', (e) => {
        if (e.target.id === 'cancelSuccessModal') closeCancelSuccessModal();
    });
}

// ========== UI HELPERS ==========
function showLoading() {
    document.getElementById('loadingSpinner').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingSpinner').style.display = 'none';
}

function showError(message) {
    alert(message);
}

function getMonthShort(dateStr) {
    const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                    'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    const date = new Date(dateStr);
    return months[date.getMonth()];
}