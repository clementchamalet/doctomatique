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

// Check that the user is a practitioner
$stmt = mysqli_prepare($connexion, "SELECT 1 FROM praticiens WHERE utilisateur_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}

// Get the practitioner's info
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

// Get the specialties
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

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $duree = intval($_POST['duree']);
    $recurrence = $_POST['recurrence'] ?? 'aucune';
    $fin_recurrence = $_POST['fin_recurrence'] ?? null;

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
            $_SESSION['user_id'],
            $debutStr,
            $duree
        );

        mysqli_stmt_execute($stmt);

        if ($recurrence === 'quotidienne') {
            do {
                $dateDebut->modify('+1 day');
            } while ($dateDebut->format('N') == 7);
        }
        elseif ($recurrence === 'hebdomadaire') {
            $dateDebut->modify('+1 week');
        }
        else {
            break; // no recurrence
        }
    }


    for ($i = 0; $i < $iterations; $i++) {
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
            $_SESSION['user_id'],
            $debutStr,
            $duree
        );

        mysqli_stmt_execute($stmt);

        if ($recurrence === 'quotidienne') {
            do {
                $dateDebut->modify('+1 day');
            } while ($dateDebut->format('N') == 7); // 7 = Sunday
        } elseif ($recurrence === 'hebdomadaire') {
            $dateDebut->modify('+1 week');
        }
    }

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle disponibilité</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/nouvelle_disponibilite.css">
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
                <a href="auth.php" class="btn-connexion">Déconnexion</a>
            </div>
        </nav>
    </header>
    <!-- ========== MAIN LAYOUT ========== -->
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

        <!-- ========== MAIN CONTENT ========== -->
        <main class="pro-main">
            <h1>➕ Nouvelle disponibilité</h1>

            <?php if ($success): ?>
                <p style="color: green;">Disponibilité ajoutée avec succès.</p>
                <a href="agenda.php">← Retour à l’agenda</a>
            <?php else: ?>

            <form method="POST" class="form-card">

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
                    <option value="quotidienne">Tous les jours</option>
                    <option value="hebdomadaire">Toutes les semaines</option>
                </select>

                <label>Fin de récurrence</label>
                <input type="date" name="fin_recurrence">

                <button type="submit" class="btn-action-primary">
                    Enregistrer
                </button>

            </form>

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
