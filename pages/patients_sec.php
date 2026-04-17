<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$user_id = $_SESSION['user_id'];

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites.php', 'nouvelle_disponibilite_sec.php', 'modifier_disponibilites_sec.php', 'edit_disponibilite_sec.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients_sec.php', 'consultation_sec.php'];
$pagesRendezvous = ['rdv_sec.php', 'desistement_sec.php'];
$pagesStats = ['professional_sec.php', 'statistiques_sec.php'];
$pagesParametres = ['parametres_sec.php'];

$_SESSION['user_id'] = 2;
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretaire') {
    header('Location: index.php');
    exit;
}

// Récupérer les infos de la secrétaire et de sa structure
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

// Si pas de structure trouvée, rediriger
if (!$secretaire) {
    header("Location: index.php");
    exit;
}

$structure_id = $secretaire['structure_id'];

// Récupérer la liste des praticiens de l'établissement
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

// Déterminer le praticien sélectionné (null = établissement entier)
$praticien_selectionne = isset($_GET['praticien']) && $_GET['praticien'] !== '' ? intval($_GET['praticien']) : null;

// Filtres
$recherche = isset($_GET['search']) ? trim($_GET['search']) : '';
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'nom';

// Construire la requête SQL pour récupérer les patients
if ($praticien_selectionne) {
    // Patients d'un praticien spécifique
    $sql = "SELECT DISTINCT
                u.id,
                u.prenom,
                u.nom,
                u.telephone,
                u.email,
                p.date_naissance,
                g.description as genre,
                COUNT(DISTINCT r.id) as nb_rdv,
                MAX(c.debut) as dernier_rdv
            FROM utilisateurs u
            JOIN patients p ON p.utilisateur_id = u.id
            LEFT JOIN genre g ON p.genre = g.id
            LEFT JOIN rendezvous r ON r.patient_id = p.utilisateur_id
            LEFT JOIN creneaux_disponibilite c ON r.creneaux_id = c.id AND c.praticien_id = ?
            WHERE EXISTS (
                SELECT 1 FROM rendezvous r2
                JOIN creneaux_disponibilite c2 ON r2.creneaux_id = c2.id
                WHERE r2.patient_id = p.utilisateur_id
                AND c2.praticien_id = ?
            )";
    
    $params = [$praticien_selectionne, $praticien_selectionne];
    $types = "ii";
} else {
    // Patients de l'établissement
    $sql = "SELECT DISTINCT
                u.id,
                u.prenom,
                u.nom,
                u.telephone,
                u.email,
                p.date_naissance,
                g.description as genre,
                COUNT(DISTINCT r.id) as nb_rdv,
                MAX(c.debut) as dernier_rdv
            FROM utilisateurs u
            JOIN patients p ON p.utilisateur_id = u.id
            LEFT JOIN genre g ON p.genre = g.id
            LEFT JOIN rendezvous r ON r.patient_id = p.utilisateur_id AND r.structure_id = ?
            LEFT JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
            WHERE EXISTS (
                SELECT 1 FROM rendezvous r2
                WHERE r2.patient_id = p.utilisateur_id
                AND r2.structure_id = ?
            )";
    
    $params = [$structure_id, $structure_id];
    $types = "ii";
}

// Filtre de recherche
if ($recherche !== '') {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ?)";
    $recherche_param = "%$recherche%";
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $params[] = $recherche_param;
    $types .= "ssss";
}

$sql .= " GROUP BY u.id, u.prenom, u.nom, u.telephone, u.email, p.date_naissance, g.description";

// Tri
switch ($tri) {
    case 'nom':
        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";
        break;
    case 'recents':
        $sql .= " ORDER BY dernier_rdv DESC";
        break;
    case 'rdv':
        $sql .= " ORDER BY nb_rdv DESC";
        break;
}

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
        $row['age'] = $date_naissance->diff($aujourd_hui)->y;
    } else {
        $row['age'] = null;
    }
    
    // Formater le dernier RDV
    if ($row['dernier_rdv']) {
        $dernier_rdv = new DateTime($row['dernier_rdv']);
        $row['dernier_rdv_format'] = $dernier_rdv->format('d/m/Y');
    } else {
        $row['dernier_rdv_format'] = 'Aucun';
    }
    
    $patients[] = $row;
}

