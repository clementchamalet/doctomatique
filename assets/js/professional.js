/* ========================================
   PROFESSIONNEL.JS - ESPACE PROFESSIONNEL
   Gestion du tableau de bord praticien
   ======================================== */

import { logDev } from './utils.js';

// ========== DONNÉES MOCKÉES ========== 

// Informations du praticien connecté
const PRATICIEN_ACTUEL = {
    id: 1,
    nom: "Dr. Sophie Martin",
    specialite: "Médecin généraliste",
    email: "sophie.martin@doctomatique.fr",
    telephone: "03 20 12 34 56",
    adresse: "15 Rue de la République, 59000 Lille",
    photo: "https://i.pravatar.cc/150?img=1",
    tarifConsultation: 25,
    dureeConsultation: 30, // en minutes
    numeroOrdre: "59001234567"
};

// Rendez-vous mockés
const RENDEZ_VOUS_MOCK = [
    {
        id: 1,
        patientId: 101,
        patientNom: "Marie Dubois",
        patientPhoto: "https://i.pravatar.cc/150?img=5",
        patientTelephone: "06 12 34 56 78",
        patientEmail: "marie.dubois@email.fr",
        date: "2025-12-10",
        heureDebut: "09:00",
        heureFin: "09:30",
        type: "Consultation",
        motif: "Contrôle annuel",
        statut: "confirmed", // confirmed, pending, cancelled, completed
        notes: "Patiente suivie depuis 2020",
        premiere: false
    },
    {
        id: 2,
        patientId: 102,
        patientNom: "Jean Dupont",
        patientPhoto: "https://i.pravatar.cc/150?img=12",
        patientTelephone: "06 23 45 67 89",
        patientEmail: "jean.dupont@email.fr",
        date: "2025-12-10",
        heureDebut: "09:30",
        heureFin: "10:00",
        type: "Consultation",
        motif: "Douleurs abdominales",
        statut: "confirmed",
        notes: "",
        premiere: false
    },
    {
        id: 3,
        patientId: 103,
        patientNom: "Claire Martin",
        patientPhoto: "https://i.pravatar.cc/150?img=9",
        patientTelephone: "06 34 56 78 90",
        patientEmail: "claire.martin@email.fr",
        date: "2025-12-10",
        heureDebut: "10:00",
        heureFin: "10:30",
        type: "Téléconsultation",
        motif: "Renouvellement ordonnance",
        statut: "pending",
        notes: "",
        premiere: false
    },
    {
        id: 4,
        patientId: 104,
        patientNom: "Thomas Bernard",
        patientPhoto: "https://i.pravatar.cc/150?img=13",
        patientTelephone: "06 45 67 89 01",
        patientEmail: "thomas.bernard@email.fr",
        date: "2025-12-10",
        heureDebut: "11:00",
        heureFin: "11:30",
        type: "Consultation",
        motif: "Première visite",
        statut: "confirmed",
        notes: "Nouveau patient",
        premiere: true
    },
    {
        id: 5,
        patientId: 105,
        patientNom: "Emma Petit",
        patientPhoto: "https://i.pravatar.cc/150?img=10",
        patientTelephone: "06 56 78 90 12",
        patientEmail: "emma.petit@email.fr",
        date: "2025-12-10",
        heureDebut: "14:00",
        heureFin: "14:30",
        type: "Consultation",
        motif: "Certificat médical",
        statut: "confirmed",
        notes: "",
        premiere: false
    },
    {
        id: 6,
        patientId: 106,
        patientNom: "Lucas Simon",
        patientPhoto: "https://i.pravatar.cc/150?img=14",
        patientTelephone: "06 67 89 01 23",
        patientEmail: "lucas.simon@email.fr",
        date: "2025-12-10",
        heureDebut: "14:30",
        heureFin: "15:00",
        type: "Consultation",
        motif: "Suivi grippe",
        statut: "confirmed",
        notes: "Patient vu la semaine dernière",
        premiere: false
    },
    {
        id: 7,
        patientId: 107,
        patientNom: "Léa Rousseau",
        patientPhoto: "https://i.pravatar.cc/150?img=20",
        patientTelephone: "06 78 90 12 34",
        patientEmail: "lea.rousseau@email.fr",
        date: "2025-12-11",
        heureDebut: "09:00",
        heureFin: "09:30",
        type: "Consultation",
        motif: "Vaccin grippe",
        statut: "confirmed",
        notes: "",
        premiere: false
    },
    {
        id: 8,
        patientId: 108,
        patientNom: "Alexandre Garnier",
        patientPhoto: "https://i.pravatar.cc/150?img=33",
        patientTelephone: "06 89 01 23 45",
        patientEmail: "alexandre.garnier@email.fr",
        date: "2025-12-11",
        heureDebut: "10:00",
        heureFin: "10:30",
        type: "Consultation",
        motif: "Douleurs dos",
        statut: "pending",
        notes: "",
        premiere: true
    },
    {
        id: 9,
        patientId: 109,
        patientNom: "Sophie Moreau",
        patientPhoto: "https://i.pravatar.cc/150?img=25",
        patientTelephone: "06 90 12 34 56",
        patientEmail: "sophie.moreau@email.fr",
        date: "2025-12-12",
        heureDebut: "14:00",
        heureFin: "14:30",
        type: "Consultation",
        motif: "Résultats analyses",
        statut: "confirmed",
        notes: "",
        premiere: false
    },
    {
        id: 10,
        patientId: 110,
        patientNom: "Pierre Lefebvre",
        patientPhoto: "https://i.pravatar.cc/150?img=15",
        patientTelephone: "06 01 23 45 67",
        patientEmail: "pierre.lefebvre@email.fr",
        date: "2025-12-13",
        heureDebut: "09:30",
        heureFin: "10:00",
        type: "Téléconsultation",
        motif: "Suivi traitement",
        statut: "confirmed",
        notes: "Traitement depuis 3 mois",
        premiere: false
    }
];

