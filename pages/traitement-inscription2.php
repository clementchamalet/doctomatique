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
$user_id = $_POST['user_id'];
$date_naissance = $_POST['date_naissance'];
$genre_description = $_POST['genre']; // Receives 'homme', 'femme', or 'autre'
$adresse = trim($_POST['adresse']);
$contact_urgence = trim($_POST['contact_urgence']);

// Validate common data
$erreurs = [];

if (empty($user_id) || empty($date_naissance) || empty($genre_description) || empty($adresse) || empty($contact_urgence)) {
    $erreurs[] = "Tous les champs sont obligatoires";
}

// Check that the user exists
$sql_check = "SELECT id FROM utilisateurs WHERE id = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "i", $user_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    $erreurs[] = "Utilisateur introuvable";
}
mysqli_stmt_close($stmt_check);

if (!empty($erreurs)) {
    echo '<p>' . implode(", ", $erreurs) . '</p>
    <form action="inscription.php">
        <button type="submit">Retour a l\'inscription</button>
    </form>';
    exit;
}

// Map form values to DB values
$genre_map = [
    'homme' => 'M',
    'femme' => 'F',
    'autre' => 'Autre'
];

$genre_db_value = $genre_map[strtolower($genre_description)] ?? $genre_description;

// Get the gender ID from the 'genre' table
$sql_genre = "SELECT id FROM genre WHERE LOWER(description) = LOWER(?)";
$stmt_genre = mysqli_prepare($conn, $sql_genre);
mysqli_stmt_bind_param($stmt_genre, "s", $genre_db_value);
mysqli_stmt_execute($stmt_genre);
$result_genre = mysqli_stmt_get_result($stmt_genre);

if (mysqli_num_rows($result_genre) > 0) {
    $genre_row = mysqli_fetch_assoc($result_genre);
    $genre_id = $genre_row['id'];
} else {
    echo '<p>Genre invalide. Valeur recue : ' . htmlspecialchars($genre_description) . ' (mappee vers : ' . htmlspecialchars($genre_db_value) . ')</p>
    <form action="inscription.php">
        <button type="submit">Retour a l\'inscription</button>
    </form>';
    exit;
}
mysqli_stmt_close($stmt_genre);

// Insert into the patients table (everyone is a patient)
$sql_insert_patient = "INSERT INTO patients (utilisateur_id, date_naissance, genre, adresse, contact_urgence) VALUES (?, ?, ?, ?, ?)";
$stmt_patient = mysqli_prepare($conn, $sql_insert_patient);
mysqli_stmt_bind_param($stmt_patient, "isiss", $user_id, $date_naissance, $genre_id, $adresse, $contact_urgence);

if (!mysqli_stmt_execute($stmt_patient)) {
    echo '<p>Erreur lors de l\'insertion dans patients : ' . mysqli_error($conn) . '</p>';
    exit;
}
mysqli_stmt_close($stmt_patient);

// Get the user's role(s)
$sql_roles = "SELECT r.nom FROM roles r INNER JOIN utilisateurs_roles ur ON r.id = ur.role_id WHERE ur.utilisateur_id = ?";
$stmt_roles = mysqli_prepare($conn, $sql_roles);
mysqli_stmt_bind_param($stmt_roles, "i", $user_id);
mysqli_stmt_execute($stmt_roles);
$result_roles = mysqli_stmt_get_result($stmt_roles);

$roles = [];
while ($row = mysqli_fetch_assoc($result_roles)) {
    $roles[] = strtolower($row['nom']);
}
mysqli_stmt_close($stmt_roles);

