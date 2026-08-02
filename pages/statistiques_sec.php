<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites.php', 'nouvelle_disponibilite_sec.php', 'modifier_disponibilites_sec.php', 'edit_disponibilite_sec.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients_sec.php', 'consultation_sec.php'];
$pagesRendezvous = ['rdv_sec.php', 'desistement_sec.php'];
$pagesStats = ['professional_sec.php', 'statistiques_sec.php'];
$pagesParametres = ['parametres_sec.php'];

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretaire') {
    header('Location: index.php');
    exit;
}

// Get the secretary's info and their facility
$stmt = mysqli_prepare(
    $connexion,
    "SELECT u.prenom, u.nom, s.id as structure_id, s.nom as structure_nom
     FROM utilisateurs u
     JOIN structures_sante s ON s.secretaire_id = u.id
     WHERE u.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$secretaire = mysqli_fetch_assoc($result);

// If no facility found, redirect
if (!$secretaire) {
    header("Location: index.php");
    exit;
}

$structure_id = $secretaire['structure_id'];

// Get the list of practitioners at the facility
$stmt = mysqli_prepare(
    $connexion,
    "SELECT DISTINCT p.utilisateur_id, u.prenom, u.nom
     FROM praticiens p
     JOIN utilisateurs u ON p.utilisateur_id = u.id
     JOIN praticiens_structures ps ON ps.praticien_id = p.utilisateur_id
     WHERE ps.structure_id = ?
     ORDER BY u.nom, u.prenom"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$praticiens_liste = [];
while ($row = mysqli_fetch_assoc($result)) {
    $praticiens_liste[] = $row;
}

// Determine the selected practitioner (null = entire facility)
$praticien_selectionne = isset($_GET['praticien']) && $_GET['praticien'] !== '' ? intval($_GET['praticien']) : null;

// If a practitioner is selected, get their specialties
$specialites_praticien = [];
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT sm.nom
         FROM specialites_medecine sm
         JOIN praticiens_specialites ps ON ps.specialite_id = sm.id
         WHERE ps.praticien_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $praticien_selectionne);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $specialites_praticien[] = $row['nom'];
    }
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

// ========== ADAPTED STATISTICS ==========
// If praticien_selectionne is null: facility stats
// Otherwise: practitioner stats

// 1. Total appointments
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_total'] = mysqli_fetch_assoc($result)['nb'];

// Appointments for the previous period
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['prev_start'], $dates['prev_end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['prev_start'], $dates['prev_end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_total_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_rdv_total'] = $stats['rdv_total'] - $stats['rdv_total_prev'];

// 2. Confirmed appointments
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Confirmé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes'] = mysqli_fetch_assoc($result)['nb'];

// Confirmed for the previous period
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['prev_start'], $dates['prev_end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Confirmé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['prev_start'], $dates['prev_end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_confirmes'] = $stats['rdv_confirmes'] - $stats['rdv_confirmes_prev'];

// 3. Completed appointments
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Terminé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_termines'] = mysqli_fetch_assoc($result)['nb'];

// 4. Cancelled appointments
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Annulé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_annules'] = mysqli_fetch_assoc($result)['nb'];

// Cancelled for the previous period
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['prev_start'], $dates['prev_end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Annulé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['prev_start'], $dates['prev_end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_annules_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_annules'] = $stats['rdv_annules'] - $stats['rdv_annules_prev'];

// 5. Withdrawn appointments
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Désistement'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_desistement'] = mysqli_fetch_assoc($result)['nb'];

// 6. New patients
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['nouveaux_patients'] = mysqli_fetch_assoc($result)['nb'];

// New patients for the previous period
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['prev_start'], $dates['prev_end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['prev_start'], $dates['prev_end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['nouveaux_patients_prev'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_patients'] = $stats['nouveaux_patients'] - $stats['nouveaux_patients_prev'];

// 7. Total unique patients
if ($praticien_selectionne) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $praticien_selectionne);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT COUNT(DISTINCT r.patient_id) as nb
         FROM rendezvous r
         WHERE r.structure_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $structure_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['total_patients'] = mysqli_fetch_assoc($result)['nb'];

// 8. Fill rate
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT 
            COUNT(DISTINCT c.id) as total_creneaux,
            COUNT(DISTINCT r.id) as creneaux_reserves
         FROM creneaux_disponibilite c
         JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
         LEFT JOIN rendezvous r ON r.creneaux_id = c.id AND r.structure_id = ?
         WHERE ps.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iiss", $structure_id, $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$taux_data = mysqli_fetch_assoc($result);
$stats['taux_remplissage'] = $taux_data['total_creneaux'] > 0 
    ? round(($taux_data['creneaux_reserves'] / $taux_data['total_creneaux']) * 100) 
    : 0;
$stats['total_creneaux'] = $taux_data['total_creneaux'];
$stats['creneaux_reserves'] = $taux_data['creneaux_reserves'];

// Rate for the previous period
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['prev_start'], $dates['prev_end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT 
            COUNT(DISTINCT c.id) as total_creneaux,
            COUNT(DISTINCT r.id) as creneaux_reserves
         FROM creneaux_disponibilite c
         JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
         LEFT JOIN rendezvous r ON r.creneaux_id = c.id AND r.structure_id = ?
         WHERE ps.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, "iiss", $structure_id, $structure_id, $dates['prev_start'], $dates['prev_end']);
}
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
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT AVG(c.duree_minute) as moyenne
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN statut s ON r.statut = s.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         AND s.description = 'Terminé'"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['duree_moyenne'] = round(mysqli_fetch_assoc($result)['moyenne'] ?? 0);

// 12. Top 3 consultation reasons
if ($praticien_selectionne) {
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
    mysqli_stmt_bind_param($stmt, "iss", $praticien_selectionne, $dates['start'], $dates['end']);
} else {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT m.description, COUNT(*) as nb
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN motif m ON r.motif = m.id
         WHERE r.structure_id = ?
         AND DATE(c.debut) BETWEEN ? AND ?
         GROUP BY m.description
         ORDER BY nb DESC
         LIMIT 3"
    );
    mysqli_stmt_bind_param($stmt, "iss", $structure_id, $dates['start'], $dates['end']);
}
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
            setlocale(LC_TIME, 'fr_FR.UTF-8');
            return ucfirst(strftime('%B %Y', strtotime($dates['start'])));
        case 'annee':
            return "Année " . date('Y', strtotime($dates['start']));
    }
}

