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
    "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, s.id as structure_id, s.nom as structure_nom, t.description as structure_type
     FROM utilisateurs u
     JOIN structures_sante s ON s.secretaire_id = u.id
     LEFT JOIN type_struct t ON s.type = t.id
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

$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_info') {
        // Mise à jour des informations de base
        $prenom = trim($_POST['prenom']);
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        
        // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
        $stmt = mysqli_prepare($connexion, "SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_message = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Mise à jour de la table utilisateurs
            $stmt = mysqli_prepare($connexion, "UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssssi", $prenom, $nom, $email, $telephone, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Vos informations ont été mises à jour avec succès.";
                // Recharger les données
                $secretaire['prenom'] = $prenom;
                $secretaire['nom'] = $nom;
                $secretaire['email'] = $email;
                $secretaire['telephone'] = $telephone;
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour.";
            }
        }
    }
    
    if ($action === 'update_password') {
        // Mise à jour du mot de passe
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Récupérer le mot de passe actuel
        $stmt = mysqli_prepare($connexion, "SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (!password_verify($current_password, $user['mot_de_passe'])) {
            $error_message = "Le mot de passe actuel est incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Les nouveaux mots de passe ne correspondent pas.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($connexion, "UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Votre mot de passe a été modifié avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la modification du mot de passe.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Espace Professionnel</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/parametres.css">
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
            <div class="section-header">
                <h1>Paramètres du compte</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <span>✕</span>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Informations personnelles -->
            <div class="settings-section">
                <h3>Informations personnelles</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($secretaire['prenom']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($secretaire['nom']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($secretaire['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($secretaire['telephone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.reload()">Annuler</button>
                        <button type="submit" class="btn-action-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>

            <!-- Établissement -->
            <div class="settings-section">
                <h3>Établissement</h3>
                <div class="info-section">
                    <div class="form-group">
                        <label>Nom de l'établissement</label>
                        <div class="info-badge">
                            🏥 <?= htmlspecialchars($secretaire['structure_nom']) ?>
                        </div>
                    </div>
                    
                    <?php if ($secretaire['structure_type']): ?>
                        <div class="form-group">
                            <label>Type d'établissement</label>
                            <div class="info-badge">
                                <?= htmlspecialchars($secretaire['structure_type']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <p style="font-size: 0.85rem; color: var(--couleur-texte-secondaire); margin-top: 1rem;">
                        Vous êtes secrétaire pour cet établissement. Pour modifier l'établissement associé à votre compte, veuillez contacter l'administration.
                    </p>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="settings-section">
                <h3>Sécurité</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe *</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <p style="font-size: 0.85rem; color: var(--couleur-texte-secondaire); margin-top: 0.5rem;">
                                Minimum 8 caractères
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le nouveau mot de passe *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.reload()">Annuler</button>
                        <button type="submit" class="btn-action-primary">Modifier le mot de passe</button>
                    </div>
                </form>
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

</body>
</html>