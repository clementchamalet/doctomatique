<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$user_id = $_SESSION['user_id'];
$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites.php', 'nouvelle_disponibilite.php', 'modifier_disponibilites.php', 'edit_disponibilite.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients.php', 'consultation.php'];
$pagesRendezvous = ['rdv.php', 'desistement.php'];
$pagesStats = ['professional.php', 'statistiques.php'];
$pagesParametres = ['parametres.php'];

// Vérifier dans la table structures_sante que la secrétaire existe bien
$stmt = mysqli_prepare($connexion, "SELECT 1 FROM structures_sante WHERE secretaire_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Si ce n'est pas le cas alors l'utilisateur n'est pas une secrétaire
if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}

// Récupérer les infos de la secrétaire et sa structure
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

// Récupérer le structure_id
$structure_id = $secretaire['structure_id'];

// ========== CALCUL DES STATISTIQUES DE L'ÉTABLISSEMENT ==========

$stats = [];

// 1. Rendez-vous aujourd'hui pour l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE r.structure_id = ?
     AND DATE(c.debut) = CURDATE()"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_aujourdhui'] = mysqli_fetch_assoc($result)['nb'];

// Créneaux disponibles aujourd'hui pour l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM creneaux_disponibilite c
     JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
     WHERE ps.structure_id = ?
     AND DATE(c.debut) = CURDATE()
     AND c.est_reserve = 0"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['creneaux_aujourdhui'] = mysqli_fetch_assoc($result)['nb'];

// 2. RDV confirmés cette semaine
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE r.structure_id = ?
     AND WEEK(c.debut) = WEEK(CURDATE())
     AND YEAR(c.debut) = YEAR(CURDATE())
     AND s.description = 'Confirmé'"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes'] = mysqli_fetch_assoc($result)['nb'];

// RDV confirmés semaine dernière (pour le comparatif)
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(*) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN statut s ON r.statut = s.id
     WHERE r.structure_id = ?
     AND WEEK(c.debut) = WEEK(DATE_SUB(CURDATE(), INTERVAL 7 DAY))
     AND YEAR(c.debut) = YEAR(CURDATE())
     AND s.description = 'Confirmé'"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['rdv_confirmes_semaine_derniere'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_confirmes'] = $stats['rdv_confirmes'] - $stats['rdv_confirmes_semaine_derniere'];

// 3. Nombre total de patients de l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     WHERE r.structure_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['total_patients'] = mysqli_fetch_assoc($result)['nb'];

// Patients ce mois
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE r.structure_id = ?
     AND MONTH(c.debut) = MONTH(CURDATE())
     AND YEAR(c.debut) = YEAR(CURDATE())"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['patients_ce_mois'] = mysqli_fetch_assoc($result)['nb'];

