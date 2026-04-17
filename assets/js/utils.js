/* ========================================
   UTILS.JS - FONCTIONS UTILITAIRES
   Fonctions réutilisables dans tout le projet
   ======================================== */

// ========== VALIDATION ==========

/**
 * Valide le format d'une adresse email
 * @param {string} email - L'adresse email à valider
 * @returns {boolean} - true si l'email est valide, false sinon
 */
export function validerEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Valide la force d'un mot de passe
 * @param {string} motdepasse - Le mot de passe à valider
 * @returns {Object} - { isValid, score, niveau, message }
 */
export function validerMotdepasse(motdepasse) {
    let score = 0;
    
    // Longueur minimale
    if (motdepasse.length >= 8) score++;
    if (motdepasse.length >= 12) score++;
    
    // Contient des lettres minuscules
    if (/[a-z]/.test(motdepasse)) score++;
    
    // Contient des lettres majuscules
    if (/[A-Z]/.test(motdepasse)) score++;
    
    // Contient des chiffres
    if (/[0-9]/.test(motdepasse)) score++;
    
    // Contient des caractères spéciaux
    if (/[^a-zA-Z0-9]/.test(motdepasse)) score++;
    
    // Détermination du niveau
    let niveau, message;
    if (score <= 2) {
        niveau = 'faible';
        message = 'Mot de passe faible';
    } else if (score <= 4) {
        niveau = 'moyen';
        message = 'Mot de passe moyen';
    } else {
        niveau = 'fort';
        message = 'Mot de passe fort';
    }
    
    return {
        isValid: motdepasse.length >= 8,
        score,
        niveau,
        message
    };
}

/**
 * Valide qu'un champ n'est pas vide
 * @param {string} value - La valeur à valider
 * @returns {boolean} - true si non vide, false sinon
 */
export function validerChampNonVide(value) {
    return value && value.trim().length > 0;
}

// ========== NOTIFICATIONS ==========

/**
 * Affiche une notification (à améliorer avec une librairie comme Toastify)
 * @param {string} message - Le message à afficher
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 */
export function afficherNotification(message, type = 'info') {
    // Pour l'instant, utilise alert
    // TODO: Remplacer par une vraie notification visuelle
    
    const prefix = {
        'success': '✓ ',
        'error': '✗ ',
        'warning': '⚠ ',
        'info': 'ℹ '
    }[type] || '';
    
    alert(prefix + message);
    
    // Alternative avec console coloré en développement
    const styles = {
        'success': 'color: #10b981; font-weight: bold;',
        'error': 'color: #ef4444; font-weight: bold;',
        'warning': 'color: #f59e0b; font-weight: bold;',
        'info': 'color: #3b82f6; font-weight: bold;'
    };
    
    console.log(`%c${prefix}${message}`, styles[type] || '');
}

/**
 * Affiche une notification de succès
 * @param {string} message - Le message de succès
 */
export function notificationSucces(message) {
    afficherNotification(message, 'success');
}

/**
 * Affiche une notification d'erreur
 * @param {string} message - Le message d'erreur
 */
export function notificationErreur(message) {
    afficherNotification(message, 'error');
}

// ========== GESTION DES FORMULAIRES ==========

/**
 * Récupère toutes les valeurs d'un formulaire
 * @param {string} formId - L'ID du formulaire
 * @returns {Object} - Objet avec les valeurs des champs
 */
export function recupererValeursFormulaire(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        console.error(`Formulaire "${formId}" introuvable`);
        return null;
    }
    
    const formData = new FormData(form);
    const values = {};
    
    for (let [key, value] of formData.entries()) {
        values[key] = value;
    }
    
    return values;
}

/**
 * Réinitialise un formulaire
 * @param {string} formId - L'ID du formulaire
 */
export function reinitialiserFormulaire(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

/**
 * Active/désactive un bouton de soumission
 * @param {string} buttonId - L'ID du bouton
 * @param {boolean} actif - true pour activer, false pour désactiver
 */
export function toggleBoutonSoumission(buttonId, actif) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = !actif;
        button.style.opacity = actif ? '1' : '0.6';
        button.style.cursor = actif ? 'pointer' : 'not-allowed';
    }
}

// ========== GESTION DES ÉVÉNEMENTS CLAVIER ==========

/**
 * Ajoute un listener pour la touche Entrée sur un champ
 * @param {string} elementId - L'ID de l'élément
 * @param {Function} callback - La fonction à exécuter
 */
export function ajouterListenerEntree(elementId, callback) {
    const element = document.getElementById(elementId);
    if (element) {
        element.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                callback();
            }
        });
    }
}

