<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

require 'config.php';

// Ensure the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inscription.php');
    exit;
}

$conn = get_db();

// Get form data
$type_compte = $_POST['type_compte'];
$email = trim($_POST['email']);
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$telephone = trim($_POST['telephone']);
$mot_de_passe = $_POST['mot_de_passe'];
$confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];

// Validate data
$erreurs = [];

// Check that all fields are filled in
if (empty($type_compte) || empty($email) || empty($nom) || empty($prenom) || empty($telephone) || empty($mot_de_passe)) {
    $erreurs[] = "Tous les champs sont obligatoires";
}

// Check that the email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = "L'email n'est pas valide";
}

// Check that the passwords match
if ($mot_de_passe !== $confirmation_mot_de_passe) {
    $erreurs[] = "Les mots de passe ne correspondent pas";
}

// Check that the password is at least 4 characters long
if (strlen($mot_de_passe) < 4) {
    $erreurs[] = "Le mot de passe doit contenir au moins 4 caractères";
}

// Check that the email doesn't already exist in the DB (using a prepared statement)
$sql_check_email = "SELECT id FROM utilisateurs WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check_email);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    $erreurs[] = "Cet email est déjà utilisé";
}
mysqli_stmt_close($stmt_check);

// If there are errors, display them and STOP execution
if (!empty($erreurs)) {
    echo $erreurs[0] . '
        <br>
        <form action="inscription.php">
            <button type="submit">Retour à l\'inscription</button>
        </form>';
    exit;
}

// Hash the password
$mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

// Insert the user into the DB
$sql_insert = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, telephone, actif) VALUES (?, ?, ?, ?, ?, 1)";
$stmt_insert = mysqli_prepare($conn, $sql_insert);
mysqli_stmt_bind_param($stmt_insert, "sssss", $email, $mot_de_passe_hash, $nom, $prenom, $telephone);

