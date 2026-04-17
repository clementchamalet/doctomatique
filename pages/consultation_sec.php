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

// Récupérer l'ID du rendez-vous OU l'ID du patient
$rdv_id = isset($_GET['rdv_id']) ? intval($_GET['rdv_id']) : 0;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$praticien_param = isset($_GET['praticien']) ? intval($_GET['praticien']) : null;

// Si on a un patient_id, récupérer le dernier rendez-vous de ce patient dans l'établissement
if ($patient_id && !$rdv_id) {
    if ($praticien_param) {
        // Dernier RDV avec le praticien spécifique
        $stmt = mysqli_prepare(
            $connexion,
            "SELECT r.id
             FROM rendezvous r
             JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
             WHERE r.patient_id = ?
             AND r.structure_id = ?
             AND c.praticien_id = ?
             ORDER BY c.debut DESC
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "iii", $patient_id, $structure_id, $praticien_param);
    } else {
        // Dernier RDV dans l'établissement
        $stmt = mysqli_prepare(
            $connexion,
            "SELECT r.id
             FROM rendezvous r
             JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
             WHERE r.patient_id = ?
             AND r.structure_id = ?
             ORDER BY c.debut DESC
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $patient_id, $structure_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $rdv_id = $row['id'];
    } else {
        header("Location: patients_sec.php" . ($praticien_param ? "?praticien=$praticien_param" : ""));
        exit;
    }
}

if (!$rdv_id) {
    header("Location: professional_sec.php");
    exit;
}

// Récupérer les informations complètes du rendez-vous et vérifier qu'il appartient à l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        r.patient_id,
        c.debut,
        c.duree_minute,
        c.praticien_id,
        up.prenom as praticien_prenom,
        up.nom as praticien_nom,
        u.prenom,
        u.nom,
        u.email,
        u.telephone,
        p.date_naissance,
        p.adresse,
        p.contact_urgence,
        g.description as genre,
        m.description as motif,
        s.description as statut,
        st.nom as structure
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN patients p ON r.patient_id = p.utilisateur_id
     JOIN utilisateurs u ON p.utilisateur_id = u.id
     JOIN praticiens pr ON c.praticien_id = pr.utilisateur_id
     JOIN utilisateurs up ON pr.utilisateur_id = up.id
     LEFT JOIN genre g ON p.genre = g.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     LEFT JOIN structures_sante st ON r.structure_id = st.id
     WHERE r.id = ?
     AND r.structure_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv_id, $structure_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rdv = mysqli_fetch_assoc($result);

if (!$rdv) {
    header("Location: professional_sec.php");
    exit;
}

// === TRAITEMENT UPLOAD DE DOCUMENT (stockage en base64 dans url) ===
$upload_message = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $titre = trim($_POST['titre_document']);
    $description = trim($_POST['description_document']);
    
    if (empty($titre)) {
        $upload_error = "Le titre du document est obligatoire";
    } elseif (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] === UPLOAD_ERR_NO_FILE) {
        $upload_error = "Veuillez sélectionner un fichier";
    } elseif ($_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = "Erreur lors de l'upload du fichier";
    } else {
        $fichier = $_FILES['fichier'];
        $nom_fichier = $fichier['name'];
        $taille_fichier = $fichier['size'];
        $tmp_name = $fichier['tmp_name'];
        
        // Vérifier la taille (max 5 Mo pour base64)
        if ($taille_fichier > 5 * 1024 * 1024) {
            $upload_error = "Le fichier est trop volumineux (max 5 Mo)";
        } else {
            // Extensions autorisées
            $extensions_autorisees = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'];
            $extension = strtolower(pathinfo($nom_fichier, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensions_autorisees)) {
                $upload_error = "Type de fichier non autorisé. Formats acceptés : " . implode(', ', $extensions_autorisees);
            } else {
                // Lire le contenu du fichier
                $contenu_fichier = file_get_contents($tmp_name);
                
                // Déterminer le type MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                
                // Encoder en base64 et créer une data URL
                $base64 = base64_encode($contenu_fichier);
                $data_url = "data:$mime_type;base64,$base64";
                
                // Créer une description enrichie avec les métadonnées
                $meta_info = json_encode([
                    'nom_fichier' => $nom_fichier,
                    'type_mime' => $mime_type,
                    'taille' => $taille_fichier,
                    'extension' => $extension
                ]);
                
                $description_complete = $description . "\n[META]" . $meta_info;
                
                // Insérer dans la base de données
                $stmt = mysqli_prepare(
                    $connexion,
                    "INSERT INTO documents_patients (patient_id, expediteur, titre, description, url) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                
                mysqli_stmt_bind_param(
                    $stmt, 
                    "iisss",
                    $rdv['patient_id'],
                    $_SESSION['user_id'],
                    $titre,
                    $description_complete,
                    $data_url
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: consultation_sec.php?rdv_id=$rdv_id" . ($praticien_param ? "&praticien=$praticien_param" : "") . "&upload=success");
                    exit;
                } else {
                    $upload_error = "Erreur lors de l'enregistrement en base de données : " . mysqli_error($connexion);
                }
            }
        }
    }
}

