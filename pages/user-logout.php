<?php
session_start();

// Destroy all user session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_email']);
unset($_SESSION['user_prenom']);
unset($_SESSION['user_nom']);
unset($_SESSION['role']);

session_destroy();

// Redirect to the login page
header('Location: auth.php');
exit;
?>