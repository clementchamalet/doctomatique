<?php

require 'pages/config.php' ;

if (isset($_SESSION['user_id'])) {
    $link = 'pages/user-logout.php' ;
    $affichage = 'Se déconnecter' ;
}
else {
    $link = 'pages/auth.php' ;
    $affichage = 'Se connecter' ;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctomatique - Prenez rendez-vous en ligne</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>

    <!-- ========== PAGE HEADER ========== -->
    <header>
        <nav>
            <!-- Main logo linking back to the home page -->
            <a href="index.php" class="logo">
                <img src="assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>

            <!-- Main navigation -->
            <div class="nav-links">
                <!-- Link to the patient area -->
                <a href="pages/patient.php">Espace patient</a>

                <!-- Login button -->
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main>
        <div class="container">
            <!-- Main page title -->
            <h1>Trouvez un praticien près de chez vous</h1>

            <!-- Explanatory subtitle -->
            <p class="subtitle">Réservez votre consultation en quelques clics</p>

            <!-- ========== SEARCH FORM ========== -->
            <div class="search-box">
                <!-- Container for the input fields -->
                <div class="search-inputs">
                    <!-- Search field: practitioner/specialty -->
                    <div class="input-wrapper">
                        <img src="assets/images/icone-practicien.png" alt="Icône praticien" class="input-icon">
                        <input
                            type="text"
                            id="champPraticien"
                            placeholder="Médecin, spécialité, établissement..."
                        >
                    </div>

                    <!-- Vertical separator between the two fields -->
                    <div class="separator"></div>

                    <!-- Search field: location -->
                    <div class="input-wrapper">
                        <img src="assets/images/icone-localisation.png" alt="Icône localisation" class="input-icon">
                        <input
                            type="text"
                            id="champLocalisation"
                            placeholder="Ville, code postal..."
                        >
                    </div>
                </div>

                <!-- Search button -->
                <button class="btn-search">
                    <img src="assets/images/icone-recherche.png" alt="Icône recherche">
                    Rechercher
                </button>
            </div>
        </div>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer>
        <div class="footer-content">
            <!-- About section -->
            <div class="footer-section">
                <h3>Doctomatique</h3>
                <p>La plateforme de santé qui simplifie votre quotidien</p>
            </div>

            <!-- Patients section -->
            <div class="footer-section">
                <h4>Patients</h4>
                <a href="pages/help.php">Aide et contact</a>
                <a href="pages/patient.php">Espace client</a>
            </div>

            <!-- Professionals section -->
            <div class="footer-section">
                <h4>Professionnels</h4>
                <a href="pages/prices.php">Nos solutions</a>
                <a href="pages/professional.php">Espace professionnel</a>
                <a href="pages/professional_sec.php">Espace secrétaire</a>
                <a href="pages/admin-login.php">Espace administrateur</a>
            </div>

            <!-- Legal information section -->
            <div class="footer-section">
                <h4>Informations</h4>
                <a href="pages/about.php">À propos</a>
                <a href="pages/legal-mentions.php">Mentions légales</a>
                <a href="pages/confidentiality.php">Confidentialité</a>
            </div>
        </div>

        <!-- Copyright bar -->
        <div class="footer-bottom">
            <p>&copy; 2025 Doctomatique. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- JavaScript file for autocomplete and search -->
    <script src="assets/js/home.js"></script>
</body>
</html>