// Patients mockés
const PATIENTS_MOCK = [
    {
        id: 101,
        nom: "Marie Dubois",
        photo: "https://i.pravatar.cc/150?img=5",
        telephone: "06 12 34 56 78",
        email: "marie.dubois@email.fr",
        dateNaissance: "1985-03-15",
        adresse: "23 Rue Victor Hugo, 59000 Lille",
        numeroSecu: "2 85 03 59 123 456 78",
        derniereVisite: "2025-11-15",
        nombreConsultations: 12,
        allergies: ["Pénicilline"],
        traitements: ["Doliprane 1000mg"],
        antecedents: ["Hypertension"]
    },
    {
        id: 102,
        nom: "Jean Dupont",
        photo: "https://i.pravatar.cc/150?img=12",
        telephone: "06 23 45 67 89",
        email: "jean.dupont@email.fr",
        dateNaissance: "1978-07-22",
        adresse: "45 Avenue de la République, 59000 Lille",
        numeroSecu: "1 78 07 59 234 567 89",
        derniereVisite: "2025-10-20",
        nombreConsultations: 8,
        allergies: [],
        traitements: ["Aspirine 100mg"],
        antecedents: ["Diabète type 2"]
    },
    {
        id: 103,
        nom: "Claire Martin",
        photo: "https://i.pravatar.cc/150?img=9",
        telephone: "06 34 56 78 90",
        email: "claire.martin@email.fr",
        dateNaissance: "1992-11-08",
        adresse: "12 Rue Jean Jaurès, 59000 Lille",
        numeroSecu: "2 92 11 59 345 678 90",
        derniereVisite: "2025-09-10",
        nombreConsultations: 5,
        allergies: ["Latex"],
        traitements: [],
        antecedents: []
    },
    {
        id: 104,
        nom: "Thomas Bernard",
        photo: "https://i.pravatar.cc/150?img=13",
        telephone: "06 45 67 89 01",
        email: "thomas.bernard@email.fr",
        dateNaissance: "2000-05-30",
        adresse: "78 Boulevard Gambetta, 59100 Roubaix",
        numeroSecu: "1 00 05 59 456 789 01",
        derniereVisite: null,
        nombreConsultations: 0,
        allergies: [],
        traitements: [],
        antecedents: []
    },
    {
        id: 105,
        nom: "Emma Petit",
        photo: "https://i.pravatar.cc/150?img=10",
        telephone: "06 56 78 90 12",
        email: "emma.petit@email.fr",
        dateNaissance: "1988-09-12",
        adresse: "34 Rue Nationale, 59000 Lille",
        numeroSecu: "2 88 09 59 567 890 12",
        derniereVisite: "2025-11-28",
        nombreConsultations: 15,
        allergies: [],
        traitements: ["Levothyrox 75"],
        antecedents: ["Thyroïde"]
    }
];

