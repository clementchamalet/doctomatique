
<?php

require 'config.php';

// Vérifier que le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-login.php');
    exit;
}

$conn = get_db();
$email = trim($_POST['email']);
$password = $_POST['password'];

// Vérifier que les champs ne sont pas vides
if (empty($email) || empty($password)) {
    header('Location: admin-login.php?error=empty');
    exit;
}

// Requête préparée pour récupérer l'utilisateur
$sql = "SELECT * FROM utilisateurs WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    // Vérifier le mot de passe
    if (password_verify($password, $user['mot_de_passe'])) {
        
        // Vérifier que l'utilisateur a le rôle "admin"
        $sql_role = "SELECT r.nom FROM roles r INNER JOIN utilisateurs_roles ur ON r.id = ur.role_id WHERE ur.utilisateur_id = ? AND LOWER(r.nom) = 'admin'";
        $stmt_role = mysqli_prepare($conn, $sql_role);
        mysqli_stmt_bind_param($stmt_role, "i", $user['id']);
        mysqli_stmt_execute($stmt_role);
        $result_role = mysqli_stmt_get_result($stmt_role);
        
        if (mysqli_num_rows($result_role) > 0) {
            // L'utilisateur est bien un admin
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['is_admin'] = true;
            
            mysqli_stmt_close($stmt_role);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            
            // Redirection vers le dashboard admin
            header('Location: espace-admin.php');
            exit;
            
        } else {
            // L'utilisateur existe mais n'est pas admin
            mysqli_stmt_close($stmt_role);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            
            header('Location: admin-login.php?error=not_admin');
            exit;
        }
        
    } else {
        // Mot de passe incorrect
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        header('Location: admin-login.php?error=credentials');
        exit;
    }
} else {
    // Email introuvable
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    header('Location: admin-login.php?error=credentials');
    exit;
}

?>