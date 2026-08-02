<?php
    require 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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
                <a href="inscription.php" class="btn-connexion">S'inscrire</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <div class="login-header">
                <h1>Connexion</h1>
                <br>
                <p>Accédez à votre espace personnel</p>
            </div>

            <br>

            <form action="traitement-login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="user_email">Email :</label>
                    <input type="email" name="user_email" id="user_email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
                <br>

                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <div class="login-footer">
                <p>Vous n'avez pas de compte ? <a href="inscription.php">S'inscrire</a></p>
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