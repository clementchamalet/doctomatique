<?php
session_start();

// Détruire toutes les variables de session admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_prenom']);
unset($_SESSION['admin_nom']);
unset($_SESSION['is_admin']);

session_destroy();

// Rediriger vers la page de connexion admin
header('Location: admin-login.php');
exit;
?>