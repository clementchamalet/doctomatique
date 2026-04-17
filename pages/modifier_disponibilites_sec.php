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

// Déterminer le praticien sélectionné
$praticien_selectionne = isset($_GET['praticien']) && $_GET['praticien'] !== '' ? intval($_GET['praticien']) : null;

// Si aucun praticien sélectionné et qu'il y en a dans la liste, sélectionner le premier
if ($praticien_selectionne === null && !empty($praticiens_liste)) {
    $praticien_selectionne = $praticiens_liste[0]['utilisateur_id'];
}

$disponibilites = [];

if ($praticien_selectionne) {
    // Récupérer les disponibilités futures du praticien sélectionné
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT c.id, c.debut, c.duree_minute
         FROM creneaux_disponibilite c
         LEFT JOIN rendezvous r ON r.creneaux_id = c.id
         WHERE c.praticien_id = ?
         AND r.id IS NULL
         AND c.debut >= NOW()
         ORDER BY c.debut"
    );

    mysqli_stmt_bind_param($stmt, "i", $praticien_selectionne);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $disponibilites = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
    <title>Modifier les disponibilités - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/modifier_disponibilites.css">
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
            <h1>✏️ Modifier les disponibilités
                <?php if ($nom_praticien): ?>
                    - <?= htmlspecialchars($nom_praticien) ?>
                <?php endif; ?>
            </h1>

            <!-- Sélecteur de praticien -->
            <div class="praticien-selector">
                <label for="praticien-select">👨‍⚕️ Modifier les disponibilités de :</label>
                <select id="praticien-select">
                    <?php foreach ($praticiens_liste as $prat): ?>
                        <option value="<?= $prat['utilisateur_id'] ?>" 
                                <?= $praticien_selectionne == $prat['utilisateur_id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($prat['prenom']) ?> <?= htmlspecialchars($prat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (empty($disponibilites)): ?>
                <p>Aucune disponibilité à modifier pour ce praticien.</p>
            <?php else: ?>

            <table class="table-card">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Durée</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($disponibilites as $dispo): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($dispo['debut'])) ?></td>
                        <td><?= date('H:i', strtotime($dispo['debut'])) ?></td>
                        <td><?= $dispo['duree_minute'] ?> min</td>
                        <td>
                            <a href="edit_disponibilite.php?id=<?= $dispo['id'] ?>&praticien=<?= $praticien_selectionne ?>">✏️ Modifier</a>
                            |
                            <a href="delete_disponibilite.php?id=<?= $dispo['id'] ?>&praticien=<?= $praticien_selectionne ?>"
                            onclick="return confirm('Supprimer cette disponibilité ?')">
                            🗑 Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php endif; ?>

            <br>
            <a href="disponibilites.php<?= $praticien_selectionne ? '?praticien='.$praticien_selectionne : '' ?>">← Retour aux disponibilités</a>
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
            window.location.href = 'modifier_disponibilites.php?praticien=' + praticienId;
        });
    </script>
</body>
</html>