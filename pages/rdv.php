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

// Filtres
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : 'tous';
$recherche = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête SQL
$sql = "SELECT 
            r.id,
            r.patient_id,
            c.debut,
            c.duree_minute,
            u.prenom,
            u.nom,
            u.telephone,
            m.description as motif,
            s.description as statut,
            s.id as statut_id,
            st.nom as structure
        FROM rendezvous r
        JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
        JOIN patients p ON r.patient_id = p.utilisateur_id
        JOIN utilisateurs u ON p.utilisateur_id = u.id
        LEFT JOIN motif m ON r.motif = m.id
        LEFT JOIN statut s ON r.statut = s.id
        LEFT JOIN structures_sante st ON r.structure_id = st.id
        WHERE c.praticien_id = ?";

$params = [$_SESSION['user_id']];
$types = "i";

// Filtre par statut
if ($filtre_statut !== 'tous') {
    $sql .= " AND s.description = ?";
    $params[] = $filtre_statut;
    $types .= "s";
}

// Filtre de recherche
if ($recherche !== '') {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR m.description LIKE ?)";
    $recherche_param = "%$recherche%";
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $types .= "sss";
}

$sql .= " ORDER BY c.debut DESC";

$stmt = mysqli_prepare($connexion, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_rdv = mysqli_stmt_get_result($stmt);

// Organiser les RDV par catégorie
$rdv_aujourd_hui = [];
$rdv_futurs = [];
$rdv_passes = [];

$aujourd_hui = new DateTime();
$aujourd_hui->setTime(0, 0, 0);
$fin_aujourd_hui = new DateTime();
$fin_aujourd_hui->setTime(23, 59, 59);

while ($row = mysqli_fetch_assoc($result_rdv)) {
    $date_rdv = new DateTime($row['debut']);
    
    // Formater les informations
    $row['date_format'] = $date_rdv->format('d/m/Y');
    $row['heure_format'] = $date_rdv->format('H:i');
    $row['jour_semaine'] = strftime('%A', $date_rdv->getTimestamp());
    
    // Déterminer si c'est aujourd'hui, futur ou passé
    if ($date_rdv >= $aujourd_hui && $date_rdv <= $fin_aujourd_hui) {
        $rdv_aujourd_hui[] = $row;
    } elseif ($date_rdv > $fin_aujourd_hui) {
        $rdv_futurs[] = $row;
    } else {
        $rdv_passes[] = $row;
    }
}

// Inverser l'ordre des RDV passés pour avoir les plus récents en premier
$rdv_passes = array_reverse($rdv_passes);

// Statistiques
$total_rdv = count($rdv_aujourd_hui) + count($rdv_futurs) + count($rdv_passes);
$total_aujourd_hui = count($rdv_aujourd_hui);
$total_futurs = count($rdv_futurs);
$total_passes = count($rdv_passes);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/rdv.css">
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
            <div class="rdv-page-header">
                <div class="rdv-title-section">
                    <span class="rdv-icon">📋</span>
                    <div>
                        <h1>Rendez-vous</h1>
                        <p class="rdv-count"><?= $total_rdv ?> rendez-vous</p>
                    </div>
                </div>
                <div class="rdv-actions">
                    <a href="desistement.php" class="btn-action-secondary">
                        <span class="btn-icon">➕</span> Signaler un désistement
                    </a>
                </div>
            </div>

            <!-- Stats rapides -->
            <div class="rdv-stats">
                <div class="rdv-stat-card rdv-stat-past">
                    <span class="stat-card-icon">✅</span>
                    <div>
                        <span class="stat-card-label">Passés</span>
                        <span class="stat-card-value"><?= count($rdv_passes) ?></span>
                    </div>
                </div>
                <div class="rdv-stat-card rdv-stat-today">
                    <span class="stat-card-icon">📅</span>
                    <div>
                        <span class="stat-card-label">Aujourd'hui</span>
                        <span class="stat-card-value"><?= count($rdv_aujourd_hui) ?></span>
                    </div>
                </div>
                <div class="rdv-stat-card rdv-stat-future">
                    <span class="stat-card-icon">🔜</span>
                    <div>
                        <span class="stat-card-label">À venir</span>
                        <span class="stat-card-value"><?= count($rdv_futurs) ?></span>
                    </div>
                </div>
            </div>

            <!-- Barre de filtres et recherche -->
            <div class="filters-container">
                <form method="GET" action="rdv.php" class="filters-form">
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Rechercher par patient ou motif..."
                            value="<?= htmlspecialchars($recherche) ?>"
                        >
                        <?php if ($recherche !== ''): ?>
                            <a href="rdv.php" class="clear-search" title="Effacer">✕</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="filter-chips">
                        <a href="?statut=tous<?= $recherche ? '&search=' . urlencode($recherche) : '' ?>" 
                           class="filter-chip <?= $filtre_statut === 'tous' ? 'active' : '' ?>">
                            Tous
                        </a>
                        <a href="?statut=Confirmé<?= $recherche ? '&search=' . urlencode($recherche) : '' ?>" 
                           class="filter-chip <?= $filtre_statut === 'Confirmé' ? 'active' : '' ?>">
                            Confirmés
                        </a>
                        <a href="?statut=Désistement<?= $recherche ? '&search=' . urlencode($recherche) : '' ?>" 
                           class="filter-chip <?= $filtre_statut === 'Désistement' ? 'active' : '' ?>">
                            Désistement
                        </a>
                        <a href="?statut=Annulé<?= $recherche ? '&search=' . urlencode($recherche) : '' ?>" 
                           class="filter-chip <?= $filtre_statut === 'Annulé' ? 'active' : '' ?>">
                            Annulés
                        </a>
                        <a href="?statut=Terminé<?= $recherche ? '&search=' . urlencode($recherche) : '' ?>" 
                           class="filter-chip <?= $filtre_statut === 'Terminé' ? 'active' : '' ?>">
                            Terminés
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des RDV -->
            <div class="rdv-container">
                <!-- RDV Aujourd'hui -->
                <?php if (count($rdv_aujourd_hui) > 0): ?>
                    <div class="rdv-section">
                        <h2 class="rdv-section-title">
                            <span class="section-icon">📅</span>
                            Aujourd'hui
                            <span class="section-count"><?= count($rdv_aujourd_hui) ?></span>
                        </h2>
                        <div class="rdv-list">
                            <?php foreach ($rdv_aujourd_hui as $rdv): 
                                // Déterminer la classe de statut
                                $statut_class = '';
                                switch (strtolower($rdv['statut'] ?? '')) {
                                    case 'confirmé':
                                        $statut_class = 'status-confirmed';
                                        break;
                                    case 'désistement':
                                        $statut_class = 'status-pending';
                                        break;
                                    case 'annulé':
                                        $statut_class = 'status-cancelled';
                                        break;
                                    case 'terminé':
                                        $statut_class = 'status-completed';
                                        break;
                                    default:
                                        $statut_class = 'status-default';
                                }
                            ?>
                                <div class="rdv-card" onclick="window.location.href='consultation.php?rdv_id=<?= $rdv['id'] ?>'">
                                    <div class="rdv-card-left">
                                        <div class="rdv-datetime">
                                            <div class="rdv-date"><?= $rdv['date_format'] ?></div>
                                            <div class="rdv-time"><?= $rdv['heure_format'] ?></div>
                                            <div class="rdv-duration"><?= $rdv['duree_minute'] ?> min</div>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-main">
                                        <div class="rdv-patient-info">
                                            <div class="rdv-patient-avatar">
                                                <span class="avatar-initials">
                                                    <?= strtoupper(substr($rdv['prenom'], 0, 1)) ?><?= strtoupper(substr($rdv['nom'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="rdv-patient-details">
                                                <h3 class="rdv-patient-name">
                                                    <?= htmlspecialchars($rdv['prenom']) ?> <?= htmlspecialchars($rdv['nom']) ?>
                                                </h3>
                                                <?php if ($rdv['telephone']): ?>
                                                    <div class="rdv-patient-contact">
                                                        <span class="contact-icon">📞</span>
                                                        <?= htmlspecialchars($rdv['telephone']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="rdv-details">
                                            <?php if ($rdv['motif']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">📝</span>
                                                    <span class="detail-label">Motif :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['motif']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($rdv['structure']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">🏥</span>
                                                    <span class="detail-label">Lieu :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['structure']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-right">
                                        <div class="rdv-status <?= $statut_class ?>">
                                            <?= htmlspecialchars($rdv['statut'] ?? 'Non défini') ?>
                                        </div>
                                        <div class="rdv-action">
                                            <span class="action-arrow">→</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- RDV Futurs -->
                <?php if (count($rdv_futurs) > 0): ?>
                    <div class="rdv-section">
                        <h2 class="rdv-section-title">
                            <span class="section-icon">🔜</span>
                            À venir
                            <span class="section-count"><?= count($rdv_futurs) ?></span>
                        </h2>
                        <div class="rdv-list">
                            <?php foreach ($rdv_futurs as $rdv): 
                                // Déterminer la classe de statut
                                $statut_class = '';
                                switch (strtolower($rdv['statut'] ?? '')) {
                                    case 'confirmé':
                                        $statut_class = 'status-confirmed';
                                        break;
                                    case 'désistement':
                                        $statut_class = 'status-pending';
                                        break;
                                    case 'annulé':
                                        $statut_class = 'status-cancelled';
                                        break;
                                    case 'terminé':
                                        $statut_class = 'status-completed';
                                        break;
                                    default:
                                        $statut_class = 'status-default';
                                }
                            ?>
                                <div class="rdv-card" onclick="window.location.href='consultation.php?rdv_id=<?= $rdv['id'] ?>'">
                                    <div class="rdv-card-left">
                                        <div class="rdv-datetime">
                                            <div class="rdv-date"><?= $rdv['date_format'] ?></div>
                                            <div class="rdv-time"><?= $rdv['heure_format'] ?></div>
                                            <div class="rdv-duration"><?= $rdv['duree_minute'] ?> min</div>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-main">
                                        <div class="rdv-patient-info">
                                            <div class="rdv-patient-avatar">
                                                <span class="avatar-initials">
                                                    <?= strtoupper(substr($rdv['prenom'], 0, 1)) ?><?= strtoupper(substr($rdv['nom'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="rdv-patient-details">
                                                <h3 class="rdv-patient-name">
                                                    <?= htmlspecialchars($rdv['prenom']) ?> <?= htmlspecialchars($rdv['nom']) ?>
                                                </h3>
                                                <?php if ($rdv['telephone']): ?>
                                                    <div class="rdv-patient-contact">
                                                        <span class="contact-icon">📞</span>
                                                        <?= htmlspecialchars($rdv['telephone']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="rdv-details">
                                            <?php if ($rdv['motif']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">📝</span>
                                                    <span class="detail-label">Motif :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['motif']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($rdv['structure']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">🏥</span>
                                                    <span class="detail-label">Lieu :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['structure']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-right">
                                        <div class="rdv-status <?= $statut_class ?>">
                                            <?= htmlspecialchars($rdv['statut'] ?? 'Non défini') ?>
                                        </div>
                                        <div class="rdv-action">
                                            <span class="action-arrow">→</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- RDV Passés -->
                <?php if (count($rdv_passes) > 0): ?>
                    <div class="rdv-section">
                        <h2 class="rdv-section-title">
                            <span class="section-icon">✅</span>
                            Passés
                            <span class="section-count"><?= count($rdv_passes) ?></span>
                        </h2>
                        <div class="rdv-list">
                            <?php foreach ($rdv_passes as $rdv): 
                                // Déterminer la classe de statut
                                $statut_class = '';
                                switch (strtolower($rdv['statut'] ?? '')) {
                                    case 'confirmé':
                                        $statut_class = 'status-confirmed';
                                        break;
                                    case 'désistement':
                                        $statut_class = 'status-pending';
                                        break;
                                    case 'annulé':
                                        $statut_class = 'status-cancelled';
                                        break;
                                    case 'terminé':
                                        $statut_class = 'status-completed';
                                        break;
                                    default:
                                        $statut_class = 'status-default';
                                }
                            ?>
                                <div class="rdv-card" onclick="window.location.href='consultation.php?rdv_id=<?= $rdv['id'] ?>'">
                                    <div class="rdv-card-left">
                                        <div class="rdv-datetime">
                                            <div class="rdv-date"><?= $rdv['date_format'] ?></div>
                                            <div class="rdv-time"><?= $rdv['heure_format'] ?></div>
                                            <div class="rdv-duration"><?= $rdv['duree_minute'] ?> min</div>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-main">
                                        <div class="rdv-patient-info">
                                            <div class="rdv-patient-avatar">
                                                <span class="avatar-initials">
                                                    <?= strtoupper(substr($rdv['prenom'], 0, 1)) ?><?= strtoupper(substr($rdv['nom'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="rdv-patient-details">
                                                <h3 class="rdv-patient-name">
                                                    <?= htmlspecialchars($rdv['prenom']) ?> <?= htmlspecialchars($rdv['nom']) ?>
                                                </h3>
                                                <?php if ($rdv['telephone']): ?>
                                                    <div class="rdv-patient-contact">
                                                        <span class="contact-icon">📞</span>
                                                        <?= htmlspecialchars($rdv['telephone']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="rdv-details">
                                            <?php if ($rdv['motif']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">📝</span>
                                                    <span class="detail-label">Motif :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['motif']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($rdv['structure']): ?>
                                                <div class="rdv-detail-item">
                                                    <span class="detail-icon">🏥</span>
                                                    <span class="detail-label">Lieu :</span>
                                                    <span class="detail-value"><?= htmlspecialchars($rdv['structure']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="rdv-card-right">
                                        <div class="rdv-status <?= $statut_class ?>">
                                            <?= htmlspecialchars($rdv['statut'] ?? 'Non défini') ?>
                                        </div>
                                        <div class="rdv-action">
                                            <span class="action-arrow">→</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Aucun RDV -->
                <?php if ($total_rdv === 0): ?>
                    <div class="rdv-empty">
                        <div class="empty-icon">📋</div>
                        <h3 class="empty-title">
                            <?php if ($recherche !== '' || $filtre_statut !== 'tous'): ?>
                                Aucun rendez-vous trouvé
                            <?php else: ?>
                                Aucun rendez-vous
                            <?php endif; ?>
                        </h3>
                        <p class="empty-text">
                            <?php if ($recherche !== '' || $filtre_statut !== 'tous'): ?>
                                Aucun rendez-vous ne correspond à vos critères de recherche.
                                <br>
                                <a href="rdv.php" class="empty-link">Voir tous les rendez-vous</a>
                            <?php else: ?>
                                Vous n'avez pas encore de rendez-vous programmés.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
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
</body>
</html>