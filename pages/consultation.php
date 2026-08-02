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

// Get the appointment ID OR the patient ID
$rdv_id = isset($_GET['rdv_id']) ? intval($_GET['rdv_id']) : 0;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// If we have a patient_id, get that patient's most recent appointment
if ($patient_id && !$rdv_id) {
    $stmt = mysqli_prepare(
        $connexion,
        "SELECT r.id
         FROM rendezvous r
         JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
         WHERE r.patient_id = ?
         AND c.praticien_id = ?
         ORDER BY c.debut DESC
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $rdv_id = $row['id'];
    } else {
        header("Location: patients.php");
        exit;
    }
}

if (!$rdv_id) {
    header("Location: agenda.php");
    exit;
}

// Get the full appointment info
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        r.patient_id,
        c.debut,
        c.duree_minute,
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
     LEFT JOIN genre g ON p.genre = g.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     LEFT JOIN structures_sante st ON r.structure_id = st.id
     WHERE r.id = ?
     AND c.praticien_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rdv = mysqli_fetch_assoc($result);

if (!$rdv) {
    header("Location: agenda.php");
    exit;
}

// === DOCUMENT UPLOAD HANDLING (stored as base64 in url) ===
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
        
        // Check the size (max 5 MB for base64)
        if ($taille_fichier > 5 * 1024 * 1024) {
            $upload_error = "Le fichier est trop volumineux (max 5 Mo)";
        } else {
            // Allowed extensions
            $extensions_autorisees = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'];
            $extension = strtolower(pathinfo($nom_fichier, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensions_autorisees)) {
                $upload_error = "Type de fichier non autorisé. Formats acceptés : " . implode(', ', $extensions_autorisees);
            } else {
                // Read the file contents
                $contenu_fichier = file_get_contents($tmp_name);
                
                // Determine the MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                
                // Encode as base64 and build a data URL
                $base64 = base64_encode($contenu_fichier);
                $data_url = "data:$mime_type;base64,$base64";
                
                // Build an enriched description with the metadata
                $meta_info = json_encode([
                    'nom_fichier' => $nom_fichier,
                    'type_mime' => $mime_type,
                    'taille' => $taille_fichier,
                    'extension' => $extension
                ]);
                
                $description_complete = $description . "\n[META]" . $meta_info;
                
                // Insert into the database
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
                    header("Location: consultation.php?rdv_id=$rdv_id&upload=success");
                    exit;
                } else {
                    $upload_error = "Erreur lors de l'enregistrement en base de données : " . mysqli_error($connexion);
                }
            }
        }
    }
}

// Get the consultation notes
$stmt = mysqli_prepare(
    $connexion,
    "SELECT notes, id
     FROM notes_consultation
     WHERE rendezvous_id = ?
     AND praticien_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result_notes = mysqli_stmt_get_result($stmt);
$notes_existantes = mysqli_fetch_assoc($result_notes);

// Get the patient's appointment history
$stmt = mysqli_prepare(
    $connexion,
    "SELECT 
        r.id,
        c.debut,
        m.description as motif,
        s.description as statut
     FROM rendezvous r
     JOIN creneaux_disponibilite c ON r.creneaux_id = c.id
     LEFT JOIN motif m ON r.motif = m.id
     LEFT JOIN statut s ON r.statut = s.id
     WHERE r.patient_id = ?
     AND c.praticien_id = ?
     ORDER BY c.debut DESC
     LIMIT 10"
);

mysqli_stmt_bind_param($stmt, "ii", $rdv['patient_id'], $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result_historique = mysqli_stmt_get_result($stmt);

$historique = [];
while ($row = mysqli_fetch_assoc($result_historique)) {
    $historique[] = $row;
}

// Get the patient's documents
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
    // Extract the metadata from the description
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

// Handle the notes form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $notes = $_POST['notes'];
    
    if ($notes_existantes) {
        $stmt = mysqli_prepare(
            $connexion,
            "UPDATE notes_consultation SET notes = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "si", $notes, $notes_existantes['id']);
    } else {
        $stmt = mysqli_prepare(
            $connexion,
            "INSERT INTO notes_consultation (rendezvous_id, praticien_id, notes) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iis", $rdv_id, $_SESSION['user_id'], $notes);
    }
    
    mysqli_stmt_execute($stmt);
    header("Location: consultation.php?rdv_id=$rdv_id&success=1");
    exit;
}

// Calculate the patient's age
$age = '';
if ($rdv['date_naissance']) {
    $date_naissance = new DateTime($rdv['date_naissance']);
    $aujourd_hui = new DateTime();
    $age = $aujourd_hui->diff($date_naissance)->y . ' ans';
}

// Function to format file sizes
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
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">✓ Notes enregistrées avec succès</div>
            <?php endif; ?>
            
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
                            <?php if ($rdv['structure']): ?>
                                <div class="rdv-info-item">
                                    🏥 <?= htmlspecialchars($rdv['structure']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-grid">
                <!-- LEFT COLUMN: Patient info -->
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
                                    <li class="historique-item" onclick="window.location.href='consultation.php?rdv_id=<?= $h['id'] ?>'">
                                        <div class="historique-date">
                                            <?= date('d/m/Y à H:i', strtotime($h['debut'])) ?>
                                        </div>
                                        <div class="historique-motif">
                                            <?= htmlspecialchars($h['motif'] ?? 'Consultation') ?> 
                                            - <?= htmlspecialchars($h['statut'] ?? '') ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- DOCUMENTS SECTION -->
                    <div class="info-card">
                        <h2 class="card-title">📎 Documents du patient</h2>
                        
                        <!-- Upload form -->
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
                        
                        <!-- Document list -->
                        <?php if (count($documents) > 0): ?>
                            <h3 style="margin-bottom: 1rem; font-size: 1rem;">📁 Documents existants (<?= count($documents) ?>)</h3>
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
                                                    Ajouté par Dr. <?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?>
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

                <!-- RIGHT COLUMN: Consultation notes -->
                <div class="right-column">
                    <div class="info-card">
                        <h2 class="card-title">📝 Notes de consultation</h2>
                        
                        <form method="POST">
                            <textarea 
                                name="notes" 
                                class="notes-editor" 
                                placeholder="Saisissez vos notes de consultation ici...

Exemples de sections :
- Motif de consultation
- Anamnèse
- Examen clinique
- Diagnostic
- Traitement prescrit
- Recommandations"><?= htmlspecialchars($notes_existantes['notes'] ?? '') ?></textarea>
                            
                            <div class="action-buttons">
                                <button type="submit" name="save_notes" class="btn-submit">
                                    💾 Enregistrer les notes
                                </button>