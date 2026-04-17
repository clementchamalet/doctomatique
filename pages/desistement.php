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

$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rdv_id'])) {
    $rdv_id = intval($_POST['rdv_id']);
    
    // Vérifier que le RDV appartient bien au praticien
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT r.id 
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.id = ? AND c.praticien_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $rdv_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Vérifier si le statut "Désistement" existe, sinon le créer
        $stmt = mysqli_prepare($connexion, "SELECT id FROM statut WHERE description = 'Désistement'");
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $statut_desistement = mysqli_fetch_assoc($result);
        
        if (!$statut_desistement) {
            // Créer le statut "Désistement" s'il n'existe pas
            $stmt = mysqli_prepare($connexion, "INSERT INTO statut (description) VALUES ('Désistement')");
            mysqli_stmt_execute($stmt);
            $statut_id = mysqli_insert_id($connexion);
        } else {
            $statut_id = $statut_desistement['id'];
        }
        
        // Mettre à jour le statut du rendez-vous vers "Désistement"
        $stmt = mysqli_prepare(
            $connexion,
            "UPDATE rendezvous SET statut = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $statut_id, $rdv_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Le désistement a été enregistré avec succès. Le patient ne s'est pas présenté.";
            $message_type = "success";
        } else {
            $message = "Une erreur est survenue lors de l'enregistrement.";
            $message_type = "error";
        }
    } else {
        $message = "Rendez-vous introuvable.";
        $message_type = "error";
    }
}

// Paramètres de recherche
$recherche = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_recherche = isset($_GET['type']) ? $_GET['type'] : 'rdv';
$date_recherche = isset($_GET['date']) ? $_GET['date'] : '';
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

$resultats = [];

