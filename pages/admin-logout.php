<?php
session_start();

// Destroy all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_prenom']);
unset($_SESSION['admin_nom']);
unset($_SESSION['is_admin']);

session_destroy();

// Redirect to the admin login page
header('Location: admin-login.php');
exit;
?>