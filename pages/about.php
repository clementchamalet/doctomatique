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
    <title>À propos</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/about.css">
</head>
<body>

    <!-- ========== EN-TÊTE DE LA PAGE ========== -->
    <header>
        <nav>
            <!-- Logo principal avec lien vers l'accueil -->
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>

            <!-- Navigation principale -->
            <div class="nav-links">
                <a href="patient.php">Espace patient</a>
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <main>
        <div class="about-container">

            <!-- Section d'introduction / Hero -->
            <div class="about-hero">
                <h1>À propos de nous</h1>
                <p>Nous sommes quatre étudiants en école d'ingénieurs passionnés par l'innovation, et nous souhaitons améliorer l'accès aux soins de santé.</p>
            </div>

            <!-- Notre Mission -->
            <div class="mission-vision">
                <div class="mission-card">
                    <h3>Notre Mission</h3>
                    <p>
                        Notre plateforme de prise de rendez-vous médicaux vise à simplifier l'accès aux soins pour tous. 
                        En tant que projet à but non lucratif, nous croyons que la technologie peut améliorer la santé publique 
                        en rendant la coordination des soins plus accessible, plus rapide et plus efficace.
                    </p>
                </div>
            </div>

            <!-- Section Qui sommes-nous, Vision, Engagement -->
            <div class="values-grid">
                <!-- Carte Qui sommes-nous -->
                <div class="value-card">
                    <div class="value-icon">🎓</div>
                    <h3>Qui sommes-nous ?</h3>
                    <p>Une équipe de quatre étudiants ingénieurs motivés par l'innovation technologique au service de la santé.</p>
                </div>

                <!-- Carte Vision -->
                <div class="value-card">
                    <div class="value-icon">💡</div>
                    <h3>Notre Vision</h3>
                    <p>Créer une solution accessible qui facilite la prise de rendez-vous médicaux et améliore l'expérience patient.</p>
                </div>

                <!-- Carte Engagement -->
                <div class="value-card">
                    <div class="value-icon">🤝</div>
                    <h3>Engagement</h3>
                    <p>Un projet à but non lucratif et open source guidé par notre volonté de contribuer positivement à l'accès aux soins.</p>
                </div>
            </div>

        </div>
    </main>

    <!-- ========== PIED DE PAGE ========== -->
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