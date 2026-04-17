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

$dispoId = intval($_GET['id']);

/* 🔒 Charger la disponibilité (non réservée) */
$stmt = mysqli_prepare(
    $connexion,
    "SELECT c.id, c.debut, c.duree_minute
     FROM creneaux_disponibilite c
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.id = ?
     AND c.praticien_id = ?
     AND r.id IS NULL"
);

mysqli_stmt_bind_param($stmt, "ii", $dispoId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$dispo = mysqli_fetch_assoc($result);

if (!$dispo) {
    header('Location: modifier_disponibilites.php');
    exit;
}

$success = false;

/* 💾 Traitement du formulaire */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $duree = intval($_POST['duree']);

    $nouveauDebut = "$date $heure";

    $update = mysqli_prepare(
        $connexion,
        "UPDATE creneaux_disponibilite
         SET debut = ?, duree_minute = ?
         WHERE id = ?
         AND praticien_id = ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "siii",
        $nouveauDebut,
        $duree,
        $dispoId,
        $_SESSION['user_id']
    );

    mysqli_stmt_execute($update);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier disponibilité</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/modifier_disponibilites.css">
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
            <h1>✏️ Modifier la disponibilité</h1>

            <?php if ($success): ?>
                <p style="color: green;">Disponibilité mise à jour avec succès.</p>
                <a href="modifier_disponibilites.php">← Retour à la liste</a>
            <?php else: ?>

            <form method="POST" class="form-card">

                <label>Date</label>
                <input type="date" name="date"
                    value="<?= date('Y-m-d', strtotime($dispo['debut'])) ?>"
                    required>

                <label>Heure de début</label>
                <input type="time" name="heure"
                    value="<?= date('H:i', strtotime($dispo['debut'])) ?>"
                    required>

                <label>Durée</label>
                <select name="duree" required>
                    <?php foreach ([15, 30, 45, 60] as $min): ?>
                        <option value="<?= $min ?>"
                            <?= $dispo['duree_minute'] == $min ? 'selected' : '' ?>>
                            <?= $min ?> min
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-action-primary">
                    💾 Enregistrer les modifications
                </button>

            </form>

            <br>
            <a href="modifier_disponibilites.php">← Annuler</a>

            <?php endif; ?>
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
