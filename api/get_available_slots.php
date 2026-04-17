<?php
/**
 * API pour obtenir les creneaux disponibles d'un praticien - VERSION PROPRE
 * Endpoint: /api/get_available_slots.php
 * Methode: GET
 * Parametres:
 *  - practitioner_id: ID du praticien (requis)
 *  - structure_id: ID de la structure (requis)
 *  - date_start: Date de debut (optionnel, format: YYYY-MM-DD)
 *  - date_end: Date de fin (optionnel, format: YYYY-MM-DD)
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Verification des parametres requis
if (!isset($_GET['practitioner_id']) || empty($_GET['practitioner_id'])) {
    sendError("L'identifiant du praticien est requis");
}

if (!isset($_GET['structure_id']) || empty($_GET['structure_id'])) {
    sendError("L'identifiant de la structure est requis");
}

$practitionerId = intval($_GET['practitioner_id']);
$structureId = intval($_GET['structure_id']);

// Gestion des dates
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d');
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d', strtotime('+14 days'));

// Validation du format des dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
    sendError("Format de date invalide. Utilisez le format YYYY-MM-DD");
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion a la base de donnees");
    }

    // Verification de l'existence du praticien et de la structure
    $sqlCheck = "
        SELECT 1
        FROM praticiens_structures
        WHERE praticien_id = :practitioner_id
        AND structure_id = :structure_id
    ";

    $stmt = $pdo->prepare($sqlCheck);
    $stmt->execute([
        'practitioner_id' => $practitionerId,
        'structure_id' => $structureId
    ]);

    if (!$stmt->fetch()) {
        sendError("Cette combinaison praticien/structure n'existe pas", 404);
    }

    // Recuperation des creneaux disponibles
    $sqlSlots = "
        SELECT 
            cd.id,
            cd.debut,
            cd.duree_minute,
            DATE(cd.debut) as date_creneau,
            TIME(cd.debut) as heure_debut
        FROM creneaux_disponibilite cd
        WHERE cd.praticien_id = :practitioner_id
        AND cd.structure_id = :structure_id
        AND cd.est_reserve = 0
        AND DATE(cd.debut) BETWEEN :date_start AND :date_end
        AND cd.debut >= NOW()
        ORDER BY cd.debut ASC
    ";

    $stmt = $pdo->prepare($sqlSlots);
    $stmt->execute([
        'practitioner_id' => $practitionerId,
        'structure_id' => $structureId,
        'date_start' => $dateStart,
        'date_end' => $dateEnd
    ]);

    $slots = $stmt->fetchAll();

    // Groupement des creneaux par date
    $slotsByDate = [];
    foreach ($slots as $slot) {
        $date = $slot['date_creneau'];
        
        if (!isset($slotsByDate[$date])) {
            // Informations sur la date
            $dateObj = new DateTime($date);
            $dayNameEn = $dateObj->format('l');
            $monthNameEn = $dateObj->format('F');
            
            $slotsByDate[$date] = [
                'date' => $date,
                'day_name' => $dayNameEn,
                'day_name_fr' => translateDayToFrench($dayNameEn),
                'day_number' => intval($dateObj->format('d')),
                'month_name' => $monthNameEn,
                'month_name_fr' => translateMonthToFrench($monthNameEn),
                'year' => intval($dateObj->format('Y')),
                'is_today' => ($date === date('Y-m-d')),
                'is_tomorrow' => ($date === date('Y-m-d', strtotime('+1 day'))),
                'slots' => []
            ];
        }

        // Calcul de l'heure de fin
        $startTime = new DateTime($slot['debut']);
        $endTime = clone $startTime;
        $endTime->add(new DateInterval('PT' . $slot['duree_minute'] . 'M'));

        $slotsByDate[$date]['slots'][] = [
            'id' => intval($slot['id']),
            'start' => $slot['debut'],
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration_minutes' => intval($slot['duree_minute'])
        ];
    }

    // Conversion en tableau indexe
    $slotsArray = array_values($slotsByDate);

    // Recuperation des informations du praticien et de la structure
    $sqlInfo = "
        SELECT 
            u.prenom,
            u.nom,
            s.nom as structure_nom,
            s.adresse,
            s.ville
        FROM praticiens p
        INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
        CROSS JOIN structures_sante s
        WHERE p.utilisateur_id = :practitioner_id
        AND s.id = :structure_id
    ";

    $stmt = $pdo->prepare($sqlInfo);
    $stmt->execute([
        'practitioner_id' => $practitionerId,
        'structure_id' => $structureId
    ]);
    $info = $stmt->fetch();

    if (!$info) {
        sendError("Praticien ou structure non trouve", 404);
    }

    sendSuccess([
        'practitioner' => [
            'id' => $practitionerId,
            'name' => $info['prenom'] . ' ' . $info['nom']
        ],
        'structure' => [
            'id' => $structureId,
            'name' => $info['structure_nom'],
            'address' => $info['adresse'],
            'city' => $info['ville']
        ],
        'period' => [
            'start' => $dateStart,
            'end' => $dateEnd
        ],
        'total_slots' => count($slots),
        'slots_by_date' => $slotsArray
    ]);

} catch (Exception $e) {
    error_log("Erreur dans get_available_slots.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de la recuperation des creneaux", 500);
}
?>