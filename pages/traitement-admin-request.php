<?php

require 'config.php';

// Check that the user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Ensure the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: espace-admin.php');
    exit;
}

$conn = get_db();
$action = $_POST['action'];

// ==================== UPDATE A USER (UNIFIED) ====================
if ($action === 'update_user') {
    $user_email = trim($_POST['user_email']);
    $nouveau_nom = isset($_POST['nouveau_nom']) ? trim($_POST['nouveau_nom']) : null;
    $nouveau_prenom = isset($_POST['nouveau_prenom']) ? trim($_POST['nouveau_prenom']) : null;
    $nouvel_email = isset($_POST['nouvel_email']) ? trim($_POST['nouvel_email']) : null;
    
    // Check that the user exists
    $sql_check = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        // If a new email is provided, check that it's not already in use
        if (!empty($nouvel_email) && $nouvel_email !== $user_email) {
            $sql_check_new = "SELECT id FROM utilisateurs WHERE email = ?";
            $stmt_check_new = mysqli_prepare($conn, $sql_check_new);
            mysqli_stmt_bind_param($stmt_check_new, "s", $nouvel_email);
            mysqli_stmt_execute($stmt_check_new);
            $result_new = mysqli_stmt_get_result($stmt_check_new);
            
            if (mysqli_num_rows($result_new) > 0) {
                mysqli_stmt_close($stmt_check_new);
                mysqli_stmt_close($stmt_check);
                mysqli_close($conn);
                header('Location: espace-admin.php?error=email_already_used');
                exit;
            }
            mysqli_stmt_close($stmt_check_new);
        }
        
        // Build the UPDATE query dynamically
        $updates = [];
        $params = [];
        $types = '';
        
        if (!empty($nouveau_nom)) {
            $updates[] = "nom = ?";
            $params[] = $nouveau_nom;
            $types .= 's';
        }
        if (!empty($nouveau_prenom)) {
            $updates[] = "prenom = ?";
            $params[] = $nouveau_prenom;
            $types .= 's';
        }
        if (!empty($nouvel_email)) {
            $updates[] = "email = ?";
            $params[] = $nouvel_email;
            $types .= 's';
        }
        
        if (count($updates) > 0) {
            $sql_update = "UPDATE utilisateurs SET " . implode(", ", $updates) . " WHERE email = ?";
            $params[] = $user_email;
            $types .= 's';
            
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, $types, ...$params);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }
        
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=user_updated');
        exit;
    } else {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=user_not_found');
        exit;
    }
}

// ==================== UPDATE NAME (LEGACY - KEPT FOR COMPATIBILITY) ====================
elseif ($action === 'update_nom') {
    $user_email = trim($_POST['user_email']);
    $nouveau_nom = trim($_POST['nouveau_nom']);
    
    // Check that the user exists
    $sql_check = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        // Update the name
        $sql_update = "UPDATE utilisateurs SET nom = ? WHERE email = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $nouveau_nom, $user_email);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=user_updated');
        exit;
    } else {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=user_not_found');
        exit;
    }
}

// ==================== UPDATE FIRST NAME ====================
elseif ($action === 'update_prenom') {
    $user_email = trim($_POST['user_email']);
    $nouveau_prenom = trim($_POST['nouveau_prenom']);
    
    $sql_check = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        $sql_update = "UPDATE utilisateurs SET prenom = ? WHERE email = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $nouveau_prenom, $user_email);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=user_updated');
        exit;
    } else {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=user_not_found');
        exit;
    }
}

// ==================== UPDATE EMAIL ====================
elseif ($action === 'update_email') {
    $user_email = trim($_POST['user_email']);
    $nouvel_email = trim($_POST['nouvel_email']);
    
    // Check that the new email isn't already in use
    $sql_check_new = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_check_new = mysqli_prepare($conn, $sql_check_new);
    mysqli_stmt_bind_param($stmt_check_new, "s", $nouvel_email);
    mysqli_stmt_execute($stmt_check_new);
    $result_new = mysqli_stmt_get_result($stmt_check_new);
    
    if (mysqli_num_rows($result_new) > 0) {
        mysqli_stmt_close($stmt_check_new);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=email_already_used');
        exit;
    }
    mysqli_stmt_close($stmt_check_new);
    
    // Check that the user exists
    $sql_check = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        $sql_update = "UPDATE utilisateurs SET email = ? WHERE email = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $nouvel_email, $user_email);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=user_updated');
        exit;
    } else {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=user_not_found');
        exit;
    }
}