// Obtenir le nom du praticien sélectionné
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
    <title>Patients - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/patients.css">
    <link rel="stylesheet" href="../assets/css/style_sec.css">
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
                <a href="professional.php" class="pro-nav-item <?= in_array($currentPage, $pagesStats) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📊</span>
                    <span>Tableau de bord</span>
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
                        <h1>Patients</h1>
                        <p class="patients-count">
                            <?= count($patients) ?> patient<?= count($patients) > 1 ? 's' : '' ?>
                            <?php if ($nom_praticien): ?>
                                - <?= htmlspecialchars($nom_praticien) ?>
                            <?php else: ?>
                                - Établissement complet
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sélecteur de praticien -->
            <div class="praticien-selector">
                <label for="praticien-select">👨‍⚕️ Afficher les patients de :</label>
                <select id="praticien-select">
                    <option value="">Tous les praticiens (<?= htmlspecialchars($secretaire['structure_nom']) ?>)</option>
                    <?php foreach ($praticiens_liste as $prat): ?>
                        <option value="<?= $prat['utilisateur_id'] ?>" 
                                <?= $praticien_selectionne == $prat['utilisateur_id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($prat['prenom']) ?> <?= htmlspecialchars($prat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Barre de recherche et filtres -->
            <div class="patients-filters">
                <form method="GET" action="patients.php" class="search-form">
                    <?php if ($praticien_selectionne): ?>
                        <input type="hidden" name="praticien" value="<?= $praticien_selectionne ?>">
                    <?php endif; ?>
                    <input type="hidden" name="tri" value="<?= htmlspecialchars($tri) ?>">
                    
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Rechercher un patient (nom, prénom, email, téléphone)..."
                            value="<?= htmlspecialchars($recherche) ?>"
                        >
                        <?php if ($recherche !== ''): ?>
                            <a href="patients.php<?= $praticien_selectionne ? '?praticien='.$praticien_selectionne : '' ?><?= $tri !== 'nom' ? ($praticien_selectionne ? '&' : '?').'tri='.$tri : '' ?>" class="clear-search" title="Effacer">✕</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="sort-options">
                    <span class="sort-label">Trier par :</span>
                    <a href="?tri=nom<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?><?= $recherche ? '&search='.urlencode($recherche) : '' ?>" 
                       class="sort-option <?= $tri === 'nom' ? 'active' : '' ?>">
                        Nom
                    </a>
                    <a href="?tri=recents<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?><?= $recherche ? '&search='.urlencode($recherche) : '' ?>" 
                       class="sort-option <?= $tri === 'recents' ? 'active' : '' ?>">
                        Plus récents
                    </a>
                    <a href="?tri=rdv<?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?><?= $recherche ? '&search='.urlencode($recherche) : '' ?>" 
                       class="sort-option <?= $tri === 'rdv' ? 'active' : '' ?>">
                        Nombre de RDV
                    </a>
                </div>
            </div>

            <!-- Liste des patients -->
            <div class="patients-container">
                <?php if (count($patients) > 0): ?>
                    <div class="patients-grid">
                        <?php foreach ($patients as $patient): ?>
                            <div class="patient-card" onclick="window.location.href='consultation_sec.php?patient_id=<?= $patient['id'] ?><?= $praticien_selectionne ? '&praticien='.$praticien_selectionne : '' ?>'">
                                <div class="patient-card-header">
                                    <div class="patient-avatar">
                                        <span class="avatar-initials">
                                            <?= strtoupper(substr($patient['prenom'], 0, 1)) ?><?= strtoupper(substr($patient['nom'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div class="patient-info">
                                        <h3 class="patient-name">
                                            <?= htmlspecialchars($patient['prenom']) ?> <?= htmlspecialchars($patient['nom']) ?>
                                        </h3>
                                        <div class="patient-details">
                                            <?php if ($patient['age']): ?>
                                                <span class="patient-detail">
                                                    <span class="detail-icon">🎂</span>
                                                    <?= $patient['age'] ?> ans
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($patient['genre']): ?>
                                                <span class="patient-detail">
                                                    <span class="detail-icon">👤</span>
                                                    <?= htmlspecialchars($patient['genre']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="patient-card-body">
                                    <?php if ($patient['email']): ?>
                                        <div class="patient-contact-item">
                                            <span class="contact-icon">📧</span>
                                            <span class="contact-value"><?= htmlspecialchars($patient['email']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($patient['telephone']): ?>
                                        <div class="patient-contact-item">
                                            <span class="contact-icon">📞</span>
                                            <span class="contact-value"><?= htmlspecialchars($patient['telephone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="patient-card-footer">
                                    <div class="patient-stat">
                                        <span class="stat-icon">📋</span>
                                        <span class="stat-value"><?= $patient['nb_rdv'] ?></span>
                                        <span class="stat-label">RDV</span>
                                    </div>
                                    <div class="patient-stat">
                                        <span class="stat-icon">📅</span>
                                        <span class="stat-value"><?= $patient['dernier_rdv_format'] ?></span>
                                        <span class="stat-label">Dernier RDV</span>
                                    </div>
                                </div>

                                <div class="patient-card-action">
                                    <span class="action-text">Voir le dossier</span>
                                    <span class="action-arrow">→</span>
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
                                Aucun patient ne correspond à votre recherche<?= $nom_praticien ? ' pour ' . htmlspecialchars($nom_praticien) : '' ?>.
                                <br>
                                <a href="patients.php<?= $praticien_selectionne ? '?praticien='.$praticien_selectionne : '' ?>" class="empty-link">Voir tous les patients</a>
                            <?php else: ?>
                                Aucun patient enregistré<?= $nom_praticien ? ' pour ' . htmlspecialchars($nom_praticien) : '' ?>.
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

    <script>
        // Auto-submit lors du changement de praticien
        document.getElementById('praticien-select').addEventListener('change', function() {
            const praticienId = this.value;
            const url = new URL(window.location);
            
            if (praticienId) {
                url.searchParams.set('praticien', praticienId);
            } else {
                url.searchParams.delete('praticien');
            }
            
            window.location.href = url.toString();
        });
    </script>
</body>
</html>