// Disponibilités du praticien
const DISPONIBILITES_MOCK = {
    lundi: { actif: true, horaires: [{ debut: "09:00", fin: "12:00" }, { debut: "14:00", fin: "18:00" }] },
    mardi: { actif: true, horaires: [{ debut: "09:00", fin: "12:00" }, { debut: "14:00", fin: "18:00" }] },
    mercredi: { actif: true, horaires: [{ debut: "09:00", fin: "12:00" }] },
    jeudi: { actif: true, horaires: [{ debut: "09:00", fin: "12:00" }, { debut: "14:00", fin: "18:00" }] },
    vendredi: { actif: true, horaires: [{ debut: "09:00", fin: "12:00" }, { debut: "14:00", fin: "17:00" }] },
    samedi: { actif: false, horaires: [] },
    dimanche: { actif: false, horaires: [] }
};

// Congés et exceptions
const CONGES_MOCK = [
    { debut: "2025-12-24", fin: "2025-12-26", motif: "Vacances Noël" },
    { debut: "2025-12-31", fin: "2026-01-02", motif: "Vacances Nouvel An" }
];

// ========== VARIABLES GLOBALES ==========
let currentView = 'dashboard';
let currentDate = new Date();
let calendarView = 'week'; // week, day, month
let rendezVousFiltres = [...RENDEZ_VOUS_MOCK];
let filtreStatut = 'tous';

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    logDev('Page espace professionnel chargée', 'success');
    
    // Affichage des informations du praticien
    afficherInfosPraticien();
    
    // Chargement du dashboard par défaut
    afficherSection('dashboard');
    
    // Event listeners sur la navigation
    document.querySelectorAll('.pro-nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            afficherSection(section);
        });
    });
    
    // Event listeners calendrier
    const prevWeekBtn = document.getElementById('prevWeek');
    const nextWeekBtn = document.getElementById('nextWeek');
    const todayBtn = document.getElementById('todayBtn');
    
    if (prevWeekBtn) prevWeekBtn.addEventListener('click', () => changerSemaine(-1));
    if (nextWeekBtn) nextWeekBtn.addEventListener('click', () => changerSemaine(1));
    if (todayBtn) todayBtn.addEventListener('click', allerAujourdhui);
    
    // Event listeners vues calendrier
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            calendarView = this.dataset.view;
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            afficherCalendrier();
        });
    });
    
    // Event listeners filtres rendez-vous
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            filtreStatut = this.dataset.status;
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            filtrerRendezVous();
        });
    });
    
    logDev('Espace professionnel initialisé', 'success');
});

