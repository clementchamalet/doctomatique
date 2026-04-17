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

// Récupérer le terme de recherche
$recherche = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête SQL pour récupérer les patients
$sql = "SELECT DISTINCT
            u.id,
            u.prenom,
            u.nom,
            u.email,
            u.telephone,
            p.date_naissance,
            p.genre,
            g.description as genre_libelle,
            COUNT(DISTINCT r.id) as nb_consultations,
            MAX(c.debut) as derniere_consultation,
            MIN(c.debut) as premiere_consultation
        FROM utilisateurs u
        JOIN patients p ON p.utilisateur_id = u.id
        JOIN rendezvous r ON r.patient_id = p.utilisateur_id
        JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
        LEFT JOIN genre g ON p.genre = g.id
        WHERE c.praticien_id = ?";

$params = [$_SESSION['user_id']];
$types = "i";

// Ajouter le filtre de recherche si présent
if ($recherche !== '') {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $recherche_param = "%$recherche%";
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $types .= "sss";
}

$sql .= " GROUP BY u.id, u.prenom, u.nom, u.email, u.telephone, p.date_naissance, p.genre, g.description
          ORDER BY derniere_consultation DESC";

$stmt = mysqli_prepare($connexion, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_patients = mysqli_stmt_get_result($stmt);

$patients = [];
while ($row = mysqli_fetch_assoc($result_patients)) {
    // Calculer l'âge
    if ($row['date_naissance']) {
        $date_naissance = new DateTime($row['date_naissance']);
        $aujourd_hui = new DateTime();
        $row['age'] = $aujourd_hui->diff($date_naissance)->y;
    } else {
        $row['age'] = null;
    }
    
    // Formater les dates
    if ($row['derniere_consultation']) {
        $date_derniere = new DateTime($row['derniere_consultation']);
        $row['derniere_consultation_format'] = $date_derniere->format('d/m/Y');
    }
    
    $patients[] = $row;
}

// Statistiques
$total_patients = count($patients);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Patients - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
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
            <div class="patients-page-header">
                <div class="patients-title-section">
                    <span class="patients-icon">👥</span>
                    <div>
                        <h1>Mes patients</h1>
                        <p class="patients-count"><?= $total_patients ?> patient<?= $total_patients > 1 ? 's' : '' ?></p>
                    </div>
                </div>
            </div>

            <!-- Barre de recherche -->
             <div class="patients-container">
            <div class="search-bar-container">
                <form method="GET" action="patients.php" class="search-form">
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Rechercher un patient par nom, prénom ou email..."
                            value="<?= htmlspecialchars($recherche) ?>"
                        >
                        <?php if ($recherche !== ''): ?>
                            <a href="patients.php" class="clear-search" title="Effacer la recherche">✕</a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-search">Rechercher</button>
                </form>
                
                <?php if ($recherche !== ''): ?>
                    <div class="search-results-info">
                        <?= $total_patients ?> résultat<?= $total_patients > 1 ? 's' : '' ?> pour "<?= htmlspecialchars($recherche) ?>"
                    </div>
                <?php endif; ?>
            </div>

            <!-- Liste des patients -->
            
                <?php if (count($patients) > 0): ?>
                    <div class="patients-grid">
                        <?php foreach ($patients as $patient): ?>
                            <div class="patient-card" onclick="window.location.href='consultation.php?patient_id=<?= $patient['id'] ?>'">
                                <div class="patient-card-header">
                                    <div class="patient-avatar-wrapper">
                                        <div class="patient-avatar">
                                            <span class="avatar-initials">
                                                <?= strtoupper(substr($patient['prenom'], 0, 1)) ?><?= strtoupper(substr($patient['nom'], 0, 1)) ?>
                                            </span>
                                        </div>
                                        <?php if ($patient['nb_consultations'] >= 5): ?>
                                            <div class="patient-badge patient-badge-regular" title="Patient régulier">⭐</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="patient-card-info">
                                        <h3 class="patient-card-name">
                                            <?= htmlspecialchars($patient['prenom']) ?> <?= htmlspecialchars($patient['nom']) ?>
                                        </h3>
                                        <div class="patient-card-meta">
                                            <?php if ($patient['age']): ?>
                                                <span class="patient-meta-item">
                                                    <span class="meta-icon">🎂</span>
                                                    <?= $patient['age'] ?> ans
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($patient['genre_libelle']): ?>
                                                <span class="patient-meta-item">
                                                    <span class="meta-icon">👤</span>
                                                    <?= htmlspecialchars($patient['genre_libelle']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="patient-card-contact">
                                            <?php if ($patient['telephone']): ?>
                                                <span class="contact-item">
                                                    <span class="contact-icon">📞</span>
                                                    <?= htmlspecialchars($patient['telephone']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="contact-item">
                                                <span class="contact-icon">✉️</span>
                                                <?= htmlspecialchars($patient['email']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="patient-card-footer">
                                    <div class="patient-stat">
                                        <span class="stat-label">Consultations</span>
                                        <span class="stat-value"><?= $patient['nb_consultations'] ?></span>
                                    </div>
                                    <div class="patient-stat">
                                        <span class="stat-label">Dernière visite</span>
                                        <span class="stat-value"><?= $patient['derniere_consultation_format'] ?></span>
                                    </div>
                                    <div class="patient-card-action">
                                        <span class="action-arrow">→</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="patients-empty">
                        <div class="empty-icon">👥</div>
                        <h3 class="empty-title">
                            <?php if ($recherche !== ''): ?>
                                Aucun patient trouvé
                            <?php else: ?>
                                Aucun patient
                            <?php endif; ?>
                        </h3>
                        <p class="empty-text">
                            <?php if ($recherche !== ''): ?>
                                Aucun patient ne correspond à votre recherche "<?= htmlspecialchars($recherche) ?>".
                                <br>
                                <a href="patients.php" class="empty-link">Voir tous les patients</a>
                            <?php else: ?>
                                Vous n'avez pas encore de patients ayant pris rendez-vous.
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