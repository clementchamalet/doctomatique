<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

$currentPage = basename($_SERVER['PHP_SELF']);
$pagesDisponibilites = [
    'disponibilites.php',
    'nouvelle_disponibilite.php',
    'modifier_disponibilites.php',
    'edit_disponibilite.php',
    'delete_disponibilite.php'
];

if (!isset($_SESSION['user_id'], $_GET['id'])) {
    header('Location: agenda.php');
    exit;
}

$stmt = mysqli_prepare(
    $connexion,
    "DELETE c FROM creneaux_disponibilite c
     LEFT JOIN rendezvous r ON r.creneaux_id = c.id
     WHERE c.id = ?
     AND c.praticien_id = ?
     AND r.id IS NULL"
);

mysqli_stmt_bind_param($stmt, "ii", $_GET['id'], $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

header('Location: modifier_disponibilites.php');
exit;
