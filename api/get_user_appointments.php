<?php
/**
 * API to get a patient's appointments - CLEAN VERSION
 * Endpoint: /api/get_user_appointments.php
 * Method: GET
 * Authentication: Required (logged-in patient)
 * Parameters:
 *  - status: Filter by status (optional: 'upcoming', 'past', 'cancelled')
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Check authentication
if (!isLoggedIn()) {
    sendError("Vous devez etre connecte pour consulter vos rendez-vous", 401);
}

if (!isPatient()) {
    sendError("Acces refuse", 403);
}

$patientId = intval($_SESSION['user_id']);
$statusFilter = isset($_GET['status']) ? cleanString($_GET['status']) : 'all';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion a la base de donnees");
    }

    // Build the base query
    $sql = "
        SELECT 
            r.id as appointment_id,
            r.cree_le,
            r.notes_patient,
            cd.debut,
            cd.duree_minute,
            u.prenom as praticien_prenom,
            u.nom as praticien_nom,
            s.id as structure_id,
            s.nom as structure_nom,
            s.adresse,
            s.code_postal,
            s.ville,
            s.telephone,
            st.description as statut,
            m.description as motif,
            r.statut as statut_id,
            GROUP_CONCAT(DISTINCT sm.nom SEPARATOR ', ') as specialites
        FROM rendezvous r
        INNER JOIN creneaux_disponibilite cd ON r.creneaux_id = cd.id
        INNER JOIN praticiens p ON r.praticien_id = p.utilisateur_id
        INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
        INNER JOIN structures_sante s ON r.structure_id = s.id
        INNER JOIN statut st ON r.statut = st.id
        INNER JOIN motif m ON r.motif = m.id
        LEFT JOIN praticiens_specialites ps ON p.utilisateur_id = ps.praticien_id
        LEFT JOIN specialites_medecine sm ON ps.specialite_id = sm.id
        WHERE r.patient_id = :patient_id
    ";

    $params = ['patient_id' => $patientId];

    // Filter by status
    switch ($statusFilter) {
        case 'upcoming':
            $sql .= " AND cd.debut >= NOW() AND r.statut IN (" . APPOINTMENT_STATUS_PENDING . ", " . APPOINTMENT_STATUS_CONFIRMED . ")";
            break;
        case 'past':
            $sql .= " AND (cd.debut < NOW() OR r.statut = " . APPOINTMENT_STATUS_COMPLETED . ")";
            break;
        case 'cancelled':
            $sql .= " AND r.statut = " . APPOINTMENT_STATUS_CANCELLED;
            break;
    }

    $sql .= " GROUP BY r.id ORDER BY cd.debut DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    // Format the results
    $formattedAppointments = [];
    $now = new DateTime();

    foreach ($appointments as $apt) {
        $startTime = new DateTime($apt['debut']);
        $endTime = clone $startTime;
        $endTime->add(new DateInterval('PT' . $apt['duree_minute'] . 'M'));

        $isPast = $startTime < $now;
        $isToday = $startTime->format('Y-m-d') === $now->format('Y-m-d');
        
        $tomorrow = clone $now;
        $isTomorrow = $startTime->format('Y-m-d') === $tomorrow->modify('+1 day')->format('Y-m-d');

        $formattedAppointments[] = [
            'id' => intval($apt['appointment_id']),
            'created_at' => $apt['cree_le'],
            'datetime' => $apt['debut'],
            'date' => $startTime->format('Y-m-d'),
            'date_formatted' => $startTime->format('d/m/Y'),
            'day_name' => translateDayToFrench($startTime->format('l')),
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration_minutes' => intval($apt['duree_minute']),
            'is_past' => $isPast,
            'is_today' => $isToday,
            'is_tomorrow' => $isTomorrow,
            'practitioner' => [
                'name' => $apt['praticien_prenom'] . ' ' . $apt['praticien_nom'],
                'firstname' => $apt['praticien_prenom'],
                'lastname' => $apt['praticien_nom'],
                'specialties' => $apt['specialites']
            ],
            'structure' => [
                'id' => intval($apt['structure_id']),
                'name' => $apt['structure_nom'],
                'address' => $apt['adresse'],
                'postal_code' => $apt['code_postal'],
                'city' => $apt['ville'],
                'phone' => $apt['telephone']
            ],
            'status' => $apt['statut'],
            'status_id' => intval($apt['statut_id']),
            'reason' => $apt['motif'],
            'notes' => $apt['notes_patient'],
            'can_cancel' => !$isPast && $apt['statut_id'] != APPOINTMENT_STATUS_CANCELLED
        ];
    }

    // Statistics
    $stats = [
        'total' => count($formattedAppointments),
        'upcoming' => 0,
        'past' => 0,
        'cancelled' => 0
    ];

    foreach ($formattedAppointments as $apt) {
        if ($apt['status_id'] == APPOINTMENT_STATUS_CANCELLED) {
            $stats['cancelled']++;
        } elseif ($apt['is_past']) {
            $stats['past']++;
        } else {
            $stats['upcoming']++;
        }
    }

    sendSuccess([
        'appointments' => $formattedAppointments,
        'stats' => $stats,
        'filter' => $statusFilter
    ]);

} catch (Exception $e) {
    error_log("Erreur dans get_user_appointments.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de la recuperation des rendez-vous", 500);
}
?>