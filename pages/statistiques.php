<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites.php', 'nouvelle_disponibilite.php', 'modifier_disponibilites.php', 'edit_disponibilite.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients.php', 'consultation.php'];
$pagesRendezvous = ['rdv.php', 'desistement.php'];
$pagesStats = ['professional.php', 'statistiques.php'];
$pagesParametres = ['parametres.php'];

// Check the praticiens table
$stmt = mysqli_prepare($connexion,"SELECT 1 FROM praticiens WHERE utilisateur_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}

// Get the practitioner's info
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

// Get the specialties
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

// ========== SELECTED PERIOD ==========
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'semaine';

// ========== STATISTICS BY PERIOD ==========

// Function to get the dates for a given period
function getDatesForPeriod($periode) {
    $dates = [];
    switch($periode) {
        case 'semaine':
            $dates['start'] = date('Y-m-d', strtotime('monday this week'));
            $dates['end'] = date('Y-m-d', strtotime('sunday this week'));
            $dates['prev_start'] = date('Y-m-d', strtotime('monday last week'));
            $dates['prev_end'] = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'mois':
            $dates['start'] = date('Y-m-01');
            $dates['end'] = date('Y-m-t');
            $dates['prev_start'] = date('Y-m-01', strtotime('first day of last month'));
            $dates['prev_end'] = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'annee':
            $dates['start'] = date('Y-01-01');
            $dates['end'] = date('Y-12-31');
            $dates['prev_start'] = date('Y-01-01', strtotime('first day of january last year'));
            $dates['prev_end'] = date('Y-12-31', strtotime('last day of december last year'));
            break;
    }
    return $dates;
}

$dates = getDatesForPeriod($periode);
$stats = [];

// 1. Total appointments
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_total'] = mysqli_fetch_assoc($result)['nb'];

// Appointments for the previous period
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['prev_start'], $dates['prev_end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_total_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_rdv_total'] = $stats['rdv_total'] - $stats['rdv_total_prev'];

// 2. Confirmed appointments
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Confirmé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes'] = mysqli_fetch_assoc($result)['nb'];

// Confirmed for the previous period
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Confirmé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['prev_start'], $dates['prev_end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_confirmes'] = $stats['rdv_confirmes'] - $stats['rdv_confirmes_prev'];

// 3. Completed appointments
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Terminé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_termines'] = mysqli_fetch_assoc($result)['nb'];

// 4. Cancelled appointments
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Annulé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_annules'] = mysqli_fetch_assoc($result)['nb'];

// Cancelled for the previous period
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Annulé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['prev_start'], $dates['prev_end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_annules_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_annules'] = $stats['rdv_annules'] - $stats['rdv_annules_prev'];

// 5. Withdrawn appointments
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Désistement'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_attente'] = mysqli_fetch_assoc($result)['nb'];

// 6. New patients
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['nouveaux_patients'] = mysqli_fetch_assoc($result)['nb'];

