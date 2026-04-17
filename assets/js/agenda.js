/**
 * AGENDA.JS - Script pour la gestion de l'agenda
 * Génère et affiche l'agenda hebdomadaire avec les rendez-vous et disponibilités
 */

// Récupération des données PHP injectées dans le HTML
const rendezvousData = Array.isArray(window.rendezvous) ? window.rendezvous : [];
const disponibilitesData = Array.isArray(window.disponibilites) ? window.disponibilites : [];
const debutSemaine = new Date(window.debutSemaine ?? new Date());
const aujourdhui = new Date().toDateString();


// Configuration de l'agenda
const CONFIG = {
    HEURE_DEBUT: 8,
    HEURE_FIN: 20,
    HAUTEUR_HEURE: 80, // pixels par heure
    JOURS_SEMAINE: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']
};

/**
 * Classe principale pour gérer l'agenda
 */
class AgendaManager {
    constructor() {
        this.grid = document.getElementById('agendaGrid');
        this.rendezvous = rendezvousData;
        this.disponibilites = disponibilitesData;
    }

    /**
     * Initialise l'agenda
     */
    init() {
        this.genererAgenda();
    }

    /**
     * Génère la structure complète de l'agenda
     */
    genererAgenda() {
        if (!this.grid) {
            console.error('Élément agendaGrid non trouvé');
            return;
        }

        this.grid.innerHTML = '';

        // Générer l'en-tête
        this.genererEnTete();

        // Générer les lignes horaires
        this.genererLignesHoraires();

        // Placer les créneaux
        this.placerCreneaux();
    }

    /**
     * Génère l'en-tête avec les jours de la semaine
     */
    genererEnTete() {
        // Colonne vide pour les heures
        this.grid.appendChild(this.creerCelluleEnTete('', 'time-column'));

        // Colonnes pour chaque jour
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
     * Génère les lignes horaires (de 8h à 20h)
     */
    genererLignesHoraires() {
        const totalHeures = CONFIG.HEURE_FIN - CONFIG.HEURE_DEBUT;
        const hauteurJournee = totalHeures * CONFIG.HAUTEUR_HEURE;

        // === Colonne des heures (une seule grande colonne) ===
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

        // === Colonnes des jours ===
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
     * Place tous les créneaux (rendez-vous et disponibilités)
     */
    placerCreneaux() {
        console.log("RDV DATA:", this.rendezvous);
        console.log("DISPO DATA:", this.disponibilites);

        let hasCreneaux = false;

        // Placer les rendez-vous
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

        // Placer les disponibilités
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

        // Si aucun créneau, afficher un message mais garder la grille visible
        // La grille reste visible dans tous les cas
    }

    /**
     * Crée un élément créneau (rendez-vous ou disponibilité)
     */
    creerCreneau(data, type) {
        const debut = new Date(data.debut);
        const heureDebut = debut.getHours() + debut.getMinutes() / 60;
        const duree = data.duree_minute / 60;

        // Vérifier si le créneau est dans les heures d'affichage
        if (heureDebut < CONFIG.HEURE_DEBUT || heureDebut >= CONFIG.HEURE_FIN) {
            return null;
        }

        const creneau = document.createElement('div');
        creneau.className = `creneau creneau-${type}`;

        // Position verticale basée sur les minutes
        const top = (heureDebut - CONFIG.HEURE_DEBUT) * CONFIG.HAUTEUR_HEURE;
        const hauteur = duree * CONFIG.HAUTEUR_HEURE;

        creneau.style.top = `${top}px`;
        creneau.style.height = `${Math.max(hauteur, 30)}px`; // Hauteur minimale de 30px

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
     * Trouve la cellule correspondant à une date/heure
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
     * Crée une cellule d'en-tête
     */
    creerCelluleEnTete(text, className = '') {
        const cell = document.createElement('div');
        cell.className = `agenda-header-cell ${className}`;
        cell.textContent = text;
        return cell;
    }

    /**
     * Formate une date en heure (HH:MM)
     */
    formatHeure(date) {
        const heures = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${heures}:${minutes}`;
    }

    /**
     * Échappe les caractères HTML pour éviter les injections XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    const agenda = new AgendaManager();
    agenda.init();
});

// Export pour utilisation dans d'autres modules si nécessaire
export { AgendaManager, CONFIG };