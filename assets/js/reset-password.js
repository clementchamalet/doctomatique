/* ========================================
   RESET-PASSWORD.JS - FORGOTTEN PASSWORD
   Password reset handling
   ======================================== */

import {
    validerEmail,
    validerMotdepasse,
    notificationErreur,
    notificationSucces,
    ajouterListenerEntree,
    getURLParam,
    logDev
} from './utils.js';

// ========== STEP HANDLING ==========

/**
 * Shows a specific step
 * @param {string} idEtape - The ID of the step to show
 */
function afficherEtape(idEtape) {
    // Get all steps
    const etapes = document.querySelectorAll('.etape');

    // Hide all steps
    etapes.forEach(etape => etape.classList.remove('active'));

    // Show the selected step
    const etapeCible = document.getElementById(idEtape);
    if (etapeCible) {
        etapeCible.classList.add('active');
        logDev(`Étape affichée: ${idEtape}`, 'info');
    }
}

// ========== STEP 1: RESET REQUEST ==========

/**
 * Handles the reset request
 * @param {Event} e - The submit event
 */
function soumettreDemandeReset(e) {
    e.preventDefault();

    // Get the email
    const email = document.getElementById('emailReset').value.trim();

    // Validate the email format
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }

    logDev(`Demande de réinitialisation pour: ${email}`, 'info');

    // TODO: API call to send the reset email
    console.log('Envoi de l\'email de réinitialisation à:', email);

    // Simulated API call
    setTimeout(() => {
        // Show the email in the confirmation step
        document.getElementById('emailConfirme').textContent = email;

        // Move to the confirmation step
        afficherEtape('etapeConfirmation');

        notificationSucces('Email envoyé avec succès !');
    }, 500);
}

// ========== STEP 2: CONFIRMATION AND RESEND ==========

/**
 * Resends the reset email
 */
function renvoyerEmail() {
    // Get the confirmed email
    const email = document.getElementById('emailConfirme').textContent;

    logDev(`Renvoi de l'email à: ${email}`, 'info');

    // TODO: API call to resend the email
    console.log('Renvoi de l\'email de réinitialisation à:', email);

    notificationSucces(`Un nouvel email vient d'être envoyé à ${email}`);
}

// Make the function globally accessible
window.renvoyerEmail = renvoyerEmail;

// ========== STEP 3: NEW PASSWORD ==========

/**
 * Updates the password strength indicator in real time
 */
function mettreAJourForceMotdepasse() {
    const motdepasse = document.getElementById('nouveauMotdepasse').value;
    const barreForce = document.getElementById('strengthLevel');
    const texteForce = document.getElementById('strengthText');

    // If the field is empty, reset
    if (!motdepasse) {
        barreForce.className = 'strength-level';
        texteForce.textContent = '';
        return;
    }

    // Use the validation function from utils.js
    const resultat = validerMotdepasse(motdepasse);

    // Update the display
    barreForce.className = `strength-level ${resultat.niveau}`;
    texteForce.textContent = resultat.message;

    // Colors based on the level
    const couleurs = {
        'faible': '#ef4444',
        'moyen': '#f59e0b',
        'fort': '#10b981'
    };
    texteForce.style.color = couleurs[resultat.niveau];
}

/**
 * Handles submission of the new password
 * @param {Event} e - The submit event
 */
function soumettrNouveauMotdepasse(e) {
    e.preventDefault();

    // Get the values
    const motdepasse = document.getElementById('nouveauMotdepasse').value;
    const confirmation = document.getElementById('confirmationNouveauMotdepasse').value;

    // Validate the length
    if (motdepasse.length < 8) {
        notificationErreur('Le mot de passe doit contenir au moins 8 caractères');
        return;
    }

    // Check that they match
    if (motdepasse !== confirmation) {
        notificationErreur('Les mots de passe ne correspondent pas');
        return;
    }

    logDev('Nouveau mot de passe défini', 'success');

    // TODO: API call to save the new password
    console.log('Enregistrement du nouveau mot de passe');

    // Simulated API call
    setTimeout(() => {
        // Move to the success step
        afficherEtape('etapeSucces');

        notificationSucces('Mot de passe réinitialisé avec succès !');
    }, 500);
}

// ========== URL PARAMETER HANDLING ==========

/**
 * Checks whether a token is present in the URL
 * If so, shows the new password step directly
 */
function verifierTokenURL() {
    const token = getURLParam('token');

    if (token) {
        logDev(`Token détecté: ${token}`, 'info');

        // TODO: Validate the token with the server
        console.log('Validation du token:', token);

        // Show step 3
        afficherEtape('etapeNouveauMotdepasse');
    } else {
        // No token, show step 1
        afficherEtape('etapeEmail');
    }
}

// ========== TEST FUNCTION (DEVELOPMENT) ==========

/**
 * Function to directly test a specific step
 * Useful for development and testing
 * @param {number} numeroEtape - Step number (1, 2, 3 or 4)
 */
function testerEtape(numeroEtape) {
    const etapes = {
        1: 'etapeEmail',
        2: 'etapeConfirmation',
        3: 'etapeNouveauMotdepasse',
        4: 'etapeSucces'
    };

    if (etapes[numeroEtape]) {
        afficherEtape(etapes[numeroEtape]);

        // If step 2, fill in a test email
        if (numeroEtape === 2) {
            document.getElementById('emailConfirme').textContent = 'test@exemple.com';
        }

        logDev(`Test: Étape ${numeroEtape} affichée`, 'success');
    } else {
        console.error('Numéro d\'étape invalide. Utilisez 1, 2, 3 ou 4.');
    }
}

// Make the function globally accessible for testing
window.testerEtape = testerEtape;

// ========== INITIALIZATION ==========

document.addEventListener('DOMContentLoaded', function() {
    logDev('Page de réinitialisation du mot de passe chargée', 'success');
    console.log('💡 Astuce: Utilisez testerEtape(1-4) dans la console pour tester les différentes étapes');

    // Check the token in the URL
    verifierTokenURL();

    // Set up forms
    const formDemandeReset = document.getElementById('formDemandeReset');
    const formNouveauMotdepasse = document.getElementById('formNouveauMotdepasse');
    const champNouveauMotdepasse = document.getElementById('nouveauMotdepasse');

    if (formDemandeReset) {
        formDemandeReset.addEventListener('submit', soumettreDemandeReset);
    }

    if (formNouveauMotdepasse) {
        formNouveauMotdepasse.addEventListener('submit', soumettrNouveauMotdepasse);
    }

    // Check password strength in real time
    if (champNouveauMotdepasse) {
        champNouveauMotdepasse.addEventListener('input', mettreAJourForceMotdepasse);
    }

    // Handle the Enter key
    ajouterListenerEntree('emailReset', () => {
        formDemandeReset.dispatchEvent(new Event('submit'));
    });

    ajouterListenerEntree('nouveauMotdepasse', () => {
        document.getElementById('confirmationNouveauMotdepasse').focus();
    });

    ajouterListenerEntree('confirmationNouveauMotdepasse', () => {
        formNouveauMotdepasse.dispatchEvent(new Event('submit'));
    });
});