// Patients mois dernier
$stmt = mysqli_prepare(
    $connexion,
    "SELECT COUNT(DISTINCT r.patient_id) as nb
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     WHERE r.structure_id = ?
     AND MONTH(c.debut) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
     AND YEAR(c.debut) = YEAR(CURDATE())"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats['patients_mois_dernier'] = mysqli_fetch_assoc($result)['nb'];
$stats['diff_patients'] = $stats['patients_ce_mois'] - $stats['patients_mois_dernier'];

// 4. Taux de remplissage ce mois
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        COUNT(DISTINCT c.id) as total_creneaux,
        COUNT(DISTINCT r.id) as creneaux_reserves
     FROM creneaux_disponibilite c
     JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id AND r.structure_id = ?
     WHERE ps.structure_id = ?
     AND MONTH(c.debut) = MONTH(CURDATE())
     AND YEAR(c.debut) = YEAR(CURDATE())"
);
mysqli_stmt_bind_param($stmt, "ii", $structure_id, $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$taux_data = mysqli_fetch_assoc($result);
$stats['taux_remplissage'] = $taux_data['total_creneaux'] > 0 
    ? round(($taux_data['creneaux_reserves'] / $taux_data['total_creneaux']) * 100) 
    : 0;

// Taux mois dernier
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        COUNT(DISTINCT c.id) as total_creneaux,
        COUNT(DISTINCT r.id) as creneaux_reserves
     FROM creneaux_disponibilite c
     JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id AND r.structure_id = ?
     WHERE ps.structure_id = ?
     AND MONTH(c.debut) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
     AND YEAR(c.debut) = YEAR(CURDATE())"
);
mysqli_stmt_bind_param($stmt, "ii", $structure_id, $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$taux_data_dernier = mysqli_fetch_assoc($result);
$stats['taux_remplissage_dernier'] = $taux_data_dernier['total_creneaux'] > 0 
    ? round(($taux_data_dernier['creneaux_reserves'] / $taux_data_dernier['total_creneaux']) * 100) 
    : 0;
$stats['diff_taux'] = $stats['taux_remplissage'] - $stats['taux_remplissage_dernier'];

// Prochains RDV (5 prochains) de l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        c.debut,
        c.duree_minute,
        u.prenom,
        u.nom,
        up.prenom as praticien_prenom,
        up.nom as praticien_nom,
        m.description as motif,
        s.description as statut
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN patients p ON r.patient_id = p.utilisateur_id
     JOIN utilisateurs u ON p.utilisateur_id = u.id
     JOIN praticiens pr ON c.praticien_id = pr.utilisateur_id
     JOIN utilisateurs up ON pr.utilisateur_id = up.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     WHERE r.structure_id = ?
     AND c.debut >= NOW()
     ORDER BY c.debut ASC
     LIMIT 5"
);
mysqli_stmt_bind_param($stmt, "i", $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$prochains_rdv = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = new DateTime($row['debut']);
    $row['date_format'] = $date->format('d/m/Y');
    $row['heure_format'] = $date->format('H:i');
    $prochains_rdv[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Professionnel - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
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
                <a href="../index.php">Espace Patient</a>
                <a href="auth.php" class="btn-connexion">Déconnexion</a>
            </div>
        </nav>
    </header>

    <!-- ========== LAYOUT PRINCIPAL ========== -->
    <div class="pro-layout">
        
        <!-- ========== SIDEBAR NAVIGATION ========== -->
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
                <a href="disponibilites_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesDisponibilites) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⏰</span>
                    <span>Disponibilités</span>
                </a>
                <a href="parametres_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesParametres) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⚙️</span>
                    <span>Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- ========== CONTENU PRINCIPAL ========== -->
        <main class="pro-main">
            <div class="section-header">
                <h1>Tableau de bord</h1>
                <div class="section-actions">
                    <a href="statistiques_sec.php" class="btn-action-secondary">Statistiques détaillées </a>
                    <a href="desistement.php" class="btn-submit">Signaler un désistement</a>
                </div>
            </div>

        <!-- Cartes statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Rendez-vous aujourd'hui</span>
                    <div class="stat-card-icon">
                        <img src="../assets/images/icone-calendar.png" alt="Calendrier">
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['rdv_aujourdhui'] ?></div>
                <div class="stat-card-subtitle">Sur <?= $stats['creneaux_aujourdhui'] ?> créneaux disponibles</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">RDV confirmés</span>
                    <div class="stat-card-icon">
                        <img src="../assets/images/icone-check.png" alt="Confirmé">
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['rdv_confirmes'] ?></div>
                <div class="stat-card-trend <?= $stats['diff_confirmes'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $stats['diff_confirmes'] >= 0 ? '↑' : '↓' ?> 
                    <?= $stats['diff_confirmes'] >= 0 ? '+' : '' ?><?= $stats['diff_confirmes'] ?> cette semaine
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Patients</span>
                    <div class="stat-card-icon">
                        <img src="../assets/images/icone-personne.png" alt="Patients">
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['total_patients'] ?></div>
                <div class="stat-card-trend <?= $stats['diff_patients'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $stats['diff_patients'] >= 0 ? '↑' : '↓' ?> 
                    <?= $stats['diff_patients'] >= 0 ? '+' : '' ?><?= $stats['diff_patients'] ?> ce mois
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Taux de remplissage</span>
                    <div class="stat-card-icon">
                        <img src="../assets/images/icone-stats.png" alt="Stats">
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['taux_remplissage'] ?>%</div>
                <div class="stat-card-trend <?= $stats['diff_taux'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $stats['diff_taux'] >= 0 ? '↑' : '↓' ?> 
                    <?= $stats['diff_taux'] >= 0 ? '+' : '' ?><?= $stats['diff_taux'] ?>% vs mois dernier
                </div>
            </div>
        </div>

            <!-- Prochains rendez-vous -->
            <div class="appointments-list" style="margin-top: 2rem;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--couleur-bordure);">
                    <h3>Prochains rendez-vous</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (count($prochains_rdv) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($prochains_rdv as $rdv): ?>
                                <div class="rdv-preview" onclick="window.location.href='consultation.php?rdv_id=<?= $rdv['id'] ?>'">
                                    <div class="rdv-preview-date">
                                        <div class="rdv-preview-day"><?= $rdv['date_format'] ?></div>
                                        <div class="rdv-preview-time"><?= $rdv['heure_format'] ?></div>
                                    </div>
                                    <div class="rdv-preview-info">
                                        <div class="rdv-preview-patient">
                                            <?= htmlspecialchars($rdv['prenom']) ?> <?= htmlspecialchars($rdv['nom']) ?>
                                        </div>
                                        <div class="rdv-preview-motif" style="font-size: 0.85rem; color: var(--couleur-texte-secondaire);">
                                            Dr. <?= htmlspecialchars($rdv['praticien_prenom']) ?> <?= htmlspecialchars($rdv['praticien_nom']) ?>
                                        </div>
                                        <?php if ($rdv['motif']): ?>
                                            <div class="rdv-preview-motif"><?= htmlspecialchars($rdv['motif']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rdv-preview-status">
                                        <span class="status-badge <?= strtolower(str_replace([' ', 'é'], ['', 'e'], $rdv['statut'] ?? 'pending')) ?>">
                                            <?= htmlspecialchars($rdv['statut'] ?? 'En attente') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--couleur-texte-secondaire); padding: 2rem;">
                            Aucun rendez-vous à venir
                        </p>
                    <?php endif; ?>
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
                <a href="search.php">Trouver un praticien</a>
                <a href="help.php">Aide et contact</a>
                <a href="#">FAQ</a>
            </div>
            
            <div class="footer-section">
                <h4>Professionnels</h4>
                <a href="professional.php">Espace professionnel</a>
                <a href="prices.php">Solutions et Tarifs</a>
            </div>
            
            <div class="footer-section">
                <h4>Informations</h4>
                <a href="about.php">A propos</a>
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