// ==================== DELETE A USER ====================
elseif ($action === 'delete_user') {
    $user_email = trim($_POST['user_email']);
    
    // Get the user's ID
    $sql_get_user = "SELECT id FROM utilisateurs WHERE email = ?";
    $stmt_get_user = mysqli_prepare($conn, $sql_get_user);
    mysqli_stmt_bind_param($stmt_get_user, "s", $user_email);
    mysqli_stmt_execute($stmt_get_user);
    $result_user = mysqli_stmt_get_result($stmt_get_user);
    
    if (mysqli_num_rows($result_user) > 0) {
        $user = mysqli_fetch_assoc($result_user);
        $user_id = $user['id'];
        
        // Check that this isn't an admin
        $sql_check_admin = "SELECT ur.role_id FROM utilisateurs_roles ur
                           INNER JOIN roles r ON ur.role_id = r.id
                           WHERE ur.utilisateur_id = ? AND LOWER(r.nom) = 'admin'";
        $stmt_check_admin = mysqli_prepare($conn, $sql_check_admin);
        mysqli_stmt_bind_param($stmt_check_admin, "i", $user_id);
        mysqli_stmt_execute($stmt_check_admin);
        $result_admin = mysqli_stmt_get_result($stmt_check_admin);
        
        if (mysqli_num_rows($result_admin) > 0) {
            mysqli_stmt_close($stmt_check_admin);
            mysqli_stmt_close($stmt_get_user);
            mysqli_close($conn);
            header('Location: espace-admin.php?error=cannot_delete_admin');
            exit;
        }
        mysqli_stmt_close($stmt_check_admin);
        
        // Get the patient/practitioner IDs linked to this user
        $sql_patient_id = "SELECT utilisateur_id FROM patients WHERE utilisateur_id = ?";
        $stmt_patient = mysqli_prepare($conn, $sql_patient_id);
        mysqli_stmt_bind_param($stmt_patient, "i", $user_id);
        mysqli_stmt_execute($stmt_patient);
        $result_patient = mysqli_stmt_get_result($stmt_patient);
        $patient_id = (mysqli_num_rows($result_patient) > 0) ? mysqli_fetch_assoc($result_patient)['utilisateur_id'] : null;
        mysqli_stmt_close($stmt_patient);
        
        $sql_praticien_id = "SELECT utilisateur_id FROM praticiens WHERE utilisateur_id = ?";
        $stmt_praticien_check = mysqli_prepare($conn, $sql_praticien_id);
        mysqli_stmt_bind_param($stmt_praticien_check, "i", $user_id);
        mysqli_stmt_execute($stmt_praticien_check);
        $result_praticien = mysqli_stmt_get_result($stmt_praticien_check);
        $praticien_id = (mysqli_num_rows($result_praticien) > 0) ? mysqli_fetch_assoc($result_praticien)['utilisateur_id'] : null;
        mysqli_stmt_close($stmt_praticien_check);
        
        // === STEP 1: Delete documents_patients ===
        if ($patient_id) {
            $sql_delete_docs = "DELETE FROM documents_patients WHERE patient_id = ?";
            $stmt_delete_docs = mysqli_prepare($conn, $sql_delete_docs);
            mysqli_stmt_bind_param($stmt_delete_docs, "i", $patient_id);
            mysqli_stmt_execute($stmt_delete_docs);
            mysqli_stmt_close($stmt_delete_docs);
        }
        
        // === STEP 2: Delete avis (as patient OR practitioner) ===
        if ($patient_id) {
            $sql_delete_avis_patient = "DELETE FROM avis WHERE patient_id = ?";
            $stmt_delete_avis_p = mysqli_prepare($conn, $sql_delete_avis_patient);
            mysqli_stmt_bind_param($stmt_delete_avis_p, "i", $patient_id);
            mysqli_stmt_execute($stmt_delete_avis_p);
            mysqli_stmt_close($stmt_delete_avis_p);
        }
        if ($praticien_id) {
            $sql_delete_avis_praticien = "DELETE FROM avis WHERE praticien_id = ?";
            $stmt_delete_avis_pr = mysqli_prepare($conn, $sql_delete_avis_praticien);
            mysqli_stmt_bind_param($stmt_delete_avis_pr, "i", $praticien_id);
            mysqli_stmt_execute($stmt_delete_avis_pr);
            mysqli_stmt_close($stmt_delete_avis_pr);
        }
        
        // === STEP 3: Get the practitioner's slot IDs ===
        $creneaux_ids = [];
        if ($praticien_id) {
            $sql_get_creneaux = "SELECT id FROM creneaux_disponibilite WHERE praticien_id = ?";
            $stmt_get_creneaux = mysqli_prepare($conn, $sql_get_creneaux);
            mysqli_stmt_bind_param($stmt_get_creneaux, "i", $praticien_id);
            mysqli_stmt_execute($stmt_get_creneaux);
            $result_creneaux = mysqli_stmt_get_result($stmt_get_creneaux);
            while ($row = mysqli_fetch_assoc($result_creneaux)) {
                $creneaux_ids[] = $row['id'];
            }
            mysqli_stmt_close($stmt_get_creneaux);
        }
        
        // === STEP 4: Get appointment IDs linked to the patient OR the practitioner's slots ===
        $rendezvous_ids = [];
        
        if ($patient_id) {
            $sql_get_rdv_patient = "SELECT id FROM rendezvous WHERE patient_id = ?";
            $stmt_get_rdv_p = mysqli_prepare($conn, $sql_get_rdv_patient);
            mysqli_stmt_bind_param($stmt_get_rdv_p, "i", $patient_id);
            mysqli_stmt_execute($stmt_get_rdv_p);
            $result_rdv_p = mysqli_stmt_get_result($stmt_get_rdv_p);
            while ($row = mysqli_fetch_assoc($result_rdv_p)) {
                $rendezvous_ids[] = $row['id'];
            }
            mysqli_stmt_close($stmt_get_rdv_p);
        }
        
        if (!empty($creneaux_ids)) {
            $placeholders = implode(',', array_fill(0, count($creneaux_ids), '?'));
            $sql_get_rdv_creneaux = "SELECT id FROM rendezvous WHERE creneaux_id IN ($placeholders)";
            $stmt_get_rdv_c = mysqli_prepare($conn, $sql_get_rdv_creneaux);
            $types = str_repeat('i', count($creneaux_ids));
            mysqli_stmt_bind_param($stmt_get_rdv_c, $types, ...$creneaux_ids);
            mysqli_stmt_execute($stmt_get_rdv_c);
            $result_rdv_c = mysqli_stmt_get_result($stmt_get_rdv_c);
            while ($row = mysqli_fetch_assoc($result_rdv_c)) {
                if (!in_array($row['id'], $rendezvous_ids)) {
                    $rendezvous_ids[] = $row['id'];
                }
            }
            mysqli_stmt_close($stmt_get_rdv_c);
        }
        
        // === STEP 5: Delete notes_consultation linked to the appointments ===
        if (!empty($rendezvous_ids)) {
            $placeholders_rdv = implode(',', array_fill(0, count($rendezvous_ids), '?'));
            $sql_delete_notes = "DELETE FROM notes_consultation WHERE rendezvous_id IN ($placeholders_rdv)";
            $stmt_delete_notes = mysqli_prepare($conn, $sql_delete_notes);
            $types_rdv = str_repeat('i', count($rendezvous_ids));
            mysqli_stmt_bind_param($stmt_delete_notes, $types_rdv, ...$rendezvous_ids);
            mysqli_stmt_execute($stmt_delete_notes);
            mysqli_stmt_close($stmt_delete_notes);
        }
        
        // === STEP 6: Delete rendezvous ===
        if (!empty($rendezvous_ids)) {
            $sql_delete_rdv = "DELETE FROM rendezvous WHERE id IN ($placeholders_rdv)";
            $stmt_delete_rdv = mysqli_prepare($conn, $sql_delete_rdv);
            mysqli_stmt_bind_param($stmt_delete_rdv, $types_rdv, ...$rendezvous_ids);
            mysqli_stmt_execute($stmt_delete_rdv);
            mysqli_stmt_close($stmt_delete_rdv);
        }
        
        // === STEP 7: Delete creneaux_disponibilite ===
        if ($praticien_id) {
            $sql_delete_creneaux = "DELETE FROM creneaux_disponibilite WHERE praticien_id = ?";
            $stmt_delete_creneaux = mysqli_prepare($conn, $sql_delete_creneaux);
            mysqli_stmt_bind_param($stmt_delete_creneaux, "i", $praticien_id);
            mysqli_stmt_execute($stmt_delete_creneaux);
            mysqli_stmt_close($stmt_delete_creneaux);
        }
        
        // === STEP 8: Delete praticiens_structures ===
        if ($praticien_id) {
            $sql_delete_prat_struct = "DELETE FROM praticiens_structures WHERE praticien_id = ?";
            $stmt_delete_ps = mysqli_prepare($conn, $sql_delete_prat_struct);
            mysqli_stmt_bind_param($stmt_delete_ps, "i", $praticien_id);
            mysqli_stmt_execute($stmt_delete_ps);
            mysqli_stmt_close($stmt_delete_ps);
        }
        
        // === STEP 9: Delete praticiens_specialites ===
        if ($praticien_id) {
            $sql_delete_prat_spec = "DELETE FROM praticiens_specialites WHERE praticien_id = ?";
            $stmt_delete_pspec = mysqli_prepare($conn, $sql_delete_prat_spec);
            mysqli_stmt_bind_param($stmt_delete_pspec, "i", $praticien_id);
            mysqli_stmt_execute($stmt_delete_pspec);
            mysqli_stmt_close($stmt_delete_pspec);
        }
        
        // === STEP 10: Set secretaire_id to NULL in structures_sante ===
        $sql_update_structure = "UPDATE structures_sante SET secretaire_id = NULL WHERE secretaire_id = ?";
        $stmt_update_structure = mysqli_prepare($conn, $sql_update_structure);
        mysqli_stmt_bind_param($stmt_update_structure, "i", $user_id);
        mysqli_stmt_execute($stmt_update_structure);
        mysqli_stmt_close($stmt_update_structure);
        
        // === STEP 11: Delete praticiens ===
        $sql_delete_praticien = "DELETE FROM praticiens WHERE utilisateur_id = ?";
        $stmt_delete_praticien = mysqli_prepare($conn, $sql_delete_praticien);
        mysqli_stmt_bind_param($stmt_delete_praticien, "i", $user_id);
        mysqli_stmt_execute($stmt_delete_praticien);
        mysqli_stmt_close($stmt_delete_praticien);
        
        // === STEP 12: Delete patients ===
        $sql_delete_patient = "DELETE FROM patients WHERE utilisateur_id = ?";
        $stmt_delete_patient = mysqli_prepare($conn, $sql_delete_patient);
        mysqli_stmt_bind_param($stmt_delete_patient, "i", $user_id);
        mysqli_stmt_execute($stmt_delete_patient);
        mysqli_stmt_close($stmt_delete_patient);
        
        // === STEP 13: Delete utilisateurs_roles ===
        $sql_delete_roles = "DELETE FROM utilisateurs_roles WHERE utilisateur_id = ?";
        $stmt_delete_roles = mysqli_prepare($conn, $sql_delete_roles);
        mysqli_stmt_bind_param($stmt_delete_roles, "i", $user_id);
        mysqli_stmt_execute($stmt_delete_roles);
        mysqli_stmt_close($stmt_delete_roles);
        
        // === STEP 14: Delete utilisateurs (FINAL) ===
        $sql_delete_user = "DELETE FROM utilisateurs WHERE id = ?";
        $stmt_delete_user = mysqli_prepare($conn, $sql_delete_user);
        mysqli_stmt_bind_param($stmt_delete_user, "i", $user_id);
        mysqli_stmt_execute($stmt_delete_user);
        mysqli_stmt_close($stmt_delete_user);
        
        mysqli_stmt_close($stmt_get_user);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=user_deleted');
        exit;
        
    } else {
        mysqli_stmt_close($stmt_get_user);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=user_not_found');
        exit;
    }
}