// ========== AFFICHAGE DES SECTIONS ==========
function afficherSection(section) {
    currentView = section;
    
    // Mise à jour de la navigation
    document.querySelectorAll('.pro-nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.section === section) {
            item.classList.add('active');
        }
    });
    
    // Masquage de toutes les sections
    document.querySelectorAll('.pro-section').forEach(sec => {
        sec.classList.remove('active');
    });
    
    // Affichage de la section demandée
    const sectionElement = document.getElementById(`section-${section}`);
    if (sectionElement) {
        sectionElement.classList.add('active');
    }
    
    // Chargement du contenu selon la section
    switch(section) {
        case 'dashboard':
            chargerDashboard();
            break;
        case 'agenda':
            afficherCalendrier();
            break;
        case 'rendez-vous':
            afficherListeRendezVous();
            break;
        case 'patients':
            afficherListePatients();
            break;
        case 'disponibilites':
            afficherDisponibilites();
            break;
        case 'parametres':
            afficherParametres();
            break;
    }
    
    logDev(`Section "${section}" affichée`, 'info');
}

// ========== INFORMATIONS DU PRATICIEN ==========
function afficherInfosPraticien() {
    document.getElementById('proNom').textContent = PRATICIEN_ACTUEL.nom;
    document.getElementById('proSpecialite').textContent = PRATICIEN_ACTUEL.specialite;
}

// ========== DASHBOARD ==========
function chargerDashboard() {
    // Calcul des statistiques
    const aujourdhui = new Date().toISOString().split('T')[0];
    const rdvAujourdhui = RENDEZ_VOUS_MOCK.filter(rdv => rdv.date === aujourdhui);
    const rdvConfirmes = RENDEZ_VOUS_MOCK.filter(rdv => rdv.statut === 'confirmed').length;
    const rdvEnAttente = RENDEZ_VOUS_MOCK.filter(rdv => rdv.statut === 'pending').length;
    const nbPatientsTotal = PATIENTS_MOCK.length;
    
    // Calcul du taux de remplissage (exemple simplifié)
    const creneauxDispos = 20; // Nombre de créneaux disponibles par jour
    const tauxRemplissage = Math.round((rdvAujourdhui.length / creneauxDispos) * 100);
    
    // Calcul des revenus du mois (simulation)
    const revenusEstimes = rdvConfirmes * PRATICIEN_ACTUEL.tarifConsultation;
    
    // Mise à jour de l'interface
    document.getElementById('statRdvJour').textContent = rdvAujourdhui.length;
    document.getElementById('statRdvConfirmes').textContent = rdvConfirmes;
    document.getElementById('statRdvEnAttente').textContent = rdvEnAttente;
    document.getElementById('statPatients').textContent = nbPatientsTotal;
    document.getElementById('statTauxRemplissage').textContent = tauxRemplissage + '%';
    document.getElementById('statRevenus').textContent = revenusEstimes + ' €';
    
    // Affichage des prochains rendez-vous
    const prochains = RENDEZ_VOUS_MOCK
        .filter(rdv => new Date(rdv.date) >= new Date())
        .sort((a, b) => {
            const dateA = new Date(`${a.date}T${a.heureDebut}`);
            const dateB = new Date(`${b.date}T${b.heureDebut}`);
            return dateA - dateB;
        })
        .slice(0, 5);
    
    const container = document.getElementById('prochainsRdv');
    if (container) {
        container.innerHTML = prochains.map(rdv => `
            <div class="appointment-item">
                <img src="${rdv.patientPhoto}" alt="${rdv.patientNom}" class="patient-avatar">
                <div>
                    <strong>${rdv.patientNom}</strong><br>
                    <small>${formatDate(rdv.date)} à ${rdv.heureDebut}</small>
                </div>
            </div>
        `).join('');
    }
    
    logDev('Dashboard chargé', 'success');
}

// ========== CALENDRIER ==========
function afficherCalendrier() {
    const container = document.getElementById('calendarGrid');
    if (!container) return;
    
    // Mise à jour du titre
    const title = document.getElementById('calendarTitle');
    if (title) {
        title.textContent = formatMoisAnnee(currentDate);
    }
    
    // Affichage selon la vue
    if (calendarView === 'week') {
        afficherVueSemaine(container);
    }
    
    logDev('Calendrier affiché', 'success');
}

