<?php 

require 'config.php' ;

        // Ensure the form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: auth.php');
            exit;
        }

        $conn     = get_db(); //connection variable from config.php
        $user_email = $_POST['user_email'];  //Variables from the login page
        $password = $_POST['password']; //Variables from the login page


        // protection against SQL injection
        $sql = "SELECT * FROM utilisateurs WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $user_email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

        // USE password_verify for hashed passwords
        // Note: your passwords should be hashed with password_hash()

        if (password_verify($password, $user['mot_de_passe'])) {

            // Regenerate the session ID in case the person switches accounts
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];                  ////////////SESSION VARIABLES
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];

            // Get the user's roles
            $sql_roles = "SELECT r.nom FROM roles r INNER JOIN utilisateurs_roles ur ON r.id = ur.role_id WHERE ur.utilisateur_id = ?";
            $stmt_roles = mysqli_prepare($conn, $sql_roles);
            mysqli_stmt_bind_param($stmt_roles, "i", $user['id']);
            mysqli_stmt_execute($stmt_roles);
            $result_roles = mysqli_stmt_get_result($stmt_roles);
            
            $roles = [];
            while ($row = mysqli_fetch_assoc($result_roles)) {
                $roles[] = strtolower($row['nom']);
            }
            mysqli_stmt_close($stmt_roles);
            
            // Determine the primary role
            if (in_array('praticien', $roles)) {
                $_SESSION['role'] = 'praticien';
            } elseif (in_array('secretaire', $roles)) {               ////////////ROLE SESSION VARIABLES
                $_SESSION['role'] = 'secretaire';
            } else {
                $_SESSION['role'] = 'patient';
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            echo '
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Inscription réussie</title>
                <link rel="stylesheet" href="../assets/css/variables.css">
                <link rel="stylesheet" href="../assets/css/global.css">
                <link rel="stylesheet" href="../assets/css/inscription.css">
            </head>
            <body>
                <main>
                    <div class="succes-container">
                        <h3>Vous êtes bien identifié !</h3>
                        <br>
                        <f>
                        <a href="../index.php" class="btn-home">Page accueil</a>
                        </f
                    </div>
                </main>
            </body>
            </html>
            ';

            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            $error = "Utilisateur introuvable";
        }

        // If there's an error, redirect to login with a message
        if (isset($error)) {
            echo '
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Inscription réussie</title>
                <link rel="stylesheet" href="../assets/css/variables.css">
                <link rel="stylesheet" href="../assets/css/global.css">
                <link rel="stylesheet" href="../assets/css/inscription.css">
            </head>
            <body>
                <main>
                    <div class="succes-container">
                        <h1>' . $error .'</h1>
                        <p>Veuillez retenter de vous connecter</p>
                        
                        <form action="auth.php">
                            <button type="submit"> Se connecter </button>
                        </form>
                    </div>
                </main>
            </body>
            </html>
            ' ;
        exit;
}

?>