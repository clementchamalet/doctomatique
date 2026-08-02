<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes rendez-vous - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/config.php';
    requirePatient(); // Redirects if not logged in or not a patient
    ?>
    
    <!-- ========== HEADER ========== -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <div class="nav-links">
                <a href="search.php">Trouver un praticien</a>
                <a href="appointments.php" class="active">Mes rendez-vous</a>
                <span>Bonjour, <?= htmlspecialchars($_SESSION['user_firstname'] ?? 'Utilisateur') ?></span>
                <a href="user-logout.php" class="btn-connexion">Déconnexion</a>
            </div>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="appointments-page">
        <div class="container">
            <h1>Mes rendez-vous</h1>

            <!-- Status filters -->
            <div class="appointments-filters">
                <button class="filter-btn active" data-filter="all">
                    Tous <span class="count" id="countAll">0</span>
                </button>
                <button class="filter-btn" data-filter="upcoming">
                    À venir <span class="count" id="countUpcoming">0</span>
                </button>
                <button class="filter-btn" data-filter="past">
                    Passés <span class="count" id="countPast">0</span>
                </button>
                <button class="filter-btn" data-filter="cancelled">
                    Annulés <span class="count" id="countCancelled">0</span>
                </button>
            </div>

            <!-- Loading indicator -->
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner"></div>
                <p>Chargement de vos rendez-vous...</p>
            </div>

            <!-- Message when there are no appointments -->
            <div id="noAppointments" class="no-appointments" style="display: none;">
                <div class="no-appointments-icon">📅</div>
                <h2>Aucun rendez-vous</h2>
                <p>Vous n'avez pas encore de rendez-vous prévu.</p>
                <a href="search.php" class="btn-primary">Prendre rendez-vous</a>
            </div>

            <!-- Appointment list -->
            <div id="appointmentsList" class="appointments-list">
                <!-- Filled dynamically by JavaScript -->
            </div>
        </div>
    </main>

    <!-- Cancellation confirmation modal -->
    <div id="cancelModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Annuler le rendez-vous</h2>
            
            <div id="cancelSummary" class="cancel-summary">
                <!-- Filled dynamically -->
            </div>

            <p class="warning-text">
                ⚠️ Êtes-vous sûr de vouloir annuler ce rendez-vous ?
                Cette action est irréversible.
            </p>

            <div class="modal-actions">
                <button class="btn-secondary" id="btnCloseCancelModal">Non, garder</button>
                <button class="btn-danger" id="btnConfirmCancel">Oui, annuler</button>
            </div>
        </div>
    </div>

    <!-- Cancellation success modal -->
    <div id="cancelSuccessModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="success-icon">✓</div>
            <h2>Rendez-vous annulé</h2>
            <p>Votre rendez-vous a été annulé avec succès.</p>
            <button class="btn-primary" id="btnCloseCancelSuccess">Fermer</button>
        </div>
    </div>

    <!-- ========== FOOTER ========== -->
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

    <!-- Modular JavaScript -->
    <script type="module" src="../assets/js/appointments.js"></script>
</body>
</html>