// Récupérer les notes de consultation (en lecture seule pour la secrétaire)
$stmt = mysqli_prepare(
    $connexion,
    "SELECT notes, id
     FROM notes_consultation
     WHERE rendezvous_id = ?
     AND praticien_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv_id, $rdv['praticien_id']);
mysqli_stmt_execute($stmt);
$result_notes = mysqli_stmt_get_result($stmt);
$notes_existantes = mysqli_fetch_assoc($result_notes);

// Récupérer l'historique des rendez-vous du patient dans l'établissement
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        c.debut,
        c.praticien_id,
        up.prenom as praticien_prenom,
        up.nom as praticien_nom,
        m.description as motif,
        s.description as statut
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     JOIN praticiens pr ON c.praticien_id = pr.utilisateur_id
     JOIN utilisateurs up ON pr.utilisateur_id = up.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     WHERE r.patient_id = ?
     AND r.structure_id = ?
     ORDER BY c.debut DESC
     LIMIT 10"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv['patient_id'], $structure_id);
mysqli_stmt_execute($stmt);
$result_historique = mysqli_stmt_get_result($stmt);

$historique = [];
while ($row = mysqli_fetch_assoc($result_historique)) {
    $historique[] = $row;
}

// Récupérer les documents du patient
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        d.id,
        d.titre,
        d.description,
        d.url,
        u.prenom,
        u.nom
     FROM documents_patients d
     LEFT JOIN utilisateurs u ON d.expediteur = u.id
     WHERE d.patient_id = ?
     ORDER BY d.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $rdv['patient_id']);
mysqli_stmt_execute($stmt);
$result_docs = mysqli_stmt_get_result($stmt);

$documents = [];
while ($row = mysqli_fetch_assoc($result_docs)) {
    // Extraire les métadonnées de la description
    $meta = null;
    $description_clean = $row['description'];
    
    if (strpos($row['description'], '[META]') !== false) {
        $parts = explode('[META]', $row['description']);
        $description_clean = trim($parts[0]);
        if (isset($parts[1])) {
            $meta = json_decode($parts[1], true);
        }
    }
    
    $row['description_clean'] = $description_clean;
    $row['meta'] = $meta;
    $documents[] = $row;
}

// Calculer l'âge du patient
$age = '';
if ($rdv['date_naissance']) {
    $date_naissance = new DateTime($rdv['date_naissance']);
    $aujourd_hui = new DateTime();
    $age = $aujourd_hui->diff($date_naissance)->y . ' ans';
}

