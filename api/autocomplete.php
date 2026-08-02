<?php
/**
 * Autocomplete API - Gets cities and specialties
 * Endpoint: /api/autocomplete.php
 * Method: GET
 * Parameters:
 *  - type: 'cities' or 'specialties'
 *  - query: Search text
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion");
    }

    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if ($type === 'cities') {
        // Get the cities
        $sql = "
            SELECT DISTINCT s.ville
            FROM structures_sante s
            WHERE s.ville IS NOT NULL AND s.ville != ''
        ";
        
        $params = [];
        if (!empty($query)) {
            $sql .= " AND s.ville LIKE ?";
            $params[] = $query . '%';
        }
        
        $sql .= " ORDER BY s.ville ASC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        $cities = array_map(function($row) {
            return $row['ville'];
        }, $results);
        
        sendSuccess(['results' => $cities]);
    } 
    else if ($type === 'specialties') {
        // Get the specialties
        $sql = "
            SELECT DISTINCT sm.id, sm.nom
            FROM specialites_medecine sm
            WHERE sm.nom IS NOT NULL AND sm.nom != ''
        ";
        
        $params = [];
        if (!empty($query)) {
            $sql .= " AND sm.nom LIKE ?";
            $params[] = $query . '%';
        }
        
        $sql .= " ORDER BY sm.nom ASC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        sendSuccess(['results' => $results]);
    }
    else {
        sendError("Type invalide. Utilisez 'cities' ou 'specialties'", 400);
    }

} catch (Exception $e) {
    error_log("Erreur autocomplete: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
?>
