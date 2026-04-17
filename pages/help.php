<?php

require 'config.php' ;

if (isset($_SESSION['user_id'])) {
    $link = 'user-logout.php' ;
    $affichage = 'Se déconnecter' ;
}
else {
    $link = 'auth.php' ;
    $affichage = 'Se connecter' ;
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aide et Contact - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/help.css">
</head>
<body>

    <!-- EN-TETE DE LA PAGE -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            <div class="nav-links">
                <a href="patient.php">Espace patient</a>
                
                <!-- Bouton de connexion -->
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- CONTENU PRINCIPAL -->
    <main>
        <div class="help-container">

            <!-- SECTION INTRODUCTION HERO -->
            <div class="help-hero">
                <h1>Comment pouvons-nous vous aider ?</h1>
                <p>Notre equipe est la pour repondre a toutes vos questions</p>
            </div>

            <!-- SECTION FORMULAIRE CONTACT -->
            <div class="contact-section">
                <h2>Envoyez-nous un message</h2>
                <p class="subtitle">Nous vous repondrons dans les plus brefs delais</p>

                <!-- Message succes/erreur -->
                <div id="formMessage" class="message"></div>

                <!-- Formulaire contact -->
                <form class="contact-form" id="contactForm" method="POST" action="">
                    
                    <!-- Champ Nom -->
                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <div class="input-with-icon">
                            <img src="../assets/images/icone-personne.png" alt="Nom" class="input-icon">
                            <input type="text" id="nom" name="nom" placeholder="Prenom Nom" required>
                        </div>
                    </div>

                    <!-- Champ Email -->
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <div class="input-with-icon">
                            <img src="../assets/images/icone-email.png" alt="Email" class="input-icon">
                            <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                        </div>
                    </div>

                    <!-- Champ Sujet -->
                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <div class="input-with-icon">
                            <img src="../assets/images/icone-check.png" alt="Sujet" class="input-icon">
                            <input type="text" id="sujet" name="sujet" placeholder="Objet de votre demande" required>
                        </div>
                    </div>

                    <!-- Champ Message -->
                    <div class="form-group">
                        <label for="message">Message</label>
                        <div class="input-with-icon">
                            <img src="../assets/images/icone-email.png" alt="Message" class="input-icon">
                            <textarea id="message" name="message" placeholder="Decrivez votre demande en detail..." required></textarea>
                        </div>
                    </div>

                    <!-- Bouton soumission -->
                    <button type="submit" class="btn-submit">Envoyer le message</button>
                </form>
            </div>

            <!-- APERCU FAQ -->
            <div class="faq-preview">
                <h2>Questions frequentes</h2>
                <p class="subtitle">Trouvez rapidement des reponses a vos questions</p>

                <div class="faq-item">
                    <div class="faq-question">Comment prendre rendez-vous sur Doctomatique ?</div>
                    <div class="faq-answer">Utilisez la barre de recherche pour trouver un praticien pres de chez vous, consultez ses disponibilites, puis selectionnez un creneau qui vous convient.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Puis-je annuler ou modifier mon rendez-vous ?</div>
                    <div class="faq-answer">Oui, vous pouvez annuler ou modifier votre rendez-vous jusqu'a 24h avant la consultation depuis votre espace patient.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Est-ce que Doctomatique est gratuit pour les patients ?</div>
                    <div class="faq-answer">Oui, la prise de rendez-vous est entierement gratuite pour les patients. Nous facturons uniquement les praticiens.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Comment creer un compte patient ?</div>
                    <div class="faq-answer">Cliquez sur "Connexion" en haut de la page, puis selectionnez "Inscription" et remplissez le formulaire avec vos informations.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Mes donnees personnelles sont-elles securisees ?</div>
                    <div class="faq-answer">Absolument. Nous utilisons un hebergement certifie HDS et toutes vos donnees sont protegees conformement au RGPD.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Comment contacter mon praticien apres la prise de rendez-vous ?</div>
                    <div class="faq-answer">Une fois votre rendez-vous confirme, vous trouverez les coordonnees du cabinet dans votre espace patient.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Que faire si je ne trouve pas de praticien disponible ?</div>
                    <div class="faq-answer">Essayez d'elargir votre zone de recherche ou d'activer les notifications pour etre alerte des nouvelles disponibilites.</div>
                </div>
            </div>

        </div>
    </main>

    <!-- PIED DE PAGE -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Doctomatique</h3>
                <p>La plateforme de santé qui simplifie votre quotidien</p>
            </div>
            
            <div class="footer-section">
                <h4>Patients</h4>
                <a href="help.php">Aide et contact</a>
                <a href="patient.php">Espace client</a>
            </div>
            
            <div class="footer-section">
                <h4>Professionnels</h4>
                <a href="prices.php">Nos solutions</a>
                <a href="professional.php">Espace professionnel</a>
                <a href="admin-login.php">Espace administrateur</a>
                <a href="professional_sec.php">Espace secrétaire</a>
            </div>
            
            <div class="footer-section">
                <h4>Informations</h4>
                <a href="about.php">À propos</a>
                <a href="legal-mentions.php">Mentions légales</a>
                <a href="confidentiality.php">Confidentialité</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 Doctomatique. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- SCRIPT JAVASCRIPT -->
    <script src="../assets/js/help.js"></script>

    <?php
    // TRAITEMENT FORMULAIRE COTE SERVEUR
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $nom = isset($_POST['nom']) ? htmlspecialchars(trim($_POST['nom'])) : '';
            $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
            $sujet = isset($_POST['sujet']) ? htmlspecialchars(trim($_POST['sujet'])) : '';
            $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';
            
            $errors = [];
            
            if (empty($nom)) $errors[] = 'Le nom est requis';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
            if (empty($sujet)) $errors[] = 'Le sujet est requis';
            if (empty($message)) $errors[] = 'Le message est requis';
            
            if (empty($errors)) {
                
                // Chemin du dossier messages
                $root_dir = dirname(__DIR__);
                $messages_dir = $root_dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'messages' . DIRECTORY_SEPARATOR;
                
                // Creer le dossier s'il n'existe pas
                if (!is_dir($messages_dir)) {
                    @mkdir($messages_dir, 0777, true);
                }
                
                if (!is_dir($messages_dir)) {
                    throw new Exception('Impossible de creer le dossier: ' . $messages_dir);
                }
                
                // Verifier les permissions
                if (!is_writable($messages_dir)) {
                    @chmod($messages_dir, 0777);
                }
                
                // Fichier du jour
                $log_file = $messages_dir . 'contact_' . date('Y-m-d') . '.txt';
                
                // Contenu du message
                $log_entry = "\n";
                $log_entry .= "========================================\n";
                $log_entry .= "NOUVEAU MESSAGE - " . date('d/m/Y a H:i:s') . "\n";
                $log_entry .= "========================================\n";
                $log_entry .= "Nom : " . $nom . "\n";
                $log_entry .= "Email : " . $email . "\n";
                $log_entry .= "Sujet : " . $sujet . "\n\n";
                $log_entry .= "Message :\n";
                $log_entry .= "----------------------------------------\n";
                $log_entry .= $message . "\n";
                $log_entry .= "----------------------------------------\n\n";
                
                // Ecrire dans le fichier
                if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
                    throw new Exception('Erreur ecriture fichier: ' . $log_file);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Votre message a ete envoye avec succes !'
                ]);
                exit;
                
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => implode(' | ', $errors)
                ]);
                exit;
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    ?>

</body>
</html>