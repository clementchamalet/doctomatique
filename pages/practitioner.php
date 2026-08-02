<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Praticien - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/practitioner.css">
</head>
<body>

    <?php 
    require_once 'config.php';
    
    // Check the practitioner_id parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: search.php');
        exit;
    }
    
    $practitionerId = intval($_GET['id']);
    $structureId = isset($_GET['structure']) ? intval($_GET['structure']) : null;
    ?>
    
    <!-- ========== HEADER ========== -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <a href="patient.php">Mes rendez-vous</a>
                    <a href="user-logout.php" class="btn-connexion">Deconnexion</a>
                <?php else: ?>
                    <a href="patient.php">Espace patient</a>
                    <a href="auth.php" class="btn-connexion">Connexion</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="practitioner-page">
        <div class="container">

            <!-- Loading indicator -->
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner"></div>
                <p>Chargement des informations...</p>
            </div>

            <!-- Main container -->
            <div id="practitionerContent" class="practitioner-content" style="display: none;">
                
                <!-- Simplified practitioner header -->
                <div class="practitioner-header">
                    <div class="practitioner-info">
                        <h1 id="practitionerName"></h1>
                        <p id="practitionerSpecialties" class="specialties"></p>
                    </div>
                </div>

                <!-- Two-column layout -->
                <div class="content-layout">
                    
                    <!-- Main column -->
                    <div class="main-column">
                        
                        <!-- About -->
                        <section class="info-section">
                            <h2>Présentation</h2>
                            <div id="practitionerBio" class="bio-text"></div>
                        </section>

                        <!-- Consultation locations -->
                        <section class="info-section">
                            <h2>Lieux de consultation</h2>
                            <div id="structuresList" class="structures-list">
                                <!-- Filled dynamically -->
                            </div>
                        </section>

                        <!-- Patient reviews -->
                        <section class="info-section" id="reviewsSection">
                            <h2>Avis des patients</h2>
                            <div id="reviewsList" class="reviews-list">
                                <!-- Filled dynamically -->
                            </div>
                        </section>
                    </div>

                    <!-- Sidebar column: Booking -->
                    <aside class="sidebar-column">
                        <div class="booking-card sticky">
                            <h3>Prendre rendez-vous</h3>
                            
                            <!-- Facility selection -->
                            <div class="form-group">
                                <label for="selectStructure">Lieu de consultation</label>
                                <select id="selectStructure" class="form-control">
                                    <option value="">Selectionnez un lieu</option>
                                    <!-- Filled dynamically -->
                                </select>
                            </div>

                            <!-- Availability calendar -->
                            <div id="slotsContainer" class="slots-container" style="display: none;">
                                <h4>Creneaux disponibles</h4>
                                
                                <div id="loadingSlots" class="loading-slots" style="display: none;">
                                    <div class="spinner-small"></div>
                                    <span>Chargement des creneaux...</span>
                                </div>

                                <div id="noSlots" class="no-slots" style="display: none;">
                                    <p>Aucun creneau disponible pour les 14 prochains jours.</p>
                                </div>

                                <div id="slotsList" class="slots-list">
                                    <!-- Filled dynamically -->
                                </div>
                            </div>

                            <?php if (!isLoggedIn()): ?>
                            <div class="login-notice">
                                <p>Vous devez être connecté pour réserver un rendez-vous.</p>
                                <a href="auth.php" class="btn-primary">Se connecter</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <!-- Booking confirmation modal -->
    <div id="bookingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Confirmer votre rendez-vous</h2>
            
            <div id="bookingSummary" class="booking-summary">
                <!-- Filled dynamically -->
            </div>

            <form id="bookingForm">
                <input type="hidden" id="selectedSlotId">
                
                <div class="form-group">
                    <label for="bookingReason">Motif de consultation *</label>
                    <select id="bookingReason" class="form-control" required>
                        <option value="">Selectionnez un motif</option>
                        <option value="1">Consultation generale</option>
                        <option value="2">Consultation de suivi</option>
                        <option value="3">Urgence</option>
                        <option value="4">Certificat medical</option>
                        <option value="5">Vaccination</option>
                        <option value="6">Examen de routine</option>
                        <option value="7">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bookingNotes">Informations complementaires</label>
                    <textarea 
                        id="bookingNotes" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Decrivez brievement votre demande (facultatif)"
                    ></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="btnCancelBooking">Annuler</button>
                    <button type="submit" class="btn-primary" id="btnConfirmBooking">
                        Confirmer le rendez-vous
                    </button>
                </div>
            </form>

            <div id="bookingSuccess" class="booking-success" style="display: none;">
                <div class="success-icon">OK</div>
                <h3>Rendez-vous confirme !</h3>
                <p>Vous recevrez une confirmation par email.</p>
                <div class="modal-actions">
                    <a href="patient.php" class="btn-primary">Voir mes rendez-vous</a>
                    <button class="btn-secondary" id="btnCloseSuccess">Fermer</button>
                </div>
            </div>
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

    <!-- Passing PHP parameters to JavaScript -->
    <script>
        window.PRACTITIONER_ID = <?= $practitionerId ?>;
        window.STRUCTURE_ID = <?= $structureId ?? 'null' ?>;
        window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
    </script>

    <!-- Modular JavaScript -->
    <script type="module" src="../assets/js/practitioner.js"></script>
</body>
</html>