/**
 * Gère la navigation au clavier entre champs
 * @param {Array<string>} fieldIds - Tableau des IDs des champs dans l'ordre
 */
export function gererNavigationClavier(fieldIds) {
    fieldIds.forEach((fieldId, index) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Si c'est le dernier champ, soumettre le formulaire
                    if (index === fieldIds.length - 1) {
                        const form = field.closest('form');
                        if (form) {
                            form.dispatchEvent(new Event('submit'));
                        }
                    } else {
                        // Sinon, passer au champ suivant
                        const nextField = document.getElementById(fieldIds[index + 1]);
                        if (nextField) {
                            nextField.focus();
                        }
                    }
                }
            });
        }
    });
}

// ========== GESTION DU DOM ==========

/**
 * Affiche/masque un élément
 * @param {string} elementId - L'ID de l'élément
 * @param {boolean} visible - true pour afficher, false pour masquer
 */
export function toggleElement(elementId, visible) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = visible ? 'block' : 'none';
    }
}

/**
 * Ajoute une classe CSS à un élément
 * @param {string} elementId - L'ID de l'élément
 * @param {string} className - Le nom de la classe
 */
export function ajouterClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add(className);
    }
}

/**
 * Retire une classe CSS d'un élément
 * @param {string} elementId - L'ID de l'élément
 * @param {string} className - Le nom de la classe
 */
export function retirerClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove(className);
    }
}

/**
 * Bascule une classe CSS sur un élément
 * @param {string} elementId - L'ID de l'élément
 * @param {string} className - Le nom de la classe
 */
export function toggleClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle(className);
    }
}

// ========== MANIPULATION DE TEXTE ==========

/**
 * Capitalise la première lettre d'une chaîne
 * @param {string} str - La chaîne à capitaliser
 * @returns {string} - La chaîne capitalisée
 */
export function capitaliser(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

/**
 * Tronque un texte à une longueur donnée
 * @param {string} text - Le texte à tronquer
 * @param {number} maxLength - La longueur maximale
 * @returns {string} - Le texte tronqué
 */
export function tronquerTexte(text, maxLength) {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// ========== FORMATAGE DE DATES ==========

/**
 * Formate une date en format français
 * @param {Date|string} date - La date à formater
 * @returns {string} - La date formatée (ex: "12 décembre 2025")
 */
export function formaterDate(date) {
    const d = new Date(date);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('fr-FR', options);
}

/**
 * Formate une heure
 * @param {Date|string} date - La date contenant l'heure
 * @returns {string} - L'heure formatée (ex: "14:30")
 */
export function formaterHeure(date) {
    const d = new Date(date);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

// ========== GESTION DU STOCKAGE LOCAL ==========

/**
 * Sauvegarde une donnée dans le localStorage
 * @param {string} key - La clé
 * @param {any} value - La valeur (sera convertie en JSON)
 */
export function sauvegarderLocal(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.error('Erreur lors de la sauvegarde locale:', error);
    }
}

/**
 * Récupère une donnée du localStorage
 * @param {string} key - La clé
 * @returns {any} - La valeur récupérée (ou null)
 */
export function recupererLocal(key) {
    try {
        const value = localStorage.getItem(key);
        return value ? JSON.parse(value) : null;
    } catch (error) {
        console.error('Erreur lors de la récupération locale:', error);
        return null;
    }
}

/**
 * Supprime une donnée du localStorage
 * @param {string} key - La clé
 */
export function supprimerLocal(key) {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        console.error('Erreur lors de la suppression locale:', error);
    }
}

// ========== GESTION DES PARAMÈTRES URL ==========

/**
 * Récupère un paramètre de l'URL
 * @param {string} paramName - Le nom du paramètre
 * @returns {string|null} - La valeur du paramètre ou null
 */
export function getURLParam(paramName) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(paramName);
}

/**
 * Récupère tous les paramètres de l'URL
 * @returns {Object} - Objet avec tous les paramètres
 */
export function getAllURLParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const params = {};
    for (let [key, value] of urlParams.entries()) {
        params[key] = value;
    }
    return params;
}

// ========== DEBUGGING ==========

/**
 * Log en couleur dans la console (pour le développement)
 * @param {string} message - Le message à logger
 * @param {string} type - Type: 'info', 'success', 'warning', 'error'
 */
export function logDev(message, type = 'info') {
    const styles = {
        'info': 'color: #3b82f6; font-weight: bold;',
        'success': 'color: #10b981; font-weight: bold;',
        'warning': 'color: #f59e0b; font-weight: bold;',
        'error': 'color: #ef4444; font-weight: bold;'
    };
    
    console.log(`%c[DEV] ${message}`, styles[type] || styles.info);
}
