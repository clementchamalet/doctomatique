<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites.php', 'nouvelle_disponibilite.php', 'modifier_disponibilites.php', 'edit_disponibilite.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients.php', 'consultation.php'];
$pagesRendezvous = ['rdv.php', 'desistement'];
$pagesStats = ['professional.php', 'statistiques.php'];
$pagesParametres = ['parametres.php'];

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Vérifier que l'utilisateur est un praticien
$stmt = mysqli_prepare($connexion, "SELECT 1 FROM praticiens WHERE utilisateur_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}

// Récupérer les infos du praticien
$stmt = mysqli_prepare(
    $connexion,
    "SELECT u.prenom, u.nom
     FROM utilisateurs u
     JOIN praticiens p ON p.utilisateur_id = u.id
     WHERE u.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$praticien = mysqli_fetch_assoc($result);

// Récupérer les spécialités
$stmt = mysqli_prepare(
    $connexion,
    "SELECT sm.nom
     FROM specialites_medecine sm
     JOIN praticiens_specialites ps ON ps.specialite_id = sm.id
     WHERE ps.praticien_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$specialites = [];
while ($row = mysqli_fetch_assoc($res)) {
    $specialites[] = $row['nom'];
}

// Déterminer la semaine à afficher
$semaine_offset = isset($_GET['semaine']) ? intval($_GET['semaine']) : 0;
$date_reference = new DateTime();
$date_reference->modify("$semaine_offset week");
$date_reference->modify('Monday this week');

// Calculer les dates de début et fin de semaine
$debut_semaine = clone $date_reference;
$fin_semaine = clone $date_reference;
$fin_semaine->modify('+6 days');

// Récupérer les rendez-vous de la semaine
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        r.patient_id,
        c.debut,
        c.duree_minute,
        u.prenom,
        u.nom,
        m.description as motif,
        s.description as statut
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN patients p ON r.patient_id = p.utilisateur_id
     JOIN utilisateurs u ON p.utilisateur_id = u.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND c.debut >= ?
     AND c.debut < ?
     ORDER BY c.debut"
);

$debut_str = $debut_semaine->format('Y-m-d 00:00:00');
$fin_str = $fin_semaine->format('Y-m-d 23:59:59');
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $debut_str, $fin_str);
mysqli_stmt_execute($stmt);
$result_rdv = mysqli_stmt_get_result($stmt);

$rendez_vous = [];
while ($row = mysqli_fetch_assoc($result_rdv)) {
    $rendez_vous[] = $row;
}

// Récupérer les disponibilités de la semaine (sans rendez-vous associé)
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        c.id,
        c.debut,
        c.duree_minute
     FROM creneaux_disponibilite c
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND c.debut >= ?
     AND c.debut < ?
     AND r.id IS NULL
     ORDER BY c.debut"
);

mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $debut_str, $fin_str);
mysqli_stmt_execute($stmt);
$result_dispo = mysqli_stmt_get_result($stmt);

$disponibilites = [];
while ($row = mysqli_fetch_assoc($result_dispo)) {
    $disponibilites[] = $row;
}

// Préparer les données pour JavaScript
$rdv_json = json_encode($rendez_vous, JSON_UNESCAPED_UNICODE);
$dispo_json = json_encode($disponibilites, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/agenda.css">
</head>
<body>
    <!-- ========== EN-TÊTE DE LA PAGE ========== -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <div class="nav-links">
                <a href="auth.php" class="btn-connexion">Déconnexion</a>
            </div>
        </nav>
    </header>

    <!-- ========== LAYOUT PRINCIPAL ========== -->
    <div class="pro-layout">
        
        <!-- ========== SIDEBAR NAVIGATION ========== -->
        <aside class="pro-sidebar">
            <div class="pro-sidebar-header">
                <h2>Dr. <?= htmlspecialchars($praticien['prenom']) ?> <?= htmlspecialchars($praticien['nom']) ?></h2>
                <p><?= htmlspecialchars(implode(', ', $specialites)) ?></p>
            </div>
           <nav class="pro-nav">
                <a href="professional.php" class="pro-nav-item <?= in_array($currentPage, $pagesStats) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📊</span>
                    <span>Tableau de bord</span>
                </a>
                <a href="agenda.php" class="pro-nav-item <?= $currentPage === 'agenda.php' ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📅</span>
                    <span>Agenda</span>
                </a>
                <a href="rdv.php" class="pro-nav-item <?= in_array($currentPage, $pagesRendezvous) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📋</span>
                    <span>Rendez-vous</span>
                </a>
                <a href="patients.php" class="pro-nav-item <?= in_array($currentPage, $pagesPatients) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">👥</span>
                    <span>Patients</span>
                </a>
                <a href="disponibilites.php" class="pro-nav-item <?= in_array($currentPage, $pagesDisponibilites) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⏰</span>
                    <span>Disponibilités</span>
                </a>
                <a href="parametres.php" class="pro-nav-item <?= in_array($currentPage, $pagesParametres) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⚙️</span>
                    <span>Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- ========== CONTENU PRINCIPAL ========== -->
        <main class="pro-main">
            <div class="agenda-page-header">
                <div class="agenda-title-section">
                    <h1>Agenda</h1>
                </div>
                <div class="agenda-actions">
                    <a href="modifier_disponibilites.php" class="btn-action-secondary">Modifier mes disponibilités</a>
                    <a href="nouvelle_disponibilite.php" class="btn-action-primary">Nouvelle disponibilité</a>
                </div>
            </div>

            <div class="agenda-container">
                <div class="agenda-card">
                    <div class="agenda-header">
                        <div class="agenda-navigation">
                            <a href="?semaine=<?= $semaine_offset - 1 ?>" class="nav-btn">←</a>
                            <a href="?semaine=0" class="nav-btn nav-btn-today">Aujourd'hui</a>
                            <a href="?semaine=<?= $semaine_offset + 1 ?>" class="nav-btn">→</a>
                        </div>
                        <div class="semaine-info">
                            <span class="semaine-titre">Semaine du <?= $debut_semaine->format('d/m/Y') ?> au <?= $fin_semaine->format('d/m/Y') ?></span>
                        </div>
                        <div class="legende">
                            <div class="legende-item">
                                <div class="legende-couleur legende-rdv"></div>
                                <span>Rendez-vous</span>
                            </div>
                            <div class="legende-item">
                                <div class="legende-couleur legende-dispo"></div>
                                <span>Disponibilités</span>
                            </div>
                        </div>
                    </div>

                    <div class="agenda-grid" id="agendaGrid">
                        <!-- Généré par JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>

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

    <script>
        // Données injectées depuis PHP
        window.rendezvous = <?= $rdv_json ?>;
        window.disponibilites = <?= $dispo_json ?>;
        window.debutSemaine = '<?= $debut_semaine->format('Y-m-d') ?>';
    </script>
    <script type="module" src="../assets/js/agenda.js"></script>
</body>
</html>