function afficherVueSemaine(container) {
    const debutSemaine = getDebutSemaine(currentDate);
    const jours = [];
    
    // Génération des 7 jours de la semaine
    for (let i = 0; i < 7; i++) {
        const jour = new Date(debutSemaine);
        jour.setDate(debutSemaine.getDate() + i);
        jours.push(jour);
    }
    
    // Horaires de consultation (8h - 19h)
    const horaires = [];
    for (let h = 8; h <= 18; h++) {
        horaires.push(`${h.toString().padStart(2, '0')}:00`);
    }
    
    // Construction de la grille
    let html = '<div class="week-view">';
    
    // En-tête avec les jours
    html += '<div class="week-header time-col"></div>';
    jours.forEach(jour => {
        const isToday = jour.toISOString().split('T')[0] === new Date().toISOString().split('T')[0];
        html += `
            <div class="week-header">
                <div class="week-day-header">
                    <span class="week-day-name">${formatJourSemaine(jour)}</span>
                    <span class="week-day-number ${isToday ? 'today' : ''}">${jour.getDate()}</span>
                </div>
            </div>
        `;
    });
    
    // Lignes pour chaque horaire
    horaires.forEach(heure => {
        html += `<div class="time-slot"><div class="time-label">${heure}</div></div>`;
        
        jours.forEach(jour => {
            const dateStr = jour.toISOString().split('T')[0];
            const rdvCreneau = RENDEZ_VOUS_MOCK.filter(rdv => 
                rdv.date === dateStr && rdv.heureDebut === heure
            );
            
            html += '<div class="day-slot">';
            rdvCreneau.forEach(rdv => {
                html += `
                    <div class="appointment ${rdv.statut}" onclick="afficherDetailsRdv(${rdv.id})">
                        <div class="appointment-time">${rdv.heureDebut}</div>
                        <div class="appointment-patient">${rdv.patientNom}</div>
                    </div>
                `;
            });
            html += '</div>';
        });
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function changerSemaine(direction) {
    currentDate.setDate(currentDate.getDate() + (direction * 7));
    afficherCalendrier();
}

function allerAujourdhui() {
    currentDate = new Date();
    afficherCalendrier();
}

// ========== LISTE DES RENDEZ-VOUS ==========
function afficherListeRendezVous() {
    filtrerRendezVous();
}

function filtrerRendezVous() {
    if (filtreStatut === 'tous') {
        rendezVousFiltres = [...RENDEZ_VOUS_MOCK];
    } else {
        rendezVousFiltres = RENDEZ_VOUS_MOCK.filter(rdv => rdv.statut === filtreStatut);
    }
    
    // Tri par date et heure
    rendezVousFiltres.sort((a, b) => {
        const dateA = new Date(`${a.date}T${a.heureDebut}`);
        const dateB = new Date(`${b.date}T${b.heureDebut}`);
        return dateA - dateB;
    });
    
    const tbody = document.querySelector('#appointmentsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = rendezVousFiltres.map(rdv => {
        const patient = PATIENTS_MOCK.find(p => p.id === rdv.patientId);
        
        return `
            <tr>
                <td>
                    <div class="patient-info">
                        <img src="${rdv.patientPhoto}" alt="${rdv.patientNom}" class="patient-avatar">
                        <div class="patient-details">
                            <div class="patient-name">${rdv.patientNom}</div>
                            <div class="patient-phone">${rdv.patientTelephone}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="appointment-datetime">
                        <div class="appointment-date">${formatDate(rdv.date)}</div>
                        <div class="appointment-hour">${rdv.heureDebut} - ${rdv.heureFin}</div>
                    </div>
                </td>
                <td>${rdv.type}</td>
                <td>${rdv.motif}</td>
                <td><span class="status-badge ${rdv.statut}">${formatStatut(rdv.statut)}</span></td>
                <td>
                    <div class="appointment-actions">
                        <button class="action-btn" onclick="modifierRdv(${rdv.id})" title="Modifier">
                            ✏️
                        </button>
                        <button class="action-btn" onclick="confirmerRdv(${rdv.id})" title="Confirmer">
                            ✓
                        </button>
                        <button class="action-btn" onclick="annulerRdv(${rdv.id})" title="Annuler">
                            ✗
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    logDev(`${rendezVousFiltres.length} rendez-vous affichés`, 'success');
}

// ========== PATIENTS ==========
function afficherListePatients() {
    const container = document.getElementById('patientsList');
    if (!container) return;
    
    container.innerHTML = PATIENTS_MOCK.map(patient => {
        const age = calculerAge(patient.dateNaissance);
        
        return `
            <div class="patient-card" onclick="afficherDetailsPatient(${patient.id})">
                <img src="${patient.photo}" alt="${patient.nom}" class="patient-card-avatar">
                <div class="patient-card-info">
                    <h3>${patient.nom}</h3>
                    <div class="patient-card-meta">
                        <span>📅 ${age} ans</span>
                        <span>📞 ${patient.telephone}</span>
                        <span>📧 ${patient.email}</span>
                    </div>
                </div>
                <div class="patient-card-stats">
                    <div class="patient-stat">
                        <div class="patient-stat-value">${patient.nombreConsultations}</div>
                        <div class="patient-stat-label">Consultations</div>
                    </div>
                    <div class="patient-stat">
                        <div class="patient-stat-value">${patient.derniereVisite ? formatDateCourte(patient.derniereVisite) : 'N/A'}</div>
                        <div class="patient-stat-label">Dernière visite</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    logDev(`${PATIENTS_MOCK.length} patients affichés`, 'success');
}

// ========== DISPONIBILITÉS ==========
function afficherDisponibilites() {
    const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    const container = document.getElementById('disponibilitesGrid');
    
    if (!container) return;
    
    const html = `
        <div class="availability-card">
            <h3>Horaires hebdomadaires</h3>
            ${jours.map(jour => {
                const dispo = DISPONIBILITES_MOCK[jour];
                return `
                    <div class="day-availability">
                        <div class="day-toggle">
                            <input type="checkbox" id="day-${jour}" ${dispo.actif ? 'checked' : ''}>
                            <label for="day-${jour}">${capitaliser(jour)}</label>
                        </div>
                        <div class="time-slots-editor">
                            ${dispo.actif ? dispo.horaires.map((h, i) => `
                                <div class="time-slot-input">
                                    <input type="time" value="${h.debut}">
                                    <span>-</span>
                                    <input type="time" value="${h.fin}">
                                </div>
                            `).join('') : '<em style="color: var(--couleur-texte-leger)">Jour non travaillé</em>'}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
        
        <div class="availability-card">
            <h3>Congés et exceptions</h3>
            ${CONGES_MOCK.map(conge => `
                <div class="day-availability">
                    <strong>${formatDate(conge.debut)} - ${formatDate(conge.fin)}</strong>
                    <span>${conge.motif}</span>
                </div>
            `).join('')}
            <button class="btn-submit" style="margin-top: 1rem;">Ajouter une période de congé</button>
        </div>
    `;
    
    container.innerHTML = html;
}

// ========== PARAMÈTRES ==========
function afficherParametres() {
    const container = document.getElementById('parametresContent');
    if (!container) return;
    
    container.innerHTML = `
        <div class="settings-section">
            <h3>Informations professionnelles</h3>
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" value="${PRATICIEN_ACTUEL.nom}" class="form-control">
            </div>
            <div class="form-group">
                <label>Spécialité</label>
                <input type="text" value="${PRATICIEN_ACTUEL.specialite}" class="form-control">
            </div>
            <div class="form-group">
                <label>Numéro d'ordre</label>
                <input type="text" value="${PRATICIEN_ACTUEL.numeroOrdre}" class="form-control">
            </div>
        </div>
        
        <div class="settings-section">
            <h3>Coordonnées</h3>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="${PRATICIEN_ACTUEL.email}" class="form-control">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" value="${PRATICIEN_ACTUEL.telephone}" class="form-control">
            </div>
            <div class="form-group">
                <label>Adresse du cabinet</label>
                <textarea class="form-control" rows="2">${PRATICIEN_ACTUEL.adresse}</textarea>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>Consultations</h3>
            <div class="form-group">
                <label>Tarif de consultation (€)</label>
                <input type="number" value="${PRATICIEN_ACTUEL.tarifConsultation}" class="form-control">
            </div>
            <div class="form-group">
                <label>Durée par défaut (minutes)</label>
                <input type="number" value="${PRATICIEN_ACTUEL.dureeConsultation}" class="form-control">
            </div>
        </div>
        
        <button class="btn-submit">Enregistrer les modifications</button>
    `;
}

// ========== ACTIONS SUR LES RENDEZ-VOUS ==========
window.afficherDetailsRdv = function(id) {
    const rdv = RENDEZ_VOUS_MOCK.find(r => r.id === id);
    if (!rdv) return;
    
    alert(`Détails du rendez-vous\n\nPatient: ${rdv.patientNom}\nDate: ${formatDate(rdv.date)}\nHeure: ${rdv.heureDebut}\nMotif: ${rdv.motif}\nStatut: ${formatStatut(rdv.statut)}`);
};

window.modifierRdv = function(id) {
    logDev(`Modification RDV ${id}`, 'info');
    alert('Fonctionnalité de modification à implémenter');
};

window.confirmerRdv = function(id) {
    const rdv = RENDEZ_VOUS_MOCK.find(r => r.id === id);
    if (rdv) {
        rdv.statut = 'confirmed';
        afficherListeRendezVous();
        logDev(`RDV ${id} confirmé`, 'success');
    }
};

window.annulerRdv = function(id) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
        const rdv = RENDEZ_VOUS_MOCK.find(r => r.id === id);
        if (rdv) {
            rdv.statut = 'cancelled';
            afficherListeRendezVous();
            logDev(`RDV ${id} annulé`, 'warning');
        }
    }
};

window.afficherDetailsPatient = function(id) {
    const patient = PATIENTS_MOCK.find(p => p.id === id);
    if (!patient) return;
    
    const age = calculerAge(patient.dateNaissance);
    alert(`Détails du patient\n\nNom: ${patient.nom}\nÂge: ${age} ans\nTéléphone: ${patient.telephone}\nEmail: ${patient.email}\nConsultations: ${patient.nombreConsultations}\nAllergies: ${patient.allergies.join(', ') || 'Aucune'}`);
};

// ========== FONCTIONS UTILITAIRES ==========
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { weekday: 'short', day: 'numeric', month: 'short' };
    return date.toLocaleDateString('fr-FR', options);
}

function formatDateCourte(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
}

function formatMoisAnnee(date) {
    const options = { month: 'long', year: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

function formatJourSemaine(date) {
    return date.toLocaleDateString('fr-FR', { weekday: 'short' });
}

function formatStatut(statut) {
    const statuts = {
        'confirmed': 'Confirmé',
        'pending': 'En attente',
        'cancelled': 'Annulé',
        'completed': 'Terminé'
    };
    return statuts[statut] || statut;
}

function getDebutSemaine(date) {
    const d = new Date(date);
    const jour = d.getDay();
    const diff = d.getDate() - jour + (jour === 0 ? -6 : 1); // Lundi
    return new Date(d.setDate(diff));
}

function calculerAge(dateNaissance) {
    const aujourd = new Date();
    const naissance = new Date(dateNaissance);
    let age = aujourd.getFullYear() - naissance.getFullYear();
    const m = aujourd.getMonth() - naissance.getMonth();
    if (m < 0 || (m === 0 && aujourd.getDate() < naissance.getDate())) {
        age--;
    }
    return age;
}

function capitaliser(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
