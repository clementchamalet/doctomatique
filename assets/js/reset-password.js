/* ========================================
   RESET-PASSWORD.JS - MOT DE PASSE OUBLIÉ
   Gestion de la réinitialisation du mot de passe
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

// ========== GESTION DES ÉTAPES ==========

/**
 * Affiche une étape spécifique
 * @param {string} idEtape - L'ID de l'étape à afficher
 */
function afficherEtape(idEtape) {
    // Récupération de toutes les étapes
    const etapes = document.querySelectorAll('.etape');
    
    // Masquage de toutes les étapes
    etapes.forEach(etape => etape.classList.remove('active'));
    
    // Affichage de l'étape sélectionnée
    const etapeCible = document.getElementById(idEtape);
    if (etapeCible) {
        etapeCible.classList.add('active');
        logDev(`Étape affichée: ${idEtape}`, 'info');
    }
}

// ========== ÉTAPE 1 : DEMANDE DE RÉINITIALISATION ==========

/**
 * Traite la demande de réinitialisation
 * @param {Event} e - L'événement de soumission
 */
function soumettreDemandeReset(e) {
    e.preventDefault();
    
    // Récupération de l'email
    const email = document.getElementById('emailReset').value.trim();
    
    // Validation du format email
    if (!validerEmail(email)) {
        notificationErreur('Veuillez entrer une adresse email valide');
        return;
    }
    
    logDev(`Demande de réinitialisation pour: ${email}`, 'info');
    
    // TODO: Appel API pour envoyer l'email de réinitialisation
    console.log('Envoi de l\'email de réinitialisation à:', email);
    
    // Simulation d'un appel API
    setTimeout(() => {
        // Affichage de l'email dans l'étape de confirmation
        document.getElementById('emailConfirme').textContent = email;
        
        // Passage à l'étape de confirmation
        afficherEtape('etapeConfirmation');
        
        notificationSucces('Email envoyé avec succès !');
    }, 500);
}

// ========== ÉTAPE 2 : CONFIRMATION ET RENVOI ==========

/**
 * Renvoie l'email de réinitialisation
 */
function renvoyerEmail() {
    // Récupération de l'email confirmé
    const email = document.getElementById('emailConfirme').textContent;
    
    logDev(`Renvoi de l'email à: ${email}`, 'info');
    
    // TODO: Appel API pour renvoyer l'email
    console.log('Renvoi de l\'email de réinitialisation à:', email);
    
    notificationSucces(`Un nouvel email vient d'être envoyé à ${email}`);
}

// Rendre la fonction accessible globalement
window.renvoyerEmail = renvoyerEmail;

// ========== ÉTAPE 3 : NOUVEAU MOT DE PASSE ==========

/**
 * Met à jour l'indicateur de force du mot de passe en temps réel
 */
function mettreAJourForceMotdepasse() {
    const motdepasse = document.getElementById('nouveauMotdepasse').value;
    const barreForce = document.getElementById('strengthLevel');
    const texteForce = document.getElementById('strengthText');
    
    // Si le champ est vide, réinitialiser
    if (!motdepasse) {
        barreForce.className = 'strength-level';
        texteForce.textContent = '';
        return;
    }
    
    // Utilisation de la fonction de validation du utils.js
    const resultat = validerMotdepasse(motdepasse);
    
    // Mise à jour de l'affichage
    barreForce.className = `strength-level ${resultat.niveau}`;
    texteForce.textContent = resultat.message;
    
    // Couleurs selon le niveau
    const couleurs = {
        'faible': '#ef4444',
        'moyen': '#f59e0b',
        'fort': '#10b981'
    };
    texteForce.style.color = couleurs[resultat.niveau];
}

/**
 * Traite la soumission du nouveau mot de passe
 * @param {Event} e - L'événement de soumission
 */
function soumettrNouveauMotdepasse(e) {
    e.preventDefault();
    
    // Récupération des valeurs
    const motdepasse = document.getElementById('nouveauMotdepasse').value;
    const confirmation = document.getElementById('confirmationNouveauMotdepasse').value;
    
    // Validation de la longueur
    if (motdepasse.length < 8) {
        notificationErreur('Le mot de passe doit contenir au moins 8 caractères');
        return;
    }
    
    // Vérification de la correspondance
    if (motdepasse !== confirmation) {
        notificationErreur('Les mots de passe ne correspondent pas');
        return;
    }
    
    logDev('Nouveau mot de passe défini', 'success');
    
    // TODO: Appel API pour enregistrer le nouveau mot de passe
    console.log('Enregistrement du nouveau mot de passe');
    
    // Simulation d'un appel API
    setTimeout(() => {
        // Passage à l'étape de succès
        afficherEtape('etapeSucces');
        
        notificationSucces('Mot de passe réinitialisé avec succès !');
    }, 500);
}

// ========== GESTION DES PARAMÈTRES URL ==========

/**
 * Vérifie si un token est présent dans l'URL
 * Si oui, affiche directement l'étape de nouveau mot de passe
 */
function verifierTokenURL() {
    const token = getURLParam('token');
    
    if (token) {
        logDev(`Token détecté: ${token}`, 'info');
        
        // TODO: Valider le token auprès du serveur
        console.log('Validation du token:', token);
        
        // Affichage de l'étape 3
        afficherEtape('etapeNouveauMotdepasse');
    } else {
        // Pas de token, afficher l'étape 1
        afficherEtape('etapeEmail');
    }
}

// ========== FONCTION DE TEST (DÉVELOPPEMENT) ==========

/**
 * Fonction pour tester directement une étape spécifique
 * Utile pour le développement et les tests
 * @param {number} numeroEtape - Numéro de l'étape (1, 2, 3 ou 4)
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
        
        // Si étape 2, remplir l'email de test
        if (numeroEtape === 2) {
            document.getElementById('emailConfirme').textContent = 'test@exemple.com';
        }
        
        logDev(`Test: Étape ${numeroEtape} affichée`, 'success');
    } else {
        console.error('Numéro d\'étape invalide. Utilisez 1, 2, 3 ou 4.');
    }
}

// Rendre la fonction accessible globalement pour les tests
window.testerEtape = testerEtape;

// ========== INITIALISATION ==========

document.addEventListener('DOMContentLoaded', function() {
    logDev('Page de réinitialisation du mot de passe chargée', 'success');
    console.log('💡 Astuce: Utilisez testerEtape(1-4) dans la console pour tester les différentes étapes');
    
    // Vérification du token dans l'URL
    verifierTokenURL();
    
    // Gestion des formulaires
    const formDemandeReset = document.getElementById('formDemandeReset');
    const formNouveauMotdepasse = document.getElementById('formNouveauMotdepasse');
    const champNouveauMotdepasse = document.getElementById('nouveauMotdepasse');
    
    if (formDemandeReset) {
        formDemandeReset.addEventListener('submit', soumettreDemandeReset);
    }
    
    if (formNouveauMotdepasse) {
        formNouveauMotdepasse.addEventListener('submit', soumettrNouveauMotdepasse);
    }
    
    // Vérification de la force du mot de passe en temps réel
    if (champNouveauMotdepasse) {
        champNouveauMotdepasse.addEventListener('input', mettreAJourForceMotdepasse);
    }
    
    // Gestion de la touche Entrée
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
