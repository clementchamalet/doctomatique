<?php

require 'config.php';
require 'check-connexion.php';

$connexion = get_db();

header('Content-Type: application/json');

// Vérifier que c'est un praticien
$stmt = mysqli_prepare($connexion, "SELECT 1 FROM praticiens WHERE utilisateur_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}

// Récupérer toutes les structures
$query = "SELECT s.id, s.nom, t.description as type
          FROM structures_sante s
          LEFT JOIN type_struct t ON s.type = t.id
          ORDER BY s.nom";

$result = mysqli_query($connexion, $query);

$structures = [];
while ($row = mysqli_fetch_assoc($result)) {
    $structures[] = [
        'id' => (int)$row['id'],
        'nom' => $row['nom'],
        'type' => $row['type']
    ];
}

echo json_encode($structures);