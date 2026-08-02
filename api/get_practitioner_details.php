<?php
/**
 * API to get a practitioner's full details
 * Fixed version to match the actual DB structure
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../pages/config.php';

try {
    if (!isset($_GET['practitioner_id']) || empty($_GET['practitioner_id'])) {
        throw new Exception("L'identifiant du praticien est requis");
    }

    $practitionerId = intval($_GET['practitioner_id']);

    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Get the practitioner's info (WITHOUT the columns that don't exist)
    $sql = "
        SELECT 
            p.utilisateur_id as praticien_id,
            u.prenom,
            u.nom,
            u.email,
            u.telephone,
            p.numero_licence,
            p.biographie
        FROM praticiens p
        INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
        WHERE p.utilisateur_id = :practitioner_id
        AND u.actif = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['practitioner_id' => $practitionerId]);
    $practitioner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$practitioner) {
        throw new Exception("Praticien non trouvé");
    }

    // Get the specialties
    $sqlSpecialties = "
        SELECT sm.id, sm.nom
        FROM praticiens_specialites ps
        INNER JOIN specialites_medecine sm ON ps.specialite_id = sm.id
        WHERE ps.praticien_id = :practitioner_id
    ";

    $stmt = $pdo->prepare($sqlSpecialties);
    $stmt->execute(['practitioner_id' => $practitionerId]);
    $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$specialties) $specialties = [];

    // Get the facilities (WITHOUT code_postal, latitude, longitude, which don't exist)
    $sqlStructures = "
        SELECT 
            s.id,
            s.nom,
            s.adresse,
            s.telephone,
            ts.description as type
        FROM praticiens_structures ps
        INNER JOIN structures_sante s ON ps.structure_id = s.id
        LEFT JOIN type_struct ts ON s.type = ts.id
        WHERE ps.praticien_id = :practitioner_id
    ";

    $stmt = $pdo->prepare($sqlStructures);
    $stmt->execute(['practitioner_id' => $practitionerId]);
    $structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$structures) $structures = [];

    // Get the reviews
    $sqlReviews = "
        SELECT 
            a.id,
            a.evaluation,
            a.commentaire,
            a.cree_le,
            u.prenom as patient_prenom
        FROM avis a
        INNER JOIN patients pat ON a.patient_id = pat.utilisateur_id
        INNER JOIN utilisateurs u ON pat.utilisateur_id = u.id
        WHERE a.praticien_id = :practitioner_id
        ORDER BY a.cree_le DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sqlReviews);
    $stmt->execute(['practitioner_id' => $practitionerId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$reviews) $reviews = [];

    // Calculate the average rating
    $avgRating = null;
    if (count($reviews) > 0) {
        $totalRating = array_sum(array_column($reviews, 'evaluation'));
        $avgRating = round($totalRating / count($reviews), 1);
    }

    // Count the available slots (WITHOUT est_reserve and structure_id, which don't exist)
    $structuresWithSlots = [];
    foreach ($structures as $structure) {
        $sqlSlots = "
            SELECT COUNT(*) as count
            FROM creneaux_disponibilite
            WHERE praticien_id = :practitioner_id
            AND debut >= NOW()
        ";

        $stmt = $pdo->prepare($sqlSlots);
        $stmt->execute(['practitioner_id' => $practitionerId]);
        $slotsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $slotsCount = $slotsResult ? intval($slotsResult['count']) : 0;

        $structuresWithSlots[] = [
            'id' => intval($structure['id']),
            'name' => $structure['nom'],
            'type' => $structure['type'],
            'address' => $structure['adresse'],
            'phone' => $structure['telephone'],
            'available_slots' => $slotsCount
        ];
    }

    // Format the response
    $response = [
        'practitioner' => [
            'id' => intval($practitioner['praticien_id']),
            'firstname' => $practitioner['prenom'],
            'lastname' => $practitioner['nom'],
            'name' => $practitioner['prenom'] . ' ' . $practitioner['nom'],
            'email' => $practitioner['email'],
            'phone' => $practitioner['telephone'],
            'license' => $practitioner['numero_licence'],
            'biography' => $practitioner['biographie'],
            'specialties' => array_map(function($s) {
                return [
                    'id' => intval($s['id']),
                    'name' => $s['nom']
                ];
            }, $specialties),
            'structures' => $structuresWithSlots,
            'reviews' => [
                'average' => $avgRating,
                'count' => count($reviews),
                'list' => array_map(function($r) {
                    return [
                        'id' => intval($r['id']),
                        'rating' => intval($r['evaluation']),
                        'comment' => $r['commentaire'],
                        'date' => $r['cree_le'],
                        'patient_firstname' => $r['patient_prenom']
                    ];
                }, $reviews)
            ]
        ]
    ];

    sendSuccess($response);

} catch (Exception $e) {
    error_log("Erreur get_practitioner_details.php: " . $e->getMessage());
    sendError("Erreur : " . $e->getMessage());
}
?>