// Si on a un patient_id, afficher ses RDV (aujourd'hui + passés)
if ($patient_id > 0) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT 
            r.id,
            c.debut,
            c.duree_minute,
            u.prenom,
            u.nom,
            u.telephone,
            m.description as motif,
            s.description as statut
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN patients p ON r.patient_id = p.utilisateur_id
         JOIN utilisateurs u ON p.utilisateur_id = u.id
         LEFT JOIN motif m ON r.motif = m.id
         LEFT JOIN statut s ON r.statut = s.id
         WHERE c.praticien_id = ?
         AND p.utilisateur_id = ?
         AND c.debut <= NOW()
         AND s.description NOT IN ('Annulé', 'Désistement')
         ORDER BY c.debut DESC"
    );
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $date = new DateTime($row['debut']);
        $row['date_format'] = $date->format('d/m/Y');
        $row['heure_format'] = $date->format('H:i');
        $resultats[] = $row;
    }
    $type_recherche = 'rdv'; // Forcer l'affichage en mode RDV
}
// Recherche par date (aujourd'hui ou passée)
elseif ($date_recherche !== '' && $type_recherche === 'rdv') {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT 
            r.id,
            c.debut,
            c.duree_minute,
            u.prenom,
            u.nom,
            u.telephone,
            m.description as motif,
            s.description as statut
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         JOIN patients p ON r.patient_id = p.utilisateur_id
         JOIN utilisateurs u ON p.utilisateur_id = u.id
         LEFT JOIN motif m ON r.motif = m.id
         LEFT JOIN statut s ON r.statut = s.id
         WHERE c.praticien_id = ?
         AND DATE(c.debut) = ?
         AND s.description NOT IN ('Annulé', 'Désistement')
         ORDER BY c.debut ASC"
    );
    mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $date_recherche);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $date = new DateTime($row['debut']);
        $row['date_format'] = $date->format('d/m/Y');
        $row['heure_format'] = $date->format('H:i');
        $resultats[] = $row;
    }
}
// Recherche par texte (nom patient) - Aujourd'hui + passés
elseif ($recherche !== '') {
    if ($type_recherche === 'patient') {
        // Recherche par patient - Compte les RDV aujourd'hui + passés non désistés
        $stmt = mysqli_prepare(
            $connexion,
            "SELECT DISTINCT
                u.id as patient_id,
                u.prenom,
                u.nom,
                u.telephone,
                COUNT(DISTINCT r.id) as nb_rdv
             FROM utilisateurs u
             JOIN patients p ON p.utilisateur_id = u.id
             JOIN rendezvous r ON r.patient_id = p.utilisateur_id
             JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
             JOIN statut s ON r.statut = s.id
             WHERE c.praticien_id = ?
             AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.telephone LIKE ?)
             AND c.debut <= NOW()
             AND s.description NOT IN ('Annulé', 'Désistement')
             GROUP BY u.id, u.prenom, u.nom, u.telephone
             LIMIT 20"
        );
        $search_param = "%$recherche%";
        mysqli_stmt_bind_param($stmt, "isss", $_SESSION['user_id'], $search_param, $search_param, $search_param);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $resultats[] = $row;
        }
    } else {
        // Recherche par RDV (nom) - Aujourd'hui + passés
        $stmt = mysqli_prepare(
            $connexion,
            "SELECT 
                r.id,
                c.debut,
                c.duree_minute,
                u.prenom,
                u.nom,
                u.telephone,
                m.description as motif,
                s.description as statut
             FROM rendezvous r
             JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
             JOIN patients p ON r.patient_id = p.utilisateur_id
             JOIN utilisateurs u ON p.utilisateur_id = u.id
             LEFT JOIN motif m ON r.motif = m.id
             LEFT JOIN statut s ON r.statut = s.id
             WHERE c.praticien_id = ?
             AND (u.prenom LIKE ? OR u.nom LIKE ?)
             AND c.debut <= NOW()
             AND s.description NOT IN ('Annulé', 'Désistement')
             ORDER BY c.debut DESC
             LIMIT 20"
        );
        $search_param = "%$recherche%";
        mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $search_param, $search_param);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $date = new DateTime($row['debut']);
            $row['date_format'] = $date->format('d/m/Y');
            $row['heure_format'] = $date->format('H:i');
            $resultats[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signaler un désistement - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/desistement.css">
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
            <div class="desistement-header">
                <div class="desistement-title-section">
                    <span class="desistement-icon">⚠️</span>
                    <div>
                        <h1>Signaler un désistement</h1>
                        <p class="desistement-subtitle">Recherchez le rendez-vous ou le patient concerné</p>
                    </div>
                </div>
                <a href="professional.php" class="btn-action-secondary">
                    ← Retour
                </a>
            </div>

            <?php if ($message): ?>
                <div class="message-box message-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Sélection du type de recherche -->
            <div class="search-type-selector">
                <div class="search-type-tabs">
                    <a href="?type=rdv" 
                       class="search-type-tab <?= $type_recherche === 'rdv' && $patient_id === 0 ? 'active' : '' ?>">
                        📅 Par date/rendez-vous
                    </a>
                    <a href="?type=patient" 
                       class="search-type-tab <?= $type_recherche === 'patient' && $patient_id === 0 ? 'active' : '' ?>">
                        👤 Par patient
                    </a>
                </div>
            </div>

            <!-- Barre de recherche -->
            <div class="search-container">
                <?php if ($type_recherche === 'rdv' && $patient_id === 0): ?>
                    <!-- Recherche par date ou nom pour RDV -->
                    <div class="search-dual">
                        <form method="GET" action="desistement.php" class="date-search-form">
                            <input type="hidden" name="type" value="rdv">
                            <div class="date-input-group">
                                <label for="date">📅 Sélectionner une date :</label>
                                <input 
                                    type="date" 
                                    id="date"
                                    name="date" 
                                    class="date-input" 
                                    value="<?= htmlspecialchars($date_recherche) ?>"
                                    max="<?= date('Y-m-d') ?>"
                                >
                                <button type="submit" class="btn-search">Voir les RDV</button>
                            </div>
                        </form>
                        
                        <div class="search-divider">OU</div>
                        
                        <form method="GET" action="desistement.php" class="search-form">
                            <input type="hidden" name="type" value="rdv">
                            <div class="search-input-wrapper">
                                <span class="search-icon">🔍</span>
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="search-input" 
                                    placeholder="Rechercher par nom du patient..."
                                    value="<?= htmlspecialchars($recherche) ?>"
                                >
                                <?php if ($recherche !== ''): ?>
                                    <a href="desistement.php?type=rdv" class="clear-search">✕</a>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn-search">Rechercher</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Recherche par patient -->
                    <form method="GET" action="desistement.php" class="search-form">
                        <input type="hidden" name="type" value="patient">
                        <div class="search-input-wrapper">
                            <span class="search-icon">🔍</span>
                            <input 
                                type="text" 
                                name="search" 
                                class="search-input" 
                                placeholder="Nom, prénom ou téléphone du patient..."
                                value="<?= htmlspecialchars($recherche) ?>"
                                autofocus
                            >
                            <?php if ($recherche !== ''): ?>
                                <a href="desistement.php?type=patient" class="clear-search">✕</a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-search">Rechercher</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Résultats -->
            <div class="results-container">
                <?php if ($patient_id > 0 || $recherche !== '' || $date_recherche !== ''): ?>
                    <?php if (count($resultats) > 0): ?>
                        <div class="results-header">
                            <h2><?= count($resultats) ?> résultat<?= count($resultats) > 1 ? 's' : '' ?> trouvé<?= count($resultats) > 1 ? 's' : '' ?></h2>
                            <?php if ($patient_id > 0): ?>
                                <a href="desistement.php?type=patient&search=<?= urlencode($recherche) ?>" class="btn-back">
                                    ← Retour aux patients
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($type_recherche === 'patient' && $patient_id === 0): ?>
                            <!-- Résultats par patient -->
                            <div class="results-list">
                                <?php foreach ($resultats as $patient): ?>
                                    <div class="result-card patient-result">
                                        <div class="result-avatar">
                                            <?= strtoupper(substr($patient['prenom'], 0, 1)) ?><?= strtoupper(substr($patient['nom'], 0, 1)) ?>
                                        </div>
                                        <div class="result-info">
                                            <h3><?= htmlspecialchars($patient['prenom']) ?> <?= htmlspecialchars($patient['nom']) ?></h3>
                                            <p><?= htmlspecialchars($patient['telephone']) ?></p>
                                            <p class="result-meta"><?= $patient['nb_rdv'] ?> rendez-vous </p>
                                        </div>
                                        <a href="?type=rdv&patient_id=<?= $patient['patient_id'] ?>&search=<?= urlencode($patient['nom']) ?>" class="btn-view-rdv">
                                            Voir les RDV →
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Résultats par RDV -->
                            <div class="results-list">
                                <?php foreach ($resultats as $rdv): ?>
                                    <div class="result-card rdv-result">
                                        <div class="rdv-date-time">
                                            <div class="rdv-date"><?= $rdv['date_format'] ?></div>
                                            <div class="rdv-time"><?= $rdv['heure_format'] ?></div>
                                        </div>
                                        <div class="result-info">
                                            <h3><?= htmlspecialchars($rdv['prenom']) ?> <?= htmlspecialchars($rdv['nom']) ?></h3>
                                            <?php if ($rdv['motif']): ?>
                                                <p>📝 <?= htmlspecialchars($rdv['motif']) ?></p>
                                            <?php endif; ?>
                                            <p class="result-meta">
                                                <?= $rdv['duree_minute'] ?> min • 
                                                <span class="status-<?= strtolower(str_replace(' ', '-', $rdv['statut'])) ?>">
                                                    <?= htmlspecialchars($rdv['statut']) ?>
                                                </span>
                                            </p>
                                        </div>
                                        <form method="POST" class="desist-form" onsubmit="return confirm('Confirmer le désistement de ce rendez-vous ?')">
                                            <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                            <button type="submit" class="btn-desist">
                                                ⚠️ Signaler
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">🔍</div>
                            <h3>Aucun résultat trouvé</h3>
                            <p>
                                <?php if ($patient_id > 0): ?>
                                    Ce patient n'a pas de rendez-vous à venir.
                                <?php elseif ($date_recherche): ?>
                                    Aucun rendez-vous trouvé pour cette date.
                                <?php else: ?>
                                    Aucun <?= $type_recherche === 'patient' ? 'patient' : 'rendez-vous' ?> ne correspond à votre recherche.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <h3>Recherchez un rendez-vous</h3>
                        <p>
                            <?php if ($type_recherche === 'patient'): ?>
                                Saisissez le nom, prénom ou téléphone du patient pour retrouver ses rendez-vous.
                            <?php else: ?>
                                Sélectionnez une date ou saisissez le nom du patient pour retrouver ses rendez-vous.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
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