if (mysqli_stmt_execute($stmt_insert)) {
    // Get the ID of the newly created user
    $user_id = mysqli_insert_id($conn);

    // Get the role ID matching the account type
    $sql_role = "SELECT id FROM roles WHERE LOWER(nom) = ?";
    $stmt_role = mysqli_prepare($conn, $sql_role);
    $type_compte_lower = strtolower($type_compte);
    mysqli_stmt_bind_param($stmt_role, "s", $type_compte_lower);
    mysqli_stmt_execute($stmt_role);
    $result_role = mysqli_stmt_get_result($stmt_role);
    
    if (mysqli_num_rows($result_role) > 0) {
        $role = mysqli_fetch_assoc($result_role);
        $role_id = $role['id'];
        
        // Link the user to the role in the utilisateurs_roles table
        $sql_assoc = "INSERT INTO utilisateurs_roles (utilisateur_id, role_id) VALUES (?, ?)";
        $stmt_assoc = mysqli_prepare($conn, $sql_assoc);
        mysqli_stmt_bind_param($stmt_assoc, "ii", $user_id, $role_id);
        mysqli_stmt_execute($stmt_assoc);
        mysqli_stmt_close($stmt_assoc);

        // If not a patient, also link to the patient role
        if ($type_compte_lower != 'patient') {
            // Find the 'patient' role ID
            $sql_id_patient = "SELECT id FROM roles WHERE LOWER(nom) = 'patient'";
            $result_id_patient = mysqli_query($conn, $sql_id_patient);

            if (mysqli_num_rows($result_id_patient) > 0) {
                $id_patient = mysqli_fetch_assoc($result_id_patient);

                // Link the patient role ID to the user
                $sql_assoc2 = "INSERT INTO utilisateurs_roles (utilisateur_id, role_id) VALUES (?, ?)";
                $stmt_assoc2 = mysqli_prepare($conn, $sql_assoc2);
                mysqli_stmt_bind_param($stmt_assoc2, "ii", $user_id, $id_patient['id']);
                mysqli_stmt_execute($stmt_assoc2);
                mysqli_stmt_close($stmt_assoc2);
            }
        }
    } else {
        echo 'Le rôle "' . htmlspecialchars($type_compte) . '" n\'existe pas dans la base de données.';
        exit;
    }
    
    mysqli_stmt_close($stmt_role);
    mysqli_stmt_close($stmt_insert);
    
    // Show success

    if ($type_compte_lower == 'patient') {
        echo '
        <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Inscription Patient</title>
                <link rel="stylesheet" href="../assets/css/variables.css">
                <link rel="stylesheet" href="../assets/css/global.css">
                <link rel="stylesheet" href="../assets/css/inscription.css">
            </head>
        <body>
            <main>
                <div class="inscription-container">
                    <div class="inscription-header">
                        <h1>Bienvenue</h1>
                        <h3>Merci pour votre confiance ! Nous vous invitons à remplir les informations ci-dessous</h3>
                    </div>
                        <br>
                        <form class="inscription-form inscription-form--patient" action="traitement-inscription2.php" method="POST">
                        
                            <input type="hidden" name="user_id" value="' . $user_id . '">
                            
                            <div class="form-section">
                                <h4>Informations personnelles</h4>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <label for="date_naissance">Date de naissance :</label>
                                        <input type="date" name="date_naissance" id="date_naissance" required>
                                    </div>
                                    <div class="form-col">
                                        <label for="genre">Genre :</label>
                                        <select name="genre" id="genre" required>
                                            <option value="">-- Choisir --</option>
                                            <option value="homme">Homme</option>
                                            <option value="femme">Femme</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <label for="adresse">Adresse complète :</label>
                                <textarea name="adresse" id="adresse" rows="2" required></textarea>
                                
                                <label for="contact_urgence">Contact d\'urgence (téléphone) :</label>
                                <input type="tel" name="contact_urgence" id="contact_urgence" required>
                            </div>
                            
                            <button type="submit">Valider</button>
                        </form>
                </div>
            </main>
        </body>
        ';
    }

    if ($type_compte_lower == 'praticien') {
        // Get the available specialties for the select
        $sql_specialites = "SELECT id, nom FROM specialites_medecine ORDER BY nom ASC";
        $result_specialites = mysqli_query($conn, $sql_specialites);
        
        $options_specialites = '';
        if (mysqli_num_rows($result_specialites) > 0) {
            while ($row = mysqli_fetch_assoc($result_specialites)) {
                $options_specialites .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['nom']) . '</option>';
            }
        }
        
        // Get the available healthcare facilities for the select
        $sql_structures = "SELECT id, nom FROM structures_sante ORDER BY nom ASC";
        $result_structures = mysqli_query($conn, $sql_structures);
        
        $options_structures = '';
        if (mysqli_num_rows($result_structures) > 0) {
            while ($row = mysqli_fetch_assoc($result_structures)) {
                $options_structures .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['nom']) . '</option>';
            }
        }
        
        echo '
        <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Erreur - Inscription</title>
                <link rel="stylesheet" href="../assets/css/variables.css">
                <link rel="stylesheet" href="../assets/css/global.css">
                <link rel="stylesheet" href="../assets/css/inscription.css">
            </head>
        <body>
            <main>        
                <div class="inscription-container">
                    <div class="inscription-header">
                        <h1>Bienvenue Médecin</h1>
                        <h3>Merci pour votre confiance ! Nous vous invitons à remplir les informations ci-dessous</h3>
                    </div>
                        <br>
                        <form class="inscription-form inscription-form--praticien" action="traitement-inscription2.php" method="POST">
                        
                            <input type="hidden" name="user_id" value="' . $user_id . '">
                            
                            <div class="form-section">
                                <h4>Informations personnelles</h4>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <label for="date_naissance">Date de naissance :</label>
                                        <input type="date" name="date_naissance" id="date_naissance" required>
                                    </div>
                                    <div class="form-col">
                                        <label for="genre">Genre :</label>
                                        <select name="genre" id="genre" required>
                                            <option value="">-- Choisir --</option>
                                            <option value="homme">Homme</option>
                                            <option value="femme">Femme</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <label for="adresse">Adresse complète :</label>
                                <textarea name="adresse" id="adresse" rows="2" required></textarea>
                                
                                <label for="contact_urgence">Contact d\'urgence (téléphone) :</label>
                                <input type="tel" name="contact_urgence" id="contact_urgence" required>
                            </div>

                            <div class="form-section">
                                <h4>Informations professionnelles</h4>
                                
                                <label for="numero_licence">Numéro de licence (RPPS) :</label>
                                <input type="text" name="numero_licence" id="numero_licence" required>
                                
                                <label for="specialite">Spécialité médicale :</label>
                                <select name="specialite" id="specialite" required>
                                    <option value="">-- Choisir une spécialité --</option>
                                    ' . $options_specialites . '
                                </select>
                                
                                <label for="structure">Structure médicale :</label>
                                <select name="structure" id="structure" required>
                                    <option value="">-- Choisir une structure --</option>
                                    ' . $options_structures . '
                                </select>
                                
                                <label for="biographie">Biographie professionnelle :</label>
                                <textarea name="biographie" id="biographie" rows="3" placeholder="Décrivez votre parcours professionnel, vos spécialisations, votre expérience..."></textarea>
                            </div>
                            
                            <button type="submit">Valider</button>
                        </form>
                </div>
            </main>
        </body>
        ';
    }

    elseif ($type_compte_lower == 'secretaire') {
        // Get the available facility types
        $sql_types = "SELECT id, description FROM type_struct ORDER BY description ASC";
        $result_types = mysqli_query($conn, $sql_types);
        
        $options_types = '';
        if (mysqli_num_rows($result_types) > 0) {
            while ($row = mysqli_fetch_assoc($result_types)) {
                $options_types .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['description']) . '</option>';
            }
        }
        
        echo '
        <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Inscription Secrétaire</title>
                <link rel="stylesheet" href="../assets/css/variables.css">
                <link rel="stylesheet" href="../assets/css/global.css">
                <link rel="stylesheet" href="../assets/css/inscription.css">
            </head>
        <body>
            <main>        
                <div class="inscription-container">
                    <div class="inscription-header">
                        <h1>Bienvenue Secrétaire</h1>
                        <h3>Merci pour votre confiance ! Nous vous invitons à remplir les informations ci-dessous</h3>
                    </div>
                        <br>
                        <form class="inscription-form inscription-form--secretaire" action="traitement-inscription2.php" method="POST">
                        
                            <input type="hidden" name="user_id" value="' . $user_id . '">
                            
                            <div class="form-section">
                                <h4>Informations personnelles</h4>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <label for="date_naissance">Date de naissance :</label>
                                        <input type="date" name="date_naissance" id="date_naissance" required>
                                    </div>
                                    <div class="form-col">
                                        <label for="genre">Genre :</label>
                                        <select name="genre" id="genre" required>
                                            <option value="">-- Choisir --</option>
                                            <option value="homme">Homme</option>
                                            <option value="femme">Femme</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <label for="adresse">Adresse complète :</label>
                                <textarea name="adresse" id="adresse" rows="2" required></textarea>
                                
                                <label for="contact_urgence">Contact d\'urgence (téléphone) :</label>
                                <input type="tel" name="contact_urgence" id="contact_urgence" required>
                            </div>

                            <div class="form-section">
                                <h4>Structure médicale</h4>
                                
                                <label for="nom_etablissement">Nom de l\'établissement :</label>
                                <input type="text" name="nom_etablissement" id="nom_etablissement" required>
                                
                                <label for="type_etablissement">Type d\'établissement :</label>
                                <select name="type_etablissement" id="type_etablissement" required>
                                    <option value="">-- Choisir un type --</option>
                                    ' . $options_types . '
                                </select>
                                
                                <label for="adresse_etablissement">Adresse de l\'établissement :</label>
                                <textarea name="adresse_etablissement" id="adresse_etablissement" rows="2" required></textarea>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <label for="code_postal">Code postal :</label>
                                        <input type="text" name="code_postal" id="code_postal" placeholder="75001" required>
                                    </div>
                                    <div class="form-col">
                                        <label for="ville">Ville :</label>
                                        <input type="text" name="ville" id="ville" placeholder="Paris" required>
                                    </div>
                                </div>
                                
                                <label for="tel_etablissement">Téléphone :</label>
                                <input type="tel" name="tel_etablissement" id="tel_etablissement" required>
                            </div>
                            
                            <button type="submit">Valider</button>
                        </form>
                </div>
            </main>
        </body>
        ';
    }

    exit;
    
} else {
    mysqli_stmt_close($stmt_insert);
    echo 'Erreur lors de l\'inscription : ' . mysqli_error($conn) . '
        <br>
        <form action="inscription.php">
            <button type="submit">Retour à l\'inscription</button>
        </form>';
    exit;
}

mysqli_close($conn);

?>