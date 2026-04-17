<?php

//Mini script pour check si le utilisateur est connecté

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
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


//Si l'utilisateur est connecté, vous pouvez utilisez la variable : $_SESSION['role'], elle stocke le rôle de la personne 
//                                                                              -> "praticien" si c'est un docteur qui se co
//                                                                              -> "secretaire" si c'est un secretaire qui se co
//                                                                              -> "patient" si c'est ni un docteur ni un secretaire

?>