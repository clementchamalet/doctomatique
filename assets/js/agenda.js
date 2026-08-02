/**
 * AGENDA.JS - Schedule management script
 * Generates and displays the weekly agenda with appointments and availability
 */

// Get the PHP data injected into the HTML
const rendezvousData = Array.isArray(window.rendezvous) ? window.rendezvous : [];
const disponibilitesData = Array.isArray(window.disponibilites) ? window.disponibilites : [];
const debutSemaine = new Date(window.debutSemaine ?? new Date());
const aujourdhui = new Date().toDateString();


// Agenda configuration
const CONFIG = {
    HEURE_DEBUT: 8,
    HEURE_FIN: 20,
    HAUTEUR_HEURE: 80, // pixels per hour
    JOURS_SEMAINE: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']
};

/**
 * Main class for managing the agenda
 */
class AgendaManager {
    constructor() {
        this.grid = document.getElementById('agendaGrid');
        this.rendezvous = rendezvousData;
        this.disponibilites = disponibilitesData;
    }

    /**
     * Initializes the agenda
     */
    init() {
        this.genererAgenda();
    }

    /**
     * Generates the full agenda structure
     */
    genererAgenda() {
        if (!this.grid) {
            console.error('Élément agendaGrid non trouvé');
            return;
        }

        this.grid.innerHTML = '';

        // Generate the header
        this.genererEnTete();

        // Generate the hour rows
        this.genererLignesHoraires();

        // Place the slots
        this.placerCreneaux();
    }

    /**
     * Generates the header with the days of the week
     */
    genererEnTete() {
        // Empty column for the hours
        this.grid.appendChild(this.creerCelluleEnTete('', 'time-column'));

        // Columns for each day
        for (let i = 0; i < 7; i++) {
            const date = new Date(debutSemaine);
            date.setDate(date.getDate() + i);
            const isToday = date.toDateString() === aujourdhui;

            const cell = this.creerCelluleEnTete('', isToday ? 'today-header' : '');
            cell.innerHTML = `
                <div class="jour-header">
                    <span class="jour-nom">${CONFIG.JOURS_SEMAINE[i]}</span>
                    <span class="jour-date">${date.getDate().toString().padStart(2, '0')}</span>
                </div>
            `;
            this.grid.appendChild(cell);
        }
    }

    /**
     * Generates the hour rows (from 8am to 8pm)
     */
    genererLignesHoraires() {
        const totalHeures = CONFIG.HEURE_FIN - CONFIG.HEURE_DEBUT;
        const hauteurJournee = totalHeures * CONFIG.HAUTEUR_HEURE;

        // === Hour column (a single large column) ===
        const timeColumn = document.createElement('div');
        timeColumn.className = 'time-column-container';
        timeColumn.style.height = `${hauteurJournee}px`;
        this.grid.appendChild(timeColumn);

        for (let heure = CONFIG.HEURE_DEBUT; heure <= CONFIG.HEURE_FIN; heure++) {
            const label = document.createElement('div');
            label.className = 'time-label';
            label.textContent = `${heure.toString().padStart(2, '0')}:00`;

            const top =
                (heure - CONFIG.HEURE_DEBUT) * CONFIG.HAUTEUR_HEURE;

            label.style.top = `${top}px`;
            timeColumn.appendChild(label);
        }

        // === Day columns ===
        for (let jour = 0; jour < 7; jour++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-column';
            dayCell.dataset.jour = jour;
            dayCell.style.height = `${hauteurJournee}px`;
            dayCell.style.position = 'relative';

            this.grid.appendChild(dayCell);
        }
    }

    /**
     * Places all slots (appointments and availability)
     */
    placerCreneaux() {
        console.log("RDV DATA:", this.rendezvous);
        console.log("DISPO DATA:", this.disponibilites);

        let hasCreneaux = false;

        // Place the appointments
        this.rendezvous.forEach(rdv => {
            const creneau = this.creerCreneau(rdv, 'rdv');
            if (creneau) {
                const cell = this.trouverCellule(rdv.debut);
                if (cell) {
                    cell.appendChild(creneau);
                    hasCreneaux = true;
                }
            }
        });

        // Place the availability slots
        this.disponibilites.forEach(dispo => {
            const creneau = this.creerCreneau(dispo, 'dispo');
            if (creneau) {
                const cell = this.trouverCellule(dispo.debut);
                if (cell) {
                    cell.appendChild(creneau);
                    hasCreneaux = true;
                }
            }
        });

        // If there are no slots, show a message but keep the grid visible
        // The grid stays visible in all cases
    }

    /**
     * Creates a slot element (appointment or availability)
     */
    creerCreneau(data, type) {
        const debut = new Date(data.debut);
        const heureDebut = debut.getHours() + debut.getMinutes() / 60;
        const duree = data.duree_minute / 60;

        // Check whether the slot falls within the displayed hours
        if (heureDebut < CONFIG.HEURE_DEBUT || heureDebut >= CONFIG.HEURE_FIN) {
            return null;
        }

        const creneau = document.createElement('div');
        creneau.className = `creneau creneau-${type}`;

        // Vertical position based on the minutes
        const top = (heureDebut - CONFIG.HEURE_DEBUT) * CONFIG.HAUTEUR_HEURE;
        const hauteur = duree * CONFIG.HAUTEUR_HEURE;

        creneau.style.top = `${top}px`;
        creneau.style.height = `${Math.max(hauteur, 30)}px`; // Minimum height of 30px

        if (type === 'rdv') {
            const heureStr = this.formatHeure(debut);
            creneau.innerHTML = `
                <div class="creneau-titre">${this.escapeHtml(data.prenom)} ${this.escapeHtml(data.nom)}</div>
                <div class="creneau-info">${heureStr} - ${data.duree_minute} min</div>
                ${data.motif ? `<div class="creneau-info">${this.escapeHtml(data.motif)}</div>` : ''}
            `;
            creneau.onclick = () => {
                window.location.href = `consultation.php?rdv_id=${data.id}`;
            };
        } else {
            const heureStr = this.formatHeure(debut);
            creneau.innerHTML = `
                <div class="creneau-titre">Disponible</div>
                <div class="creneau-info">${heureStr} - ${data.duree_minute} min</div>
            `;
        }

        return creneau;
    }

    /**
     * Finds the cell matching a date/time
     */
    trouverCellule(dateStr) {
        const date = new Date(dateStr.replace(' ', 'T'));

        const diffTime =
            date.setHours(0, 0, 0, 0) -
            new Date(debutSemaine).setHours(0, 0, 0, 0);

        const jourSemaine = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (jourSemaine < 0 || jourSemaine > 6) return null;

        return document.querySelector(
            `.day-column[data-jour="${jourSemaine}"]`
        );
    }

    /**
     * Creates a header cell
     */
    creerCelluleEnTete(text, className = '') {
        const cell = document.createElement('div');
        cell.className = `agenda-header-cell ${className}`;
        cell.textContent = text;
        return cell;
    }

    /**
     * Formats a date as a time (HH:MM)
     */
    formatHeure(date) {
        const heures = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${heures}:${minutes}`;
    }

    /**
     * Escapes HTML characters to prevent XSS injection
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const agenda = new AgendaManager();
    agenda.init();
});

// Export for use in other modules if needed
export { AgendaManager, CONFIG };
