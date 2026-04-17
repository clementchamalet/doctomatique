<?php
/**
 * API pour obtenir la liste des specialites medicales
 * Endpoint: /api/get_specialties.php
 * Methode: GET
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion a la base de donnees");
    }

    $sql = "SELECT id, nom, description FROM specialites_medecine ORDER BY nom ASC";
    $stmt = $pdo->query($sql);
    
    if (!$stmt) {
        throw new Exception("Erreur lors de la requete SQL");
    }
    
    $specialties = $stmt->fetchAll();

    $formattedSpecialties = array_map(function($spec) {
        return [
            'id' => intval($spec['id']),
            'name' => $spec['nom'],
            'description' => $spec['description']
        ];
    }, $specialties);

    sendSuccess(['specialties' => $formattedSpecialties]);

} catch (Exception $e) {
    error_log("Erreur dans get_specialties.php: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
?>