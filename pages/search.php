<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de praticiens - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/search.css">
    <link rel="stylesheet" href="../assets/css/search-autocomplete.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <?php require_once __DIR__ . '/config.php'; ?>
    
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <a href="patient.php">Mes rendez-vous</a>
                    <a href="user-logout.php" class="btn-connexion">Déconnexion</a>
                <?php else: ?>
                    <a href="patient.php">Espace patient</a>
                    <a href="auth.php" class="btn-connexion">Connexion</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="search-page">
        <div class="search-header">
            <div class="container">
                <div class="search-box">
                    <div class="search-inputs">
                        <div class="autocomplete-wrapper">
                            <input 
                                type="text" 
                                id="filterSpecialty" 
                                class="autocomplete-input"
                                placeholder="Type de médecin (cardiologue, dentiste...)"
                            >
                            <div id="autocompleteSpecialties" class="autocomplete-list"></div>
                        </div>
                        
                        <div class="separator"></div>
                        
                        <div class="autocomplete-wrapper">
                            <input 
                                type="text" 
                                id="filterCity" 
                                class="autocomplete-input"
                                placeholder="Ville"
                            >
                            <div id="autocompleteCities" class="autocomplete-list"></div>
                        </div>
                    </div>
                    
                    <button id="btnSearch" class="btn-search">Rechercher</button>
                </div>
            </div>
        </div>

        <div class="search-container">
            <div class="results-panel">
                <div class="results-header">
                    <h2>Praticiens trouvés</h2>
                    <span id="resultsCount" class="results-count"></span>
                </div>

                <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Recherche en cours...</p>
                </div>

                <div id="practitionersList" class="practitioners-list">
                </div>
            </div>

            <div class="map-panel">
                <div id="map" class="map-container"></div>
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

    <script src="../assets/js/search.js"></script>
</body>
</html>
