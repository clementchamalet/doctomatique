<?php

//Mini script to check whether the user is logged in

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '

    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/inscription.css">
</head>
<body>
    <main>
        <div class="auth-box">
            <h2>Accès restreint</h2>
            <br>
            <p>
                Vous devez être connecté pour accéder à cette page.
            </p>
            <br>
            <a href="auth.php" class="btn-submit">Se connecter</a>
        </div>
    </main>
</body>
    ';
    exit;
}


//If the user is logged in, you can use the $_SESSION['role'] variable, which stores their role:
//                                                                              -> "praticien" if it's a doctor logged in
//                                                                              -> "secretaire" if it's a secretary logged in
//                                                                              -> "patient" if neither a doctor nor a secretary

?>