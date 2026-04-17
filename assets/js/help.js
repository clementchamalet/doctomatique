/**
 * Module formulaire de contact - help.js
 * Gere la soumission du formulaire de contact avec AJAX
 * Enregistre les messages dans assets/messages/
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (!contactForm) {
        console.error('Formulaire de contact non trouve');
        return;
    }

    // Gestion de la soumission du formulaire
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Empecher la soumission normale

        // Recuperer les valeurs
        const nom = document.getElementById('nom').value.trim();
        const email = document.getElementById('email').value.trim();
        const sujet = document.getElementById('sujet').value.trim();
        const message = document.getElementById('message').value.trim();

        // Validation basique cote client
        if (!nom || !email || !sujet || !message) {
            showMessage('Veuillez remplir tous les champs', 'error');
            return;
        }

        // Valider l'email
        if (!isValidEmail(email)) {
            showMessage('Adresse email invalide', 'error');
            return;
        }

        // Soumettre le formulaire via AJAX
        submitForm(nom, email, sujet, message);
    });

    /**
     * Valide le format d'une adresse email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Soumet le formulaire au serveur via AJAX
     */
    function submitForm(nom, email, sujet, message) {
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('sujet', sujet);
        formData.append('message', message);

        // Desactiver le bouton pendant l'envoi
        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        fetch('help.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                showMessage(data.message, 'success');
                contactForm.reset(); // Vider le formulaire
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Une erreur est survenue lors de l\'envoi. Veuillez reessayer.', 'error');
        })
        .finally(() => {
            // Reactiver le bouton
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    /**
     * Affiche un message de succes ou d'erreur
     */
    function showMessage(text, type) {
        formMessage.textContent = text;
        formMessage.className = 'message';
        formMessage.classList.add(type);
        
        // Scroll vers le message pour le voir
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Effacer le message apres 5 secondes pour les erreurs, 7 pour les success
        const duration = type === 'success' ? 7000 : 5000;
        setTimeout(() => {
            formMessage.textContent = '';
            formMessage.className = 'message';
        }, duration);
    }
});