// If the user is a practitioner
if (in_array('praticien', $roles)) {
    $numero_licence = isset($_POST['numero_licence']) ? trim($_POST['numero_licence']) : null;
    $biographie = isset($_POST['biographie']) ? trim($_POST['biographie']) : null;
    $specialite_id = isset($_POST['specialite']) ? intval($_POST['specialite']) : null;
    $structure_id = isset($_POST['structure']) ? intval($_POST['structure']) : null;
    
    // Validation: the license number is required
    if (!$numero_licence) {
        echo '<p>Le numéro de licence est obligatoire pour un praticien</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    
    // Validation: the specialty is required
    if (!$specialite_id) {
        echo '<p>La spécialité médicale est obligatoire pour un praticien</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    
    // Validation: the facility is required
    if (!$structure_id) {
        echo '<p>La structure médicale est obligatoire pour un praticien</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    
    // Check that the specialty exists in the DB
    $sql_check_specialite = "SELECT id FROM specialites_medecine WHERE id = ?";
    $stmt_check_specialite = mysqli_prepare($conn, $sql_check_specialite);
    mysqli_stmt_bind_param($stmt_check_specialite, "i", $specialite_id);
    mysqli_stmt_execute($stmt_check_specialite);
    $result_check_specialite = mysqli_stmt_get_result($stmt_check_specialite);
    
    if (mysqli_num_rows($result_check_specialite) == 0) {
        echo '<p>La spécialité sélectionnée n\'existe pas</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    mysqli_stmt_close($stmt_check_specialite);
    
    // Check that the facility exists in the DB
    $sql_check_structure = "SELECT id FROM structures_sante WHERE id = ?";
    $stmt_check_structure = mysqli_prepare($conn, $sql_check_structure);
    mysqli_stmt_bind_param($stmt_check_structure, "i", $structure_id);
    mysqli_stmt_execute($stmt_check_structure);
    $result_check_structure = mysqli_stmt_get_result($stmt_check_structure);
    
    if (mysqli_num_rows($result_check_structure) == 0) {
        echo '<p>La structure médicale sélectionnée n\'existe pas</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    mysqli_stmt_close($stmt_check_structure);
    
    // Insert into the praticiens table
    $sql_insert_praticien = "INSERT INTO praticiens (utilisateur_id, numero_licence, biographie) VALUES (?, ?, ?)";
    $stmt_praticien = mysqli_prepare($conn, $sql_insert_praticien);
    mysqli_stmt_bind_param($stmt_praticien, "iss", $user_id, $numero_licence, $biographie);
    
    if (!mysqli_stmt_execute($stmt_praticien)) {
        echo '<p>Erreur lors de l\'insertion dans praticiens : ' . mysqli_error($conn) . '</p>';
        exit;
    }
    mysqli_stmt_close($stmt_praticien);
    
    // Insert the practitioner-specialty association into praticiens_specialites
    $sql_insert_specialite = "INSERT INTO praticiens_specialites (praticien_id, specialite_id) VALUES (?, ?)";
    $stmt_specialite = mysqli_prepare($conn, $sql_insert_specialite);
    mysqli_stmt_bind_param($stmt_specialite, "ii", $user_id, $specialite_id);
    
    if (!mysqli_stmt_execute($stmt_specialite)) {
        echo '<p>Erreur lors de l\'insertion de la spécialité : ' . mysqli_error($conn) . '</p>';
        exit;
    }
    mysqli_stmt_close($stmt_specialite);
    
    // Insert the practitioner-facility association into praticiens_structures
    $sql_insert_structure = "INSERT INTO praticiens_structures (praticien_id, structure_id) VALUES (?, ?)";
    $stmt_insert_structure = mysqli_prepare($conn, $sql_insert_structure);
    mysqli_stmt_bind_param($stmt_insert_structure, "ii", $user_id, $structure_id);
    
    if (!mysqli_stmt_execute($stmt_insert_structure)) {
        echo '<p>Erreur lors de l\'insertion de la structure : ' . mysqli_error($conn) . '</p>';
        exit;
    }
    mysqli_stmt_close($stmt_insert_structure);
}

// If the user is a secretary
if (in_array('secretaire', $roles)) {
    $nom_etablissement = isset($_POST['nom_etablissement']) ? trim($_POST['nom_etablissement']) : null;
    $adresse_etablissement = isset($_POST['adresse_etablissement']) ? trim($_POST['adresse_etablissement']) : null;
    $code_postal = isset($_POST['code_postal']) ? trim($_POST['code_postal']) : null;
    $ville = isset($_POST['ville']) ? trim($_POST['ville']) : null;
    $type_etablissement_id = isset($_POST['type_etablissement']) ? intval($_POST['type_etablissement']) : null;
    $tel_etablissement = isset($_POST['tel_etablissement']) ? trim($_POST['tel_etablissement']) : null;
    
    // Validate required fields
    if (!$nom_etablissement || !$adresse_etablissement || !$code_postal || !$ville || !$type_etablissement_id || !$tel_etablissement) {
        echo '<p>Tous les champs de l\'établissement sont obligatoires</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    
    // Check that the facility type exists in the DB
    $sql_check_type = "SELECT id FROM type_struct WHERE id = ?";
    $stmt_check_type = mysqli_prepare($conn, $sql_check_type);
    mysqli_stmt_bind_param($stmt_check_type, "i", $type_etablissement_id);
    mysqli_stmt_execute($stmt_check_type);
    $result_check_type = mysqli_stmt_get_result($stmt_check_type);
    
    if (mysqli_num_rows($result_check_type) == 0) {
        echo '<p>Le type d\'établissement sélectionné n\'existe pas</p>
        <form action="inscription.php">
            <button type="submit">Retour a l\'inscription</button>
        </form>';
        exit;
    }
    mysqli_stmt_close($stmt_check_type);
    
    // Insert the healthcare facility with all fields
    $sql_insert_structure = "INSERT INTO structures_sante (nom, type, adresse, code_postal, ville, telephone, secretaire_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_structure = mysqli_prepare($conn, $sql_insert_structure);
    mysqli_stmt_bind_param($stmt_structure, "sisissi", $nom_etablissement, $type_etablissement_id, $adresse_etablissement, $code_postal, $ville, $tel_etablissement, $user_id);
    
    if (!mysqli_stmt_execute($stmt_structure)) {
        echo '<p>Erreur lors de l\'insertion de la structure de sante : ' . mysqli_error($conn) . '</p>';
        exit;
    }
    mysqli_stmt_close($stmt_structure);
}

mysqli_close($conn);

// Show the success message and redirect to login
echo '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription reussie</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/inscription.css">
</head>
<body>
    <main>
        <div class="succes-container">
            <div class="succes-icon">🎉</div>
            <h1>Inscription finalisée avec succès !</h1>
            <p>Votre compte a été créé et toutes vos informations ont été enregistrées.</p>
            <p>Vous pouvez maintenant vous connecter à votre espace personnel.</p>
            
            <form action="auth.php" method="GET">
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </main>
</body>
</html>
';

?>