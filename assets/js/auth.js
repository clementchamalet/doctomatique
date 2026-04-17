/* ========================================
   AUTH.JS - CONNEXION / INSCRIPTION
   Gestion de l'authentification
   ======================================== */

import { 
    validerEmail, 
    validerChampNonVide,
    notificationErreur,
    notificationSucces,
    gererNavigationClavier,
    logDev
} from './utils.js';

// ========== GESTION DES ONGLETS ==========

/**
 * Affiche le formulaire sélectionné (connexion ou inscription)
 * @param {string} typeFormulaire - 'connexion' ou 'inscription'
 */
function afficherFormulaire(typeFormulaire) {
    // Récupération de tous les onglets et formulaires
    const onglets = document.querySelectorAll('.tab-btn');
    const formulaires = document.querySelectorAll('.auth-form');
    
    // Retrait de la classe 'active' de tous les onglets
    onglets.forEach(onglet => onglet.classList.remove('active'));
    
    // Masquage de tous les formulaires
    formulaires.forEach(formulaire => formulaire.classList.remove('active'));
    
    // Affichage du formulaire sélectionné
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

// Rendre la fonction accessible globalement pour les boutons onclick dans HTML
window.afficherFormulaire = afficherFormulaire;

// ========== GESTION DU FORMULAIRE DE CONNEXION ==========

/**
 * Traite la soumission du formulaire de connexion
 * @param {Event} e - L'événement de soumission
 */
function soumettreConnexion(e) {
    e.preventDefault();
    
    // Récupération des valeurs
    const email = document.getElementById('emailConnexion').value.trim();
    const motdepasse = document.getElementById('motdepasseConnexion').value;
    
    // Validation des champs
    if (!validerChampNonVide(email) || !validerChampNonVide(motdepasse)) {
        notificationErreur('Veuillez remplir tous les champs');
        return;
    }
    
    // Validation du format email
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }
    
    // Log pour le développement
    logDev(`Tentative de connexion - Email: ${email}`, 'info');
    
    // TODO: Appel API pour la connexion
    console.log('Données de connexion:', { email, motdepasse: '***' });
    
    // Simulation de connexion réussie
    notificationSucces(`Connexion en cours...\nEmail: ${email}`);
    
    // TODO: Redirection après connexion réussie
    // window.location.href = 'espace-patient.html';
}

// ========== GESTION DU FORMULAIRE D'INSCRIPTION ==========

/**
 * Traite la soumission du formulaire d'inscription
 * @param {Event} e - L'événement de soumission
 */
function soumettreInscription(e) {
    e.preventDefault();
    
    // Récupération des valeurs
    const nomComplet = document.getElementById('nomComplet').value.trim();
    const email = document.getElementById('emailInscription').value.trim();
    const motdepasse = document.getElementById('motdepasseInscription').value;
    const confirmation = document.getElementById('confirmationMotdepasse').value;
    const accepteCGU = document.getElementById('accepterCGU').checked;
    
    // Validation : tous les champs doivent être remplis
    if (!validerChampNonVide(nomComplet) || 
        !validerChampNonVide(email) || 
        !validerChampNonVide(motdepasse) || 
        !validerChampNonVide(confirmation)) {
        notificationErreur('Veuillez remplir tous les champs');
        return;
    }
    
    // Validation du format email
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }
    
    // Validation de la longueur du mot de passe
    if (motdepasse.length < 8) {
        notificationErreur('Le mot de passe doit contenir au moins 8 caractères');
        return;
    }
    
    // Vérification que les mots de passe correspondent
    if (motdepasse !== confirmation) {
        notificationErreur('Les mots de passe ne correspondent pas');
        return;
    }
    
    // Vérification que les CGU sont acceptées
    if (!accepteCGU) {
        notificationErreur('Vous devez accepter les Conditions Générales d\'Utilisation');
        return;
    }
    
    // Log pour le développement
    logDev(`Tentative d'inscription - Nom: ${nomComplet}, Email: ${email}`, 'info');
    
    // TODO: Appel API pour l'inscription
    console.log('Données d\'inscription:', { nomComplet, email, motdepasse: '***' });
    
    // Simulation d'inscription réussie
    notificationSucces(`Création du compte en cours...\nNom: ${nomComplet}\nEmail: ${email}`);
    
    // TODO: Redirection après inscription réussie
    // window.location.href = 'espace-patient.html';
}

// ========== CONNEXION AVEC GOOGLE ==========

/**
 * Gère la connexion via Google OAuth
 */
function connexionGoogle() {
    logDev('Tentative de connexion avec Google', 'info');
    
    // TODO: Implémenter avec l'API Google OAuth
    alert('La connexion avec Google sera bientôt disponible');
    
    // Exemple d'implémentation future :
    // window.location.href = 'https://accounts.google.com/o/oauth2/v2/auth?...';
}

// Rendre la fonction accessible globalement
window.connexionGoogle = connexionGoogle;

// ========== AFFICHAGE/MASQUAGE DU MOT DE PASSE ==========

/**
 * Bascule l'affichage du mot de passe
 * @param {string} idChamp - L'ID du champ de mot de passe
 */
function basculerAffichageMotdepasse(idChamp) {
    const champ = document.getElementById(idChamp);
    if (champ) {
        champ.type = champ.type === 'password' ? 'text' : 'password';
        logDev(`Affichage du mot de passe ${champ.type === 'text' ? 'activé' : 'désactivé'}`, 'info');
    }
}

// Rendre la fonction accessible globalement
window.basculerAffichageMotdepasse = basculerAffichageMotdepasse;

// ========== INITIALISATION ==========

document.addEventListener('DOMContentLoaded', function() {
    logDev('Page de connexion/inscription chargée', 'success');
    
    // Affichage du formulaire de connexion par défaut
    afficherFormulaire('connexion');
    
    // Gestion des formulaires
    const formConnexion = document.getElementById('formConnexion');
    const formInscription = document.getElementById('formInscription');
    
    if (formConnexion) {
        formConnexion.addEventListener('submit', soumettreConnexion);
    }
    
    if (formInscription) {
        formInscription.addEventListener('submit', soumettreInscription);
    }
    
    // Navigation au clavier pour le formulaire de connexion
    gererNavigationClavier([
        'emailConnexion',
        'motdepasseConnexion'
    ]);
    
    // Navigation au clavier pour le formulaire d'inscription
    gererNavigationClavier([
        'nomComplet',
        'emailInscription',
        'motdepasseInscription',
        'confirmationMotdepasse'
    ]);
});
