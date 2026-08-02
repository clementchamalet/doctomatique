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

// Determine the selected practitioner
$praticien_selectionne = isset($_GET['praticien']) && $_GET['praticien'] !== '' ? intval($_GET['praticien']) : null;

// If no practitioner is selected and the list isn't empty, select the first one
if ($praticien_selectionne === null && !empty($praticiens_liste)) {
    $praticien_selectionne = $praticiens_liste[0]['utilisateur_id'];
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

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $praticien_id = intval($_POST['praticien_id']);
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $duree = intval($_POST['duree']);
    $recurrence = $_POST['recurrence'] ?? 'aucune';
    $fin_recurrence = $_POST['fin_recurrence'] ?? null;

    // Check that the practitioner really belongs to the facility
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT 1 FROM praticiens_structures WHERE praticien_id = ? AND structure_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $praticien_id, $structure_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $dateDebut = new DateTime("$date $heure");
        $dateFin = !empty($_POST['fin_recurrence'])
            ? new DateTime($_POST['fin_recurrence'] . ' 23:59:59')
            : clone $dateDebut;

        while ($dateDebut <= $dateFin) {
            $stmt = mysqli_prepare(
                $connexion,
                "INSERT INTO creneaux_disponibilite
                (praticien_id, debut, duree_minute)
                VALUES (?, ?, ?)"
            );

            $debutStr = $dateDebut->format('Y-m-d H:i:s');

            mysqli_stmt_bind_param(
                $stmt,
                "isi",
                $praticien_id,
                $debutStr,
                $duree
            );

            mysqli_stmt_execute($stmt);

            if ($recurrence === 'quotidienne') {
                do {
                    $dateDebut->modify('+1 day');
                } while ($dateDebut->format('N') == 7); // Skip Sundays
            }
            elseif ($recurrence === 'hebdomadaire') {
                $dateDebut->modify('+1 week');
            }
            else {
                break; // no recurrence
            }
        }

        $success = true;
    } else {
        $error = "Praticien invalide pour cet établissement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle disponibilité - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/nouvelle_disponibilite.css">
    <link rel="stylesheet" href="../assets/css/style_sec.css">
</head>
<body>
    <!-- ========== PAGE HEADER ========== -->
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
    <!-- ========== MAIN LAYOUT ========== -->
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

        <!-- ========== MAIN CONTENT ========== -->
        <main class="pro-main">
            <h1>➕ Nouvelle disponibilité
                <?php if ($nom_praticien): ?>
                    - <?= htmlspecialchars($nom_praticien) ?>
                <?php endif; ?>
            </h1>

            <?php if ($error): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p style="color: green;">Disponibilité(s) ajoutée(s) avec succès.</p>
                <a href="disponibilites_sec.php<?= $praticien_selectionne ? '?praticien='.$praticien_selectionne : '' ?>">← Retour aux disponibilités</a>
            <?php else: ?>

            <!-- Practitioner selector -->
            <div class="praticien-selector">
                <label for="praticien-select">👨‍⚕️ Ajouter une disponibilité pour :</label>
                <select id="praticien-select">
                    <?php foreach ($praticiens_liste as $prat): ?>
                        <option value="<?= $prat['utilisateur_id'] ?>" 
                                <?= $praticien_selectionne == $prat['utilisateur_id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($prat['prenom']) ?> <?= htmlspecialchars($prat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <form method="POST" class="form-card">
                <input type="hidden" name="praticien_id" value="<?= $praticien_selectionne ?>">

                <label>Date</label>
                <input type="date" name="date" required>

                <label>Heure de début</label>
                <input type="time" name="heure" required>

                <label>Durée (minutes)</label>
                <select name="duree" required>
                    <option value="15">15 min</option>
                    <option value="30">30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">60 min</option>
                </select>

                <label>Récurrence</label>
                <select name="recurrence" id="recurrence">
                    <option value="aucune">Aucune</option>
                    <option value="quotidienne">Tous les jours (sauf dimanche)</option>
                    <option value="hebdomadaire">Toutes les semaines</option>
                </select>

                <label>Fin de récurrence</label>
                <input type="date" name="fin_recurrence">

                <button type="submit" class="btn-action-primary">
                    Enregistrer
                </button>

            </form>

            <br>
            <a href="disponibilites_sec.php<?= $praticien_selectionne ? '?praticien='.$praticien_selectionne : '' ?>">← Retour aux disponibilités</a>

            <?php endif; ?>
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
        // Auto-submit when the practitioner changes
        document.getElementById('praticien-select').addEventListener('change', function() {
            const praticienId = this.value;
            window.location.href = 'nouvelle_disponibilite.php?praticien=' + praticienId;
        });
    </script>

</body>
</html>