// Get the selected practitioner's name
$nom_praticien = null;
if ($praticien_selectionne) {
    foreach ($praticiens_liste as $prat) {
        if ($prat['utilisateur_id'] == $praticien_selectionne) {
            $nom_praticien = "Dr. " . $prat['prenom'] . " " . $prat['nom'];
            break;
        }
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
    <link rel="stylesheet" href="../assets/css/style_sec.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            <div class="nav-links">
                <a href="../index.php">Espace Patient</a>
                <a href="auth.php" class="btn-connexion">Déconnexion</a>
            </div>
        </nav>
    </header>

    <div class="pro-layout">
        <aside class="pro-sidebar">
            <div class="pro-sidebar-header">
                <h2><?= htmlspecialchars($secretaire['prenom']) ?> <?= htmlspecialchars($secretaire['nom']) ?></h2>
                <p><?= htmlspecialchars($secretaire['structure_nom']) ?></p>
            </div>
            <nav class="pro-nav">
                <a href="professional_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesStats) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📊</span>
                    <span>Tableau de bord</span>
                </a>
                <a href="rdv_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesRendezvous) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📋</span>
                    <span>Rendez-vous</span>
                </a>
                <a href="patients_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesPatients) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">👥</span>
                    <span>Patients</span>
                </a>
                <a href="disponibilites.php" class="pro-nav-item <?= in_array($currentPage, $pagesDisponibilites) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⏰</span>
                    <span>Disponibilités</span>
                </a>
                <a href="parametres_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesParametres) ? 'active' : '' ?>">
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
                    <a href="professional_sec.php" class="btn-secondary">← Retour</a>
                </div>
            </div>

            <!-- Practitioner selector -->
            <div class="praticien-selector">
                <label for="praticien-select">📋 Afficher les statistiques pour :</label>
                <select id="praticien-select" onchange="this.form.submit()">
                    <option value="">Établissement complet (<?= htmlspecialchars($secretaire['structure_nom']) ?>)</option>
                    <?php foreach ($praticiens_liste as $prat): ?>
                        <option value="<?= $prat['utilisateur_id'] ?>" 
                                <?= $praticien_selectionne == $prat['utilisateur_id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($prat['prenom']) ?> <?= htmlspecialchars($prat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="periode-selector">
                <a href="?periode=semaine<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?>" 
                   class="periode-btn <?= $periode === 'semaine' ? 'active' : '' ?>">Semaine</a>
                <a href="?periode=mois<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?>" 
                   class="periode-btn <?= $periode === 'mois' ? 'active' : '' ?>">Mois</a>
                <a href="?periode=annee<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?>" 
                   class="periode-btn <?= $periode === 'annee' ? 'active' : '' ?>">Année</a>
            </div>

            <div class="stats-section">
                <div class="stat-card full">
                    <div class="stat-card-header">
                        <span class="stat-card-title">
                            Vue d'ensemble de la période
                            <?php if ($nom_praticien): ?>
                                - <?= htmlspecialchars($nom_praticien) ?>
                                <?php if (!empty($specialites_praticien)): ?>
                                    <small style="font-weight: normal; color: var(--couleur-texte-secondaire);">
                                        (<?= htmlspecialchars(implode(', ', $specialites_praticien)) ?>)
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
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
    </footer>

    <script>
        // Auto-submit the form when the practitioner changes
        document.getElementById('praticien-select').addEventListener('change', function() {
            const praticienId = this.value;
            const periode = '<?= $periode ?>';
            const url = new URL(window.location);
            
            if (praticienId) {
                url.searchParams.set('praticien', praticienId);
            } else {
                url.searchParams.delete('praticien');
            }
            url.searchParams.set('periode', periode);
            
            window.location.href = url.toString();
        });
    </script>
</body>
</html>
