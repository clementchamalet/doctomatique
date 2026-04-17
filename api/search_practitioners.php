<?php
/**
 * API de recherche de praticiens - VERSION COMPLÈTE AVEC LOCALISATION (CORRIGÉE)
 * Endpoint: /api/search_practitioners.php
 * Methode: GET
 * Parametres:
 *  - search: Texte de recherche (nom, prenom, specialite)
 *  - specialty: ID de la specialite (optionnel)
 *  - user_lat: Latitude utilisateur (optionnel, pour calcul distance)
 *  - user_lon: Longitude utilisateur (optionnel, pour calcul distance)
 *  - max_distance: Distance maximale en km (optionnel, filtre par distance)
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Récupérer les paramètres
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $specialty = isset($_GET['specialty']) ? intval($_GET['specialty']) : null;
    $userLat = isset($_GET['user_lat']) ? floatval($_GET['user_lat']) : null;
    $userLon = isset($_GET['user_lon']) ? floatval($_GET['user_lon']) : null;
    $maxDistance = isset($_GET['max_distance']) ? floatval($_GET['max_distance']) : null;

    // Requête SQL pour obtenir les praticiens avec leurs structures
    $sql = "
        SELECT DISTINCT
            p.utilisateur_id,
            u.prenom,
            u.nom,
            u.email,
            p.numero_licence,
            p.biographie,
            p.actif,
            GROUP_CONCAT(DISTINCT sm.nom SEPARATOR ', ') as specialites_list,
            GROUP_CONCAT(DISTINCT sm.id SEPARATOR ',') as specialite_ids,
            ps.structure_id,
            s.id as structure_id_check,
            s.nom as structure_nom,
            s.adresse,
            s.code_postal,
            s.ville,
            s.telephone,
            s.latitude,
            s.longitude,
            COUNT(DISTINCT av.id) as review_count,
            ROUND(AVG(DISTINCT av.evaluation), 1) as review_average
        FROM praticiens p
        INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
        LEFT JOIN praticiens_specialites psp ON p.utilisateur_id = psp.praticien_id
        LEFT JOIN specialites_medecine sm ON psp.specialite_id = sm.id
        LEFT JOIN praticiens_structures ps ON p.utilisateur_id = ps.praticien_id
        LEFT JOIN structures_sante s ON ps.structure_id = s.id
        LEFT JOIN avis av ON p.utilisateur_id = av.praticien_id
        WHERE u.actif = 1 AND p.actif = 1
    ";

    $params = [];

    // Filtre par recherche texte
    if (!empty($search)) {
        $sql .= " AND (
            CONCAT(u.prenom, ' ', u.nom) LIKE ? 
            OR p.biographie LIKE ? 
            OR sm.nom LIKE ?
            OR s.nom LIKE ?
        )";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    // Filtre par spécialité
    if ($specialty) {
        $sql .= " AND sm.id = ?";
        $params[] = $specialty;
    }

    $sql .= " GROUP BY p.utilisateur_id, ps.structure_id ORDER BY u.nom ASC, s.nom ASC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Traiter les résultats
    $practitioners = [];
    $practitionerMap = [];

    foreach ($results as $row) {
        $practId = intval($row['utilisateur_id']);
        
        // Créer la structure si elle existe
        $structure = null;
        if ($row['structure_id'] && $row['latitude'] && $row['longitude']) {
            $distance = null;
            
            // Calculer la distance si geoloc utilisateur fournie
            if ($userLat !== null && $userLon !== null) {
                $distance = calculateDistance(
                    $userLat, 
                    $userLon, 
                    floatval($row['latitude']), 
                    floatval($row['longitude'])
                );
                
                // Filtrer par distance max si spécifié
                if ($maxDistance !== null && $distance > $maxDistance) {
                    continue;
                }
            }
            
            $structure = [
                'id' => intval($row['structure_id']),
                'name' => $row['structure_nom'],
                'address' => $row['adresse'],
                'postal_code' => $row['code_postal'],
                'city' => $row['ville'],
                'phone' => $row['telephone'],
                'latitude' => floatval($row['latitude']),
                'longitude' => floatval($row['longitude']),
                'distance_km' => $distance
            ];
        }

        // Vérifier si ce praticien est déjà dans la map
        if (!isset($practitionerMap[$practId])) {
            $specialties = [];
            if ($row['specialites_list']) {
                $specNames = explode(', ', $row['specialites_list']);
                $specIds = explode(',', $row['specialite_ids']);
                foreach ($specNames as $idx => $name) {
                    if (trim($name)) {
                        $specialties[] = [
                            'id' => intval($specIds[$idx] ?? 0),
                            'name' => trim($name)
                        ];
                    }
                }
            }

            $practitionerMap[$practId] = [
                'id' => $practId,
                'name' => trim($row['prenom'] . ' ' . $row['nom']),
                'firstname' => $row['prenom'],
                'lastname' => $row['nom'],
                'email' => $row['email'],
                'license' => $row['numero_licence'],
                'biography' => $row['biographie'],
                'specialties' => $specialties,
                'structures' => [],
                'reviews' => [
                    'average' => $row['review_average'] ? floatval($row['review_average']) : null,
                    'count' => intval($row['review_count'] ?? 0)
                ]
            ];
        }

        // Ajouter la structure si elle existe et n'est pas en doublon
        if ($structure) {
            $isDuplicate = false;
            foreach ($practitionerMap[$practId]['structures'] as $existingStruct) {
                if ($existingStruct['id'] === $structure['id']) {
                    $isDuplicate = true;
                    break;
                }
            }
            if (!$isDuplicate) {
                $practitionerMap[$practId]['structures'][] = $structure;
            }
        }
    }

    // Convertir en array indexé
    $practitioners = array_values($practitionerMap);

    // Trier par distance (si distance calculée)
    if ($userLat !== null && $userLon !== null) {
        usort($practitioners, function($a, $b) {
            $distA = PHP_INT_MAX;
            $distB = PHP_INT_MAX;
            
            if (!empty($a['structures']) && $a['structures'][0]['distance_km'] !== null) {
                $distA = $a['structures'][0]['distance_km'];
            }
            if (!empty($b['structures']) && $b['structures'][0]['distance_km'] !== null) {
                $distB = $b['structures'][0]['distance_km'];
            }
            
            return $distA <=> $distB;
        });
    }

    sendSuccess([
        'practitioners' => $practitioners,
        'total' => count($practitioners),
        'filters_applied' => [
            'search' => !empty($search),
            'specialty' => $specialty !== null,
            'distance' => $maxDistance !== null,
            'user_location' => $userLat !== null && $userLon !== null
        ]
    ]);

} catch (Exception $e) {
    error_log("Erreur search_practitioners: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
?>