// ==================== UPDATE A FACILITY ====================
elseif ($action === 'update_structure') {
    $structure_id = trim($_POST['structure_id']);
    $structure_nom = isset($_POST['structure_nom']) ? trim($_POST['structure_nom']) : null;
    $structure_adresse = isset($_POST['structure_adresse']) ? trim($_POST['structure_adresse']) : null;
    $structure_telephone = isset($_POST['structure_telephone']) ? trim($_POST['structure_telephone']) : null;
    $structure_secretaire_id = isset($_POST['structure_secretaire_id']) ? trim($_POST['structure_secretaire_id']) : null;
    
    // Check that the facility exists
    $sql_check = "SELECT id FROM structures_sante WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $structure_id);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        // Build the UPDATE query dynamically
        $updates = [];
        $params = [];
        $types = '';
        
        if (!empty($structure_nom)) {
            $updates[] = "nom = ?";
            $params[] = $structure_nom;
            $types .= 's';
        }
        if (!empty($structure_adresse)) {
            $updates[] = "adresse = ?";
            $params[] = $structure_adresse;
            $types .= 's';
        }
        if (!empty($structure_telephone)) {
            $updates[] = "telephone = ?";
            $params[] = $structure_telephone;
            $types .= 's';
        }
        if (!empty($structure_secretaire_id)) {
            $updates[] = "secretaire_id = ?";
            $params[] = $structure_secretaire_id;
            $types .= 'i';
        }
        
        if (count($updates) > 0) {
            $sql_update = "UPDATE structures_sante SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $structure_id;
            $types .= 'i';
            
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, $types, ...$params);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }
        
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?success=structure_updated');
        exit;
    } else {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conn);
        header('Location: espace-admin.php?error=structure_not_found');
        exit;
    }
}

// If no recognized action
else {
    mysqli_close($conn);
    header('Location: espace-admin.php?error=unknown_action');
    exit;
}

?>