
<?php

require 'config.php';

// Ensure the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-login.php');
    exit;
}

$conn = get_db();
$email = trim($_POST['email']);
$password = $_POST['password'];

// Check that the fields aren't empty
if (empty($email) || empty($password)) {
    header('Location: admin-login.php?error=empty');
    exit;
}

// Prepared statement to get the user
$sql = "SELECT * FROM utilisateurs WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    // Check the password
    if (password_verify($password, $user['mot_de_passe'])) {
        
        // Check that the user has the "admin" role
        $sql_role = "SELECT r.nom FROM roles r INNER JOIN utilisateurs_roles ur ON r.id = ur.role_id WHERE ur.utilisateur_id = ? AND LOWER(r.nom) = 'admin'";
        $stmt_role = mysqli_prepare($conn, $sql_role);
        mysqli_stmt_bind_param($stmt_role, "i", $user['id']);
        mysqli_stmt_execute($stmt_role);
        $result_role = mysqli_stmt_get_result($stmt_role);
        
        if (mysqli_num_rows($result_role) > 0) {
            // The user is indeed an admin
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['is_admin'] = true;
            
            mysqli_stmt_close($stmt_role);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            
            // Redirect to the admin dashboard
            header('Location: espace-admin.php');
            exit;
            
        } else {
            // The user exists but isn't an admin
            mysqli_stmt_close($stmt_role);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            
            header('Location: admin-login.php?error=not_admin');
            exit;
        }
        
    } else {
        // Incorrect password
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        header('Location: admin-login.php?error=credentials');
        exit;
    }
} else {
    // Email not found
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    header('Location: admin-login.php?error=credentials');
    exit;
}

?>