// Fonction pour formater la taille des fichiers
function formatTaille($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' Ko';
    } else {
        return $bytes . ' octets';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation - <?= htmlspecialchars($rdv['prenom'] . ' ' . $rdv['nom']) ?></title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/professional.css">
    <link rel="stylesheet" href="../assets/css/consultation.css">
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
                <a href="professional_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesStats) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📊</span>
                    <span>Tableau de bord</span>
                </a>
                <a href="rdv_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesRendezvous) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">📋</span>
                    <span>Rendez-vous</span>
                </a>
                <a href="patients_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesPatients) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">👥</span>
                    <span>Patients</span>
                </a>
                <a href="disponibilites.php" class="pro-nav-item <?= in_array($currentPage, $pagesDisponibilites) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⏰</span>
                    <span>Disponibilités</span>
                </a>
                <a href="parametres_sec.php" class="pro-nav-item <?= in_array($currentPage, $pagesParametres) ? 'active' : '' ?>">
                    <span class="pro-nav-icon">⚙️</span>
                    <span>Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- ========== CONTENU PRINCIPAL ========== -->
        <main class="pro-main">
            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class="upload-message">✓ Document uploadé avec succès</div>
            <?php endif; ?>
            
            <?php if ($upload_error): ?>
                <div class="error-message">❌ <?= htmlspecialchars($upload_error) ?></div>
            <?php endif; ?>

            <div class="page-header">
                <div class="header-top">
                    <div>
                        <h1 class="patient-name">👤 <?= htmlspecialchars($rdv['prenom'] . ' ' . $rdv['nom']) ?></h1>
                        <div class="rdv-info">
                            <div class="rdv-info-item">
                                📅 <?= date('d/m/Y à H:i', strtotime($rdv['debut'])) ?>
                            </div>
                            <div class="rdv-info-item">
                                ⏱️ <?= $rdv['duree_minute'] ?> minutes
                            </div>
                            <div class="rdv-info-item">
                                👨‍⚕️ Dr. <?= htmlspecialchars($rdv['praticien_prenom']) ?> <?= htmlspecialchars($rdv['praticien_nom']) ?>
                            </div>
                            <?php if ($rdv['structure']): ?>
                                <div class="rdv-info-item">
                                    🏥 <?= htmlspecialchars($rdv['structure']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="patients_sec.php<?= $praticien_param ? '?praticien='.$praticien_param : '' ?>" class="btn-secondary">
                        ← Retour
                    </a>
                </div>
            </div>

            <div class="main-grid">
                <!-- COLONNE GAUCHE : Informations patient -->
                <div class="left-column">
                    <div class="info-card">
                        <h2 class="card-title">👤 Informations patient</h2>
                        
                        <div class="info-row">
                            <span class="info-label">Nom complet</span>
                            <span class="info-value"><?= htmlspecialchars($rdv['prenom'] . ' ' . $rdv['nom']) ?></span>
                        </div>
                        
                        <?php if ($age): ?>
                            <div class="info-row">
                                <span class="info-label">Âge</span>
                                <span class="info-value"><?= $age ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($rdv['genre']): ?>
                            <div class="info-row">
                                <span class="info-label">Genre</span>
                                <span class="info-value"><?= htmlspecialchars($rdv['genre']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($rdv['email']) ?></span>
                        </div>
                        
                        <?php if ($rdv['telephone']): ?>
                            <div class="info-row">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value"><?= htmlspecialchars($rdv['telephone']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($rdv['adresse']): ?>
                            <div class="info-row">
                                <span class="info-label">Adresse</span>
                                <span class="info-value"><?= htmlspecialchars($rdv['adresse']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($rdv['contact_urgence']): ?>
                            <div class="info-row">
                                <span class="info-label">Contact urgence</span>
                                <span class="info-value"><?= htmlspecialchars($rdv['contact_urgence']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($rdv['motif']): ?>
                            <div class="info-row">
                                <span class="info-label">Motif</span>
                                <span class="info-value"><?= htmlspecialchars($rdv['motif']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($historique) > 0): ?>
                        <div class="info-card">
                            <h2 class="card-title">📋 Historique des consultations</h2>
                            <ul class="historique-list">
                                <?php foreach ($historique as $h): ?>
                                    <li class="historique-item" onclick="window.location.href='consultation_sec.php?rdv_id=<?= $h['id'] ?><?= $praticien_param ? '&praticien='.$praticien_param : '' ?>'">
                                        <div class="historique-date">
                                            <?= date('d/m/Y à H:i', strtotime($h['debut'])) ?>
                                        </div>
                                        <div class="historique-motif">
                                            Dr. <?= htmlspecialchars($h['praticien_prenom']) ?> <?= htmlspecialchars($h['praticien_nom']) ?><br>
                                            <?= htmlspecialchars($h['motif'] ?? 'Consultation') ?> 
                                            - <?= htmlspecialchars($h['statut'] ?? '') ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- SECTION DOCUMENTS -->
                    <div class="info-card">
                        <h2 class="card-title">📎 Documents du patient</h2>
                        
                        <!-- Formulaire d'upload -->
                        <div class="upload-form">
                            <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1rem;">📤 Ajouter un document</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="titre_document">Titre du document *</label>
                                    <input type="text" id="titre_document" name="titre_document" required 
                                           placeholder="Ex: Ordonnance, Résultats d'analyse...">
                                </div>
                                
                                <div class="form-group">
                                    <label for="description_document">Description (optionnel)</label>
                                    <textarea id="description_document" name="description_document" 
                                              placeholder="Informations complémentaires sur le document..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fichier">Fichier *</label>
                                    <input type="file" id="fichier" name="fichier" required 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt">
                                    <div class="file-info">
                                        Formats acceptés : PDF, JPG, PNG, DOC, DOCX, TXT (max 5 Mo)
                                    </div>
                                </div>
                                
                                <button type="submit" name="upload_document" class="btn-submit">
                                    📤 Télécharger le document
                                </button>
                            </form>
                        </div>

                        <!-- Liste des documents -->
                        <?php if (count($documents) > 0): ?>
                            <h3 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1rem;">📁 Documents existants (<?= count($documents) ?>)</h3>
                            <div class="documents-list">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="document-item">
                                        <span class="document-icon">📄</span>
                                        <div class="document-info">
                                            <div class="document-titre">
                                                <?= htmlspecialchars($doc['titre']) ?>
                                            </div>
                                            <div class="document-meta">
                                                <?php if ($doc['meta']): ?>
                                                    <?= htmlspecialchars($doc['meta']['nom_fichier']) ?> 
                                                    (<?= formatTaille($doc['meta']['taille']) ?>)<br>
                                                <?php endif; ?>
                                                <?php if ($doc['description_clean']): ?>
                                                    <?= htmlspecialchars($doc['description_clean']) ?><br>
                                                <?php endif; ?>
                                                <?php if ($doc['prenom']): ?>
                                                    Ajouté par <?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($doc['url']): ?>
                                            <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" class="doc-btn" download="<?= $doc['meta'] ? htmlspecialchars($doc['meta']['nom_fichier']) : 'document' ?>">
                                                👁️ Ouvrir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic; margin-top: 1rem;">
                                Aucun document pour ce patient.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COLONNE DROITE : Notes de consultation (lecture seule) -->
                <div class="right-column">
                    <div class="info-card">
                        <h2 class="card-title">📝 Notes de consultation</h2>
                        
                        <?php if ($notes_existantes && $notes_existantes['notes']): ?>
                            <div class="notes-readonly">
                                <div class="notes-header">
                                    <span class="notes-label">Notes du Dr. <?= htmlspecialchars($rdv['praticien_prenom']) ?> <?= htmlspecialchars($rdv['praticien_nom']) ?></span>
                                </div>
                                <div class="notes-content">
                                    <?= nl2br(htmlspecialchars($notes_existantes['notes'])) ?>
                                </div>
                                <p style="font-size: 0.85rem; color: #666; margin-top: 1rem; font-style: italic;">
                                    ℹ️ Les notes de consultation sont en lecture seule. Seul le praticien peut les modifier.
                                </p>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic;">
                                Aucune note de consultation pour ce rendez-vous.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
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