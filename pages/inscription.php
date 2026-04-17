<?php 
require 'config.php' ;
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/inscription.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Doctomatique">
                Doctomatique
            </a>
            <div class="nav-links">
                <a href="auth.php" class="btn-connexion">Se connecter</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="inscription-container">
            <div class="inscription-header">
                <h1>Créer un compte</h1>
                <p>Rejoignez Doctomatique pour gérer vos rendez-vous médicaux</p>
            </div>

            <form action="traitement-inscription1.php" method="POST" class="inscription-form">
                
                <div class="form-group">
                    <label for="type_compte">Type de compte :</label>
                    <select name="type_compte" id="type_compte" required>
                        <option value="patient">Je suis patient uniquement</option>
                        <option value="secretaire">Je suis patient et secrétaire</option>
                        <option value="praticien">Je suis patient et médecin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" name="email" id="email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" name="nom" id="nom" placeholder="Votre nom" required>
                </div>

                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" name="prenom" id="prenom" placeholder="Votre prénom" required>
                </div>

                <div class="form-group">
                    <label for="telephone">Téléphone :</label>
                    <input type="tel" name="telephone" id="telephone" placeholder="06 12 34 56 78" required>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe :</label>
                    <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="••••••••" required minlength="4">
                </div>

                <div class="form-group">
                    <label for="confirmation_mot_de_passe">Confirmer le mot de passe :</label>
                    <input type="password" name="confirmation_mot_de_passe" id="confirmation_mot_de_passe" placeholder="••••••••" required minlength="4">
                </div>

                <button type="submit">S'inscrire</button>
            </form>

            <div class="inscription-footer">
                <p>Vous avez déjà un compte ? <a href="auth.php">Se connecter</a></p>
            </div>
        </div>
    </main>

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

</body>
</html>