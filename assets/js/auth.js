/* ========================================
   AUTH.JS - LOGIN / SIGNUP
   Authentication handling
   ======================================== */

import {
    validerEmail,
    validerChampNonVide,
    notificationErreur,
    notificationSucces,
    gererNavigationClavier,
    logDev
} from './utils.js';

// ========== TAB HANDLING ==========

/**
 * Shows the selected form (login or signup)
 * @param {string} typeFormulaire - 'connexion' or 'inscription'
 */
function afficherFormulaire(typeFormulaire) {
    // Get all tabs and forms
    const onglets = document.querySelectorAll('.tab-btn');
    const formulaires = document.querySelectorAll('.auth-form');

    // Remove the 'active' class from all tabs
    onglets.forEach(onglet => onglet.classList.remove('active'));

    // Hide all forms
    formulaires.forEach(formulaire => formulaire.classList.remove('active'));

    // Show the selected form
    if (typeFormulaire === 'connexion') {
        onglets[0].classList.add('active');
        document.getElementById('formConnexion').classList.add('active');
        logDev('Formulaire de connexion affiché', 'info');
    } else if (typeFormulaire === 'inscription') {
        onglets[1].classList.add('active');
        document.getElementById('formInscription').classList.add('active');
        logDev('Formulaire d\'inscription affiché', 'info');
    }
}

// Make the function globally accessible for onclick buttons in HTML
window.afficherFormulaire = afficherFormulaire;

// ========== LOGIN FORM HANDLING ==========

/**
 * Handles submission of the login form
 * @param {Event} e - The submit event
 */
function soumettreConnexion(e) {
    e.preventDefault();

    // Get the values
    const email = document.getElementById('emailConnexion').value.trim();
    const motdepasse = document.getElementById('motdepasseConnexion').value;

    // Validate fields
    if (!validerChampNonVide(email) || !validerChampNonVide(motdepasse)) {
        notificationErreur('Veuillez remplir tous les champs');
        return;
    }

    // Validate the email format
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }

    // Log for development
    logDev(`Tentative de connexion - Email: ${email}`, 'info');

    // TODO: API call for login
    console.log('Données de connexion:', { email, motdepasse: '***' });

    // Simulated successful login
    notificationSucces(`Connexion en cours...\nEmail: ${email}`);

    // TODO: Redirect after successful login
    // window.location.href = 'espace-patient.html';
}

// ========== SIGNUP FORM HANDLING ==========

/**
 * Handles submission of the signup form
 * @param {Event} e - The submit event
 */
function soumettreInscription(e) {
    e.preventDefault();

    // Get the values
    const nomComplet = document.getElementById('nomComplet').value.trim();
    const email = document.getElementById('emailInscription').value.trim();
    const motdepasse = document.getElementById('motdepasseInscription').value;
    const confirmation = document.getElementById('confirmationMotdepasse').value;
    const accepteCGU = document.getElementById('accepterCGU').checked;

    // Validation: all fields must be filled in
    if (!validerChampNonVide(nomComplet) ||
        !validerChampNonVide(email) ||
        !validerChampNonVide(motdepasse) ||
        !validerChampNonVide(confirmation)) {
        notificationErreur('Veuillez remplir tous les champs');
        return;
    }

    // Validate the email format
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }

    // Validate the password length
    if (motdepasse.length < 8) {
        notificationErreur('Le mot de passe doit contenir au moins 8 caractères');
        return;
    }

    // Check that the passwords match
    if (motdepasse !== confirmation) {
        notificationErreur('Les mots de passe ne correspondent pas');
        return;
    }

    // Check that the terms are accepted
    if (!accepteCGU) {
        notificationErreur('Vous devez accepter les Conditions Générales d\'Utilisation');
        return;
    }

    // Log for development
    logDev(`Tentative d'inscription - Nom: ${nomComplet}, Email: ${email}`, 'info');

    // TODO: API call for signup
    console.log('Données d\'inscription:', { nomComplet, email, motdepasse: '***' });

    // Simulated successful signup
    notificationSucces(`Création du compte en cours...\nNom: ${nomComplet}\nEmail: ${email}`);

    // TODO: Redirect after successful signup
    // window.location.href = 'espace-patient.html';
}

// ========== GOOGLE LOGIN ==========

/**
 * Handles login via Google OAuth
 */
function connexionGoogle() {
    logDev('Tentative de connexion avec Google', 'info');

    // TODO: Implement with the Google OAuth API
    alert('La connexion avec Google sera bientôt disponible');

    // Example of a future implementation:
    // window.location.href = 'https://accounts.google.com/o/oauth2/v2/auth?...';
}

// Make the function globally accessible
window.connexionGoogle = connexionGoogle;

// ========== PASSWORD VISIBILITY TOGGLE ==========

/**
 * Toggles the password field's visibility
 * @param {string} idChamp - The password field's ID
 */
function basculerAffichageMotdepasse(idChamp) {
    const champ = document.getElementById(idChamp);
    if (champ) {
        champ.type = champ.type === 'password' ? 'text' : 'password';
        logDev(`Affichage du mot de passe ${champ.type === 'text' ? 'activé' : 'désactivé'}`, 'info');
    }
}

// Make the function globally accessible
window.basculerAffichageMotdepasse = basculerAffichageMotdepasse;

// ========== INITIALIZATION ==========

document.addEventListener('DOMContentLoaded', function() {
    logDev('Page de connexion/inscription chargée', 'success');

    // Show the login form by default
    afficherFormulaire('connexion');

    // Set up forms
    const formConnexion = document.getElementById('formConnexion');
    const formInscription = document.getElementById('formInscription');

    if (formConnexion) {
        formConnexion.addEventListener('submit', soumettreConnexion);
    }

    if (formInscription) {
        formInscription.addEventListener('submit', soumettreInscription);
    }

    // Keyboard navigation for the login form
    gererNavigationClavier([
        'emailConnexion',
        'motdepasseConnexion'
    ]);

    // Keyboard navigation for the signup form
    gererNavigationClavier([
        'nomComplet',
        'emailInscription',
        'motdepasseInscription',
        'confirmationMotdepasse'
    ]);
});
