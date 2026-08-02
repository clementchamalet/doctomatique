/* ========================================
   UTILS.JS - UTILITY FUNCTIONS
   Functions reusable across the whole project
   ======================================== */

// ========== VALIDATION ==========

/**
 * Validates the format of an email address
 * @param {string} email - The email address to validate
 * @returns {boolean} - true if the email is valid, false otherwise
 */
export function validerEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validates the strength of a password
 * @param {string} motdepasse - The password to validate
 * @returns {Object} - { isValid, score, niveau, message }
 */
export function validerMotdepasse(motdepasse) {
    let score = 0;

    // Minimum length
    if (motdepasse.length >= 8) score++;
    if (motdepasse.length >= 12) score++;

    // Contains lowercase letters
    if (/[a-z]/.test(motdepasse)) score++;

    // Contains uppercase letters
    if (/[A-Z]/.test(motdepasse)) score++;

    // Contains digits
    if (/[0-9]/.test(motdepasse)) score++;

    // Contains special characters
    if (/[^a-zA-Z0-9]/.test(motdepasse)) score++;

    // Determine the strength level
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
 * Validates that a field is not empty
 * @param {string} value - The value to validate
 * @returns {boolean} - true if not empty, false otherwise
 */
export function validerChampNonVide(value) {
    return value && value.trim().length > 0;
}

// ========== NOTIFICATIONS ==========

/**
 * Displays a notification (to be improved with a library like Toastify)
 * @param {string} message - The message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 */
export function afficherNotification(message, type = 'info') {
    // For now, uses alert
    // TODO: Replace with an actual visual notification

    const prefix = {
        'success': '✓ ',
        'error': '✗ ',
        'warning': '⚠ ',
        'info': 'ℹ '
    }[type] || '';

    alert(prefix + message);

    // Colored console alternative for development
    const styles = {
        'success': 'color: #10b981; font-weight: bold;',
        'error': 'color: #ef4444; font-weight: bold;',
        'warning': 'color: #f59e0b; font-weight: bold;',
        'info': 'color: #3b82f6; font-weight: bold;'
    };

    console.log(`%c${prefix}${message}`, styles[type] || '');
}

/**
 * Displays a success notification
 * @param {string} message - The success message
 */
export function notificationSucces(message) {
    afficherNotification(message, 'success');
}

/**
 * Displays an error notification
 * @param {string} message - The error message
 */
export function notificationErreur(message) {
    afficherNotification(message, 'error');
}

// ========== FORM HANDLING ==========

/**
 * Gets all values from a form
 * @param {string} formId - The form's ID
 * @returns {Object} - Object with the field values
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
 * Resets a form
 * @param {string} formId - The form's ID
 */
export function reinitialiserFormulaire(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

/**
 * Enables/disables a submit button
 * @param {string} buttonId - The button's ID
 * @param {boolean} actif - true to enable, false to disable
 */
export function toggleBoutonSoumission(buttonId, actif) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = !actif;
        button.style.opacity = actif ? '1' : '0.6';
        button.style.cursor = actif ? 'pointer' : 'not-allowed';
    }
}

// ========== KEYBOARD EVENT HANDLING ==========

/**
 * Adds a listener for the Enter key on a field
 * @param {string} elementId - The element's ID
 * @param {Function} callback - The function to run
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
 * Handles keyboard navigation between fields
 * @param {Array<string>} fieldIds - Array of field IDs in order
 */
export function gererNavigationClavier(fieldIds) {
    fieldIds.forEach((fieldId, index) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    // If this is the last field, submit the form
                    if (index === fieldIds.length - 1) {
                        const form = field.closest('form');
                        if (form) {
                            form.dispatchEvent(new Event('submit'));
                        }
                    } else {
                        // Otherwise, move to the next field
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

// ========== DOM HANDLING ==========

/**
 * Shows/hides an element
 * @param {string} elementId - The element's ID
 * @param {boolean} visible - true to show, false to hide
 */
export function toggleElement(elementId, visible) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = visible ? 'block' : 'none';
    }
}

/**
 * Adds a CSS class to an element
 * @param {string} elementId - The element's ID
 * @param {string} className - The class name
 */
export function ajouterClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add(className);
    }
}

/**
 * Removes a CSS class from an element
 * @param {string} elementId - The element's ID
 * @param {string} className - The class name
 */
export function retirerClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove(className);
    }
}

/**
 * Toggles a CSS class on an element
 * @param {string} elementId - The element's ID
 * @param {string} className - The class name
 */
export function toggleClasse(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle(className);
    }
}

// ========== TEXT MANIPULATION ==========

/**
 * Capitalizes the first letter of a string
 * @param {string} str - The string to capitalize
 * @returns {string} - The capitalized string
 */
export function capitaliser(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

/**
 * Truncates a text to a given length
 * @param {string} text - The text to truncate
 * @param {number} maxLength - The maximum length
 * @returns {string} - The truncated text
 */
export function tronquerTexte(text, maxLength) {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// ========== DATE FORMATTING ==========

/**
 * Formats a date in French format
 * @param {Date|string} date - The date to format
 * @returns {string} - The formatted date (e.g. "12 décembre 2025")
 */
export function formaterDate(date) {
    const d = new Date(date);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('fr-FR', options);
}

/**
 * Formats a time
 * @param {Date|string} date - The date containing the time
 * @returns {string} - The formatted time (e.g. "14:30")
 */
export function formaterHeure(date) {
    const d = new Date(date);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

// ========== LOCAL STORAGE HANDLING ==========

/**
 * Saves data to localStorage
 * @param {string} key - The key
 * @param {any} value - The value (will be converted to JSON)
 */
export function sauvegarderLocal(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.error('Erreur lors de la sauvegarde locale:', error);
    }
}

/**
 * Gets data from localStorage
 * @param {string} key - The key
 * @returns {any} - The retrieved value (or null)
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
 * Removes data from localStorage
 * @param {string} key - The key
 */
export function supprimerLocal(key) {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        console.error('Erreur lors de la suppression locale:', error);
    }
}

// ========== URL PARAMETER HANDLING ==========

/**
 * Gets a parameter from the URL
 * @param {string} paramName - The parameter name
 * @returns {string|null} - The parameter value or null
 */
export function getURLParam(paramName) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(paramName);
}

/**
 * Gets all URL parameters
 * @returns {Object} - Object with all parameters
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
 * Logs to the console in color (for development)
 * @param {string} message - The message to log
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
