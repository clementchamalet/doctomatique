<?php
session_start();

// Détruire toutes les variables de session admin
unset($_SESSION['user_id']);
unset($_SESSION['user_email']);
unset($_SESSION['user_prenom']);
unset($_SESSION['user_nom']);
unset($_SESSION['role']);

session_destroy();

// Rediriger vers la page de connexion admin
header('Location: auth.php');
exit;
?>