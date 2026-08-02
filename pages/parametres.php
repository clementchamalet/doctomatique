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

// Check that this is a practitioner
$stmt = mysqli_prepare($connexion, "SELECT 1 FROM praticiens WHERE utilisateur_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_info') {
        // Update basic info
        $prenom = trim($_POST['prenom']);
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        $biographie = trim($_POST['biographie']);
        
        // Check that the email isn't already used by another user
        $stmt = mysqli_prepare($connexion, "SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_message = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Update the utilisateurs table
            $stmt = mysqli_prepare($connexion, "UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssssi", $prenom, $nom, $email, $telephone, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                // Update the bio in praticiens
                $stmt = mysqli_prepare($connexion, "UPDATE praticiens SET biographie = ? WHERE utilisateur_id = ?");
                mysqli_stmt_bind_param($stmt, "si", $biographie, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                
                $success_message = "Vos informations ont été mises à jour avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour.";
            }
        }
    }
    
    if ($action === 'update_password') {
        // Update the password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get the current password
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
    
    if ($action === 'update_structures') {
        // Update facilities
        $structures = $_POST['structures'] ?? [];
        
        // Delete all previous associations
        $stmt = mysqli_prepare($connexion, "DELETE FROM praticiens_structures WHERE praticien_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        
        // Add the new associations
        if (!empty($structures)) {
            $stmt = mysqli_prepare($connexion, "INSERT INTO praticiens_structures (praticien_id, structure_id) VALUES (?, ?)");
            foreach ($structures as $structure_id) {
                mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $structure_id);
                mysqli_stmt_execute($stmt);
            }
        }
        
        $success_message = "Vos établissements ont été mis à jour avec succès.";
    }
}

// Get the practitioner's info
$stmt = mysqli_prepare($connexion, 
    "SELECT u.prenom, u.nom, u.email, u.telephone, p.biographie, p.numero_licence
     FROM utilisateurs u
     JOIN praticiens p ON p.utilisateur_id = u.id
     WHERE u.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$praticien = mysqli_fetch_assoc($result);

// Get the specialties
$stmt = mysqli_prepare($connexion,
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

// Get all available facilities
$structures_disponibles = mysqli_query($connexion, 
    "SELECT s.id, s.nom, t.description as type
     FROM structures_sante s
     LEFT JOIN type_struct t ON s.type = t.id
     ORDER BY s.nom"
);

// Get the facilities currently associated with the practitioner
$stmt = mysqli_prepare($connexion,
    "SELECT structure_id FROM praticiens_structures WHERE praticien_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$structures_praticien = [];
while ($row = mysqli_fetch_assoc($result)) {
    $structures_praticien[] = $row['structure_id'];
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

            <!-- Personal information -->
            <div class="settings-section">
                <h3>Informations personnelles</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($praticien['prenom']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($praticien['nom']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($praticien['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($praticien['telephone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="biographie">Biographie</label>
                        <textarea id="biographie" name="biographie" placeholder="Présentez-vous à vos patients..."><?= htmlspecialchars($praticien['biographie'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Numéro de licence et spécialisation</label>
                        <p style="font-size: 0.85rem; color: var(--couleur-texte-secondaire); margin-top: 0.5rem;">
                            Pour modifier votre numéro de licence et votre spécialisation, veuillez contacter l'administration.
                        </p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.reload()">Annuler</button>
                        <button type="submit" class="btn-action-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>

            <!-- Facilities -->
            <div class="settings-section">
                <h3>Établissements</h3>
                <form method="POST" action="" id="structuresForm">
                    <input type="hidden" name="action" value="update_structures">
                    
                    <div class="form-group">
                        <label>Rechercher et ajouter des établissements</label>
                        <div class="structure-search-container">
                            <input 
                                type="text" 
                                id="structureSearch" 
                                placeholder="Rechercher un établissement par nom..."
                                autocomplete="off"
                            >
                            <div id="searchResults" class="search-results"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Vos établissements</label>
                        <div id="selectedStructures" class="selected-structures">
                            <?php 
                            // Get the already associated facilities
                            $stmt = mysqli_prepare($connexion,
                                "SELECT s.id, s.nom, t.description as type
                                 FROM structures_sante s
                                 LEFT JOIN type_struct t ON s.type = t.id
                                 JOIN praticiens_structures ps ON ps.structure_id = s.id
                                 WHERE ps.praticien_id = ?
                                 ORDER BY s.nom"
                            );
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) === 0):
                            ?>
                                <p class="no-structures">Aucun établissement sélectionné</p>
                            <?php 
                            else:
                                while ($structure = mysqli_fetch_assoc($result)):
                            ?>
                                <div class="selected-structure-item" data-id="<?= $structure['id'] ?>">
                                    <input type="hidden" name="structures[]" value="<?= $structure['id'] ?>">
                                    <div class="structure-info">
                                        <div class="structure-name"><?= htmlspecialchars($structure['nom']) ?></div>
                                        <?php if ($structure['type']): ?>
                                            <div class="structure-type"><?= htmlspecialchars($structure['type']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="remove-structure" onclick="removeStructure(<?= $structure['id'] ?>)">
                                        ✕
                                    </button>
                                </div>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.reload()">Annuler</button>
                        <button type="submit" class="btn-action-primary">Enregistrer les établissements</button>
                    </div>
                </form>
            </div>

            <!-- Security -->
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

    <script>
        // Handle facility search
        const searchInput = document.getElementById('structureSearch');
        const searchResults = document.getElementById('searchResults');
        const selectedStructuresContainer = document.getElementById('selectedStructures');
        
        let allStructures = [];
        let selectedStructureIds = new Set();
        
        // Load all facilities on page load
        async function loadStructures() {
            try {
                const response = await fetch('api_structures.php');
                allStructures = await response.json();
                
                // Initialize the already selected IDs
                document.querySelectorAll('.selected-structure-item').forEach(item => {
                    selectedStructureIds.add(parseInt(item.dataset.id));
                });
            } catch (error) {
                console.error('Erreur lors du chargement des structures:', error);
            }
        }
        
        // Search with a delay (debounce)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim().toLowerCase();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                displaySearchResults(query);
            }, 300);
        });
        
        // Display search results
        function displaySearchResults(query) {
            const filtered = allStructures.filter(structure => 
                structure.nom.toLowerCase().includes(query)
            );
            
            if (filtered.length === 0) {
                searchResults.innerHTML = '<div class="no-results">Aucun établissement trouvé</div>';
                searchResults.classList.add('active');
                return;
            }
            
            searchResults.innerHTML = filtered.map(structure => {
                const isSelected = selectedStructureIds.has(structure.id);
                return `
                    <div class="search-result-item ${isSelected ? 'disabled' : ''}" 
                         onclick="${isSelected ? '' : `addStructure(${structure.id}, '${escapeHtml(structure.nom)}', '${escapeHtml(structure.type || '')}')`}">
                        <div class="search-result-name">${escapeHtml(structure.nom)}</div>
                        ${structure.type ? `<div class="search-result-type">${escapeHtml(structure.type)}</div>` : ''}
                        ${isSelected ? '<div class="search-result-type">Déjà sélectionné</div>' : ''}
                    </div>
                `;
            }).join('');
            
            searchResults.classList.add('active');
        }
        
        // Add a facility
        function addStructure(id, nom, type) {
            if (selectedStructureIds.has(id)) return;
            
            selectedStructureIds.add(id);
            
            // Remove the "No facility" message
            const noStructuresMsg = selectedStructuresContainer.querySelector('.no-structures');
            if (noStructuresMsg) {
                noStructuresMsg.remove();
            }
            
            // Create the element
            const structureElement = document.createElement('div');
            structureElement.className = 'selected-structure-item';
            structureElement.dataset.id = id;
            structureElement.innerHTML = `
                <input type="hidden" name="structures[]" value="${id}">
                <div class="structure-info">
                    <div class="structure-name">${escapeHtml(nom)}</div>
                    ${type ? `<div class="structure-type">${escapeHtml(type)}</div>` : ''}
                </div>
                <button type="button" class="remove-structure" onclick="removeStructure(${id})">
                    ✕
                </button>
            `;
            
            selectedStructuresContainer.appendChild(structureElement);
            
            // Reset the search
            searchInput.value = '';
            searchResults.classList.remove('active');
        }
        
        // Remove a facility
        function removeStructure(id) {
            selectedStructureIds.delete(id);
            
            const element = selectedStructuresContainer.querySelector(`[data-id="${id}"]`);
            if (element) {
                element.remove();
            }
            
            // Show the message if no facility remains
            if (selectedStructureIds.size === 0) {
                selectedStructuresContainer.innerHTML = '<p class="no-structures">Aucun établissement sélectionné</p>';
            }
        }
        
        // Escape HTML to prevent XSS injection
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close the results when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
        
        // Load facilities on page load
        loadStructures();
    </script>

</body>
</html>