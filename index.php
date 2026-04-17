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

    <!-- ========== EN-TÊTE DE LA PAGE ========== -->
    <header>
        <nav>
            <!-- Logo principal avec lien vers l'accueil -->
            <a href="index.php" class="logo">
                <img src="assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <!-- Navigation principale -->
            <div class="nav-links">
                <!-- Lien vers l'espace professionnel -->
                <a href="pages/patient.php">Espace patient</a>
                
                <!-- Bouton de connexion -->
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <main>
        <div class="container">
            <!-- Titre principal de la page -->
            <h1>Trouvez un praticien près de chez vous</h1>
            
            <!-- Sous-titre explicatif -->
            <p class="subtitle">Réservez votre consultation en quelques clics</p>

            <!-- ========== FORMULAIRE DE RECHERCHE ========== -->
            <div class="search-box">
                <!-- Conteneur des champs de saisie -->
                <div class="search-inputs">
                    <!-- Champ de recherche : praticien/spécialité -->
                    <div class="input-wrapper">
                        <img src="assets/images/icone-practicien.png" alt="Icône praticien" class="input-icon">
                        <input 
                            type="text" 
                            id="champPraticien" 
                            placeholder="Médecin, spécialité, établissement..."
                        >
                    </div>
                    
                    <!-- Séparateur vertical entre les deux champs -->
                    <div class="separator"></div>
                    
                    <!-- Champ de recherche : localisation -->
                    <div class="input-wrapper">
                        <img src="assets/images/icone-localisation.png" alt="Icône localisation" class="input-icon">
                        <input 
                            type="text" 
                            id="champLocalisation" 
                            placeholder="Ville, code postal..."
                        >
                    </div>
                </div>
                
                <!-- Bouton de recherche -->
                <button class="btn-search">
                    <img src="assets/images/icone-recherche.png" alt="Icône recherche">
                    Rechercher
                </button>
            </div>
        </div>
    </main>

    <!-- ========== PIED DE PAGE ========== -->
    <footer>
        <div class="footer-content">
            <!-- Section À propos -->
            <div class="footer-section">
                <h3>Doctomatique</h3>
                <p>La plateforme de santé qui simplifie votre quotidien</p>
            </div>
            
            <!-- Section Patients -->
            <div class="footer-section">
                <h4>Patients</h4>
                <a href="pages/help.php">Aide et contact</a>
                <a href="pages/patient.php">Espace client</a>
            </div>
            
            <!-- Section Professionnels -->
            <div class="footer-section">
                <h4>Professionnels</h4>
                <a href="pages/prices.php">Nos solutions</a>
                <a href="pages/professional.php">Espace professionnel</a>
                <a href="pages/professional_sec.php">Espace secrétaire</a>
                <a href="pages/admin-login.php">Espace administrateur</a>
            </div>
            
            <!-- Section Informations légales -->
            <div class="footer-section">
                <h4>Informations</h4>
                <a href="pages/about.php">À propos</a>
                <a href="pages/legal-mentions.php">Mentions légales</a>
                <a href="pages/confidentiality.php">Confidentialité</a>
            </div>
        </div>
        
        <!-- Barre de copyright -->
        <div class="footer-bottom">
            <p>&copy; 2025 Doctomatique. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Fichier JavaScript pour l'autocomplete et la recherche -->
    <script src="assets/js/home.js"></script>
</body>
</html>