// New patients for the previous period
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['prev_start'], $dates['prev_end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['nouveaux_patients_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_patients'] = $stats['nouveaux_patients'] - $stats['nouveaux_patients_prev'];

// 7. Total unique patients
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['total_patients'] = mysqli_fetch_assoc($result)['nb'];

// 8. Fill rate
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        COUNT(DISTINCT c.id) as total_creneaux,
        COUNT(DISTINCT r.id) as creneaux_reserves
     FROM creneaux_disponibilite c
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$taux_data = mysqli_fetch_assoc($result);
$stats['taux_remplissage'] = $taux_data['total_creneaux'] > 0 
    ? round(($taux_data['creneaux_reserves'] / $taux_data['total_creneaux']) * 100) 
    : 0;
$stats['total_creneaux'] = $taux_data['total_creneaux'];
$stats['creneaux_reserves'] = $taux_data['creneaux_reserves'];

// Rate for the previous period
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        COUNT(DISTINCT c.id) as total_creneaux,
        COUNT(DISTINCT r.id) as creneaux_reserves
     FROM creneaux_disponibilite c
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['prev_start'], $dates['prev_end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$taux_data_prev = mysqli_fetch_assoc($result);
$stats['taux_remplissage_prev'] = $taux_data_prev['total_creneaux'] > 0 
    ? round(($taux_data_prev['creneaux_reserves'] / $taux_data_prev['total_creneaux']) * 100) 
    : 0;
$stats['diff_taux'] = $stats['taux_remplissage'] - $stats['taux_remplissage_prev'];

// 9. Cancellation rate
$stats['taux_annulation'] = $stats['rdv_total'] > 0 
    ? round(($stats['rdv_annules'] / $stats['rdv_total']) * 100, 1) 
    : 0;

// 10. Estimated revenue
$tarif_consultation = 50;
$stats['revenus'] = $stats['rdv_termines'] * $tarif_consultation;
$stats['revenus_potentiels'] = $stats['rdv_confirmes'] * $tarif_consultation;

// 11. Average consultation duration
$stmt = mysqli_prepare(
    $connexion,
    "SELECT AVG(c.duree_minute) as moyenne
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     AND s.description = 'Terminé'"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['duree_moyenne'] = round(mysqli_fetch_assoc($result)['moyenne'] ?? 0);

// 12. Top 3 consultation reasons
$stmt = mysqli_prepare(
    $connexion,
    "SELECT m.description, COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN motif m ON r.motif = m.id
     WHERE c.praticien_id = ?
     AND DATE(c.debut) BETWEEN ? AND ?
     GROUP BY m.description
     ORDER BY nb DESC
     LIMIT 3"
);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $dates['start'], $dates['end']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['top_motifs'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['top_motifs'][] = $row;
}

// Function to format the period title
function getPeriodeTitle($periode, $dates) {
    switch($periode) {
        case 'semaine':
            return "Semaine du " . date('d/m', strtotime($dates['start'])) . " au " . date('d/m/Y', strtotime($dates['end']));
        case 'mois':
            return date('F Y', strtotime($dates['start']));
        case 'annee':
            return "Année " . date('Y', strtotime($dates['start']));
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/statistiques.css">
</head>
<body>
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

    <div class="pro-layout">
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

        <main class="pro-main">
            <div class="section-header">
                <div>
                    <h1>📈 Statistiques détaillées</h1>
                    <p class="periode-label"><?= getPeriodeTitle($periode, $dates) ?></p>
                </div>
                <div class="section-actions">
                    <a href="professional.php" class="btn-secondary">← Retour</a>
                </div>
            </div>

            <div class="periode-selector">
                <a href="?periode=semaine" class="periode-btn <?= $periode === 'semaine' ? 'active' : '' ?>">Semaine</a>
                <a href="?periode=mois" class="periode-btn <?= $periode === 'mois' ? 'active' : '' ?>">Mois</a>
                <a href="?periode=annee" class="periode-btn <?= $periode === 'annee' ? 'active' : '' ?>">Année</a>
            </div>

            <div class="stats-section">
                <div class="stat-card full">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Vue d'ensemble de la période</span>
                        <span class="stat-card-icon">📌</span>
                    </div>
                    <div style="display: flex; gap: 40px; align-items: center;">
                        <div class="stat-card-value" style="margin-bottom: 0; font-size: 2.5rem;">
                            <?= $stats['rdv_total'] ?> <small style="font-size: 1rem; color: var(--couleur-texte-leger);">RDV au total</small>
                        </div>
                        <div class="stat-card-subtitle">
                            <strong><?= $stats['rdv_confirmes'] ?></strong> confirmés · 
                            <strong><?= $stats['rdv_annules'] ?></strong> annulés · 
                            <strong><?= $stats['rdv_desistement'] ?></strong> désistement
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-section">
                <h3 class="stats-section-title">📅 Performances Rendez-vous</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Activité globale</span>
                        </div>
                        <div class="stat-card-value"><?= $stats['rdv_total'] ?></div>
                        <div class="stat-card-trend <?= $stats['diff_rdv_total'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= $stats['diff_rdv_total'] >= 0 ? '↑ +' : '↓ ' ?><?= $stats['diff_rdv_total'] ?> vs préc.
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Remplissage</span>
                        </div>
                        <div class="stat-card-value"><?= $stats['taux_remplissage'] ?>%</div>
                        <div class="stat-card-subtitle"><?= $stats['creneaux_reserves'] ?> / <?= $stats['total_creneaux'] ?> créneaux</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Revenus estimés</span>
                        </div>
                        <div class="stat-card-value"><?= number_format($stats['revenus'], 0, ',', ' ') ?> €</div>
                        <div class="stat-card-subtitle">Basé sur les RDV terminés</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Nouveaux Patients</span>
                        </div>
                        <div class="stat-card-value"><?= $stats['nouveaux_patients'] ?></div>
                        <div class="stat-card-trend <?= $stats['diff_patients'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= $stats['diff_patients'] >= 0 ? '↑ +' : '↓ ' ?><?= $stats['diff_patients'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card large">
                        <div class="stat-card-header">
                            <h3 class="stat-card-title">🩺 Top 3 Motifs de consultation</h3>
                        </div>
                        <div class="motifs-list" style="margin-top: 20px;">
                            <?php if (!empty($stats['top_motifs'])): ?>
                                <?php foreach ($stats['top_motifs'] as $index => $motif): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee;">
                                        <span><strong><?= $index + 1 ?>.</strong> <?= htmlspecialchars($motif['description']) ?></span>
                                        <span class="badge"><?= $motif['nb'] ?> RDV</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucune donnée disponible</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Annulations</span>
                        </div>
                        <div class="stat-card-value"><?= $stats['taux_annulation'] ?>%</div>
                        <div class="stat-card-subtitle"><?= $stats['rdv_annules'] ?> rdv annulés au total</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Durée Moyenne</span>
                        </div>
                        <div class="stat-card-value"><?= $stats['duree_moyenne'] ?> min</div>
                        <div class="stat-card-subtitle">Temps passé par patient</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

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