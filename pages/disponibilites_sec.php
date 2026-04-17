<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = ['disponibilites_sec.php', 'nouvelle_disponibilite_sec.php', 'modifier_disponibilites_sec.php', 'edit_disponibilite_sec.php', 'delete_disponibilite.php'];
$pagesPatients = ['patients_sec.php', 'consultation_sec.php'];
$pagesRendezvous = ['rdv_sec.php', 'desistement_sec.php'];
$pagesStats = ['professional_sec.php', 'statistiques_sec.php'];
$pagesParametres = ['parametres_sec.php'];

$_SESSION['user_id'] = 2;
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretaire') {
    header('Location: index.php');
    exit;
}

/* Infos praticien */
$stmt = mysqli_prepare(
    $connexion,
    "SELECT u.prenom, u.nom
     FROM utilisateurs u
     JOIN praticiens p ON p.utilisateur_id = u.id
     WHERE u.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$praticien = mysqli_fetch_assoc($res);

/* Récupérer les spécialités */
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes disponibilités</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/disponibilites.css">
</head>
<body>

<!-- ========== HEADER ========== -->
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

<!-- ========== LAYOUT PRO ========== -->
<div class="pro-layout">

    <!-- SIDEBAR -->
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

    <!-- CONTENU PRINCIPAL -->
    <main class="pro-main disponibilites-choice">

        <h1>⏰ Gérer mes disponibilités</h1>

        <div class="dispo-actions">
            <a href="nouvelle_disponibilite_sec.php" class="btn-dispo btn-add">
                ➕ Ajouter une disponibilité
            </a>

            <a href="modifier_disponibilites_sec.php" class="btn-dispo btn-edit">
                ✏️ Modifier mes disponibilités
            </a>
        </div>

    </main>
</div>

<!-- ========== FOOTER ========== -->
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>Doctomatique</h3>
            <p>La plateforme de santé qui simplifie votre quotidien</p>
        </div>

        <div class="footer-section">
            <h4>Patients</h4>
            <a href="#">Trouver un praticien</a>
            <a href="#">Aide et contact</a>
            <a href="#">FAQ</a>
        </div>

        <div class="footer-section">
            <h4>Professionnels</h4>
            <a href="professional.php">Espace professionnel</a>
            <a href="#">Solutions et Tarifs</a>
        </div>

        <div class="footer-section">
            <h4>Informations</h4>
            <a href="#">À propos</a>
            <a href="#">Mentions légales</a>
            <a href="#">Confidentialité</a>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2025 Doctomatique. Tous droits réservés.</p>
    </div>
</footer>

</body>
</html>
