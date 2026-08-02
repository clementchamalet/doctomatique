/**
 * Contact form module - help.js
 * Handles contact form submission via AJAX
 * Saves messages to assets/messages/
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (!contactForm) {
        console.error('Formulaire de contact non trouve');
        return;
    }

    // Handle form submission
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the normal submission

        // Get the values
        const nom = document.getElementById('nom').value.trim();
        const email = document.getElementById('email').value.trim();
        const sujet = document.getElementById('sujet').value.trim();
        const message = document.getElementById('message').value.trim();

        // Basic client-side validation
        if (!nom || !email || !sujet || !message) {
            showMessage('Veuillez remplir tous les champs', 'error');
            return;
        }

        // Validate the email
        if (!isValidEmail(email)) {
            showMessage('Adresse email invalide', 'error');
            return;
        }

        // Submit the form via AJAX
        submitForm(nom, email, sujet, message);
    });

    /**
     * Validates the format of an email address
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Submits the form to the server via AJAX
     */
    function submitForm(nom, email, sujet, message) {
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('sujet', sujet);
        formData.append('message', message);

        // Disable the button while sending
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
                contactForm.reset(); // Clear the form
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Une erreur est survenue lors de l\'envoi. Veuillez reessayer.', 'error');
        })
        .finally(() => {
            // Re-enable the button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    /**
     * Displays a success or error message
     */
    function showMessage(text, type) {
        formMessage.textContent = text;
        formMessage.className = 'message';
        formMessage.classList.add(type);
        
        // Scroll to the message so it's visible
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Clear the message after 5 seconds for errors, 7 for success
        const duration = type === 'success' ? 7000 : 5000;
        setTimeout(() => {
            formMessage.textContent = '';
            formMessage.className = 'message';
        }, duration);
    }
});