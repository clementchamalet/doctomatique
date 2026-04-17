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
$dispoId = intval($_GET['id']);
$praticien_param = isset($_GET['praticien']) ? intval($_GET['praticien']) : null;

// Charger la disponibilité (non réservée) et vérifier qu'elle appartient à un praticien de l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT c.id, c.debut, c.duree_minute, c.praticien_id, u.prenom, u.nom
     FROM creneaux_disponibilite c
     JOIN praticiens_structures ps ON c.praticien_id = ps.praticien_id
     JOIN utilisateurs u ON c.praticien_id = u.id
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.id = ?
     AND ps.structure_id = ?
     AND r.id IS NULL"
);

mysqli_stmt_bind_param($stmt, "ii", $dispoId, $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$dispo = mysqli_fetch_assoc($result);

if (!$dispo) {
    header('Location: modifier_disponibilites_sec.php' . ($praticien_param ? '?praticien='.$praticien_param : ''));
    exit;
}

$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $duree = intval($_POST['duree']);

    $nouveauDebut = "$date $heure";

    $update = mysqli_prepare(
        $connexion,
        "UPDATE creneaux_disponibilite
         SET debut = ?, duree_minute = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "sii",
        $nouveauDebut,
        $duree,
        $dispoId
    );

    mysqli_stmt_execute($update);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier disponibilité - Doctomatique</title>
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
            <h1>✏️ Modifier la disponibilité
                <?php if ($dispo): ?>
                    - Dr. <?= htmlspecialchars($dispo['prenom']) ?> <?= htmlspecialchars($dispo['nom']) ?>
                <?php endif; ?>
            </h1>

            <?php if ($success): ?>
                <p style="color: green;">Disponibilité mise à jour avec succès.</p>
                <a href="modifier_disponibilites_sec.php<?= $praticien_param ? '?praticien='.$praticien_param : '' ?>">← Retour à la liste</a>
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
            <a href="modifier_disponibilites_sec.php<?= $praticien_param ? '?praticien='.$praticien_param : '' ?>">← Annuler</a>

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