<?php
/**
 * API to book an appointment - CLEAN VERSION
 * Endpoint: /api/book_appointment.php
 * Method: POST
 * Authentication: Required (logged-in patient)
 * JSON Body:
 *  - slot_id: Slot ID (required)
 *  - reason_id: Consultation reason ID (required)
 *  - notes: Patient notes (optional)
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle the OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check the method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Methode non autorisee. Utilisez POST", 405);
}

// Check authentication
if (!isLoggedIn()) {
    sendError("Vous devez etre connecte pour reserver un rendez-vous", 401);
}

if (!isPatient()) {
    sendError("Seuls les patients peuvent reserver des rendez-vous", 403);
}

// Get the JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError("Donnees JSON invalides");
}

// Validate required fields
if (!isset($input['slot_id']) || empty($input['slot_id'])) {
    sendError("L'identifiant du creneau est requis");
}

if (!isset($input['reason_id']) || empty($input['reason_id'])) {
    sendError("Le motif de consultation est requis");
}

$slotId = intval($input['slot_id']);
$reasonId = intval($input['reason_id']);
$notes = isset($input['notes']) ? cleanString($input['notes']) : null;
$patientId = intval($_SESSION['user_id']);

$pdo = null;

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion a la base de donnees");
    }

    // Start the transaction
    $pdo->beginTransaction();

    // Check that the slot exists and is available
    $sqlCheckSlot = "
        SELECT 
            cd.id,
            cd.praticien_id,
            cd.structure_id,
            cd.debut,
            cd.duree_minute,
            cd.est_reserve,
            u.prenom as praticien_prenom,
            u.nom as praticien_nom,
            s.nom as structure_nom,
            s.adresse,
            s.ville
        FROM creneaux_disponibilite cd
        INNER JOIN praticiens p ON cd.praticien_id = p.utilisateur_id
        INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
        INNER JOIN structures_sante s ON cd.structure_id = s.id
        WHERE cd.id = :slot_id
        AND cd.debut >= NOW()
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sqlCheckSlot);
    $stmt->execute(['slot_id' => $slotId]);
    $slot = $stmt->fetch();

    if (!$slot) {
        $pdo->rollBack();
        sendError("Ce creneau n'existe pas ou n'est plus disponible", 404);
    }

    if ($slot['est_reserve']) {
        $pdo->rollBack();
        sendError("Ce creneau a deja ete reserve par un autre patient", 409);
    }

    // Check that the patient doesn't already have an appointment at the same time
    $sqlCheckConflict = "
        SELECT r.id
        FROM rendezvous r
        INNER JOIN creneaux_disponibilite cd ON r.creneaux_id = cd.id
        WHERE r.patient_id = :patient_id
        AND r.statut IN (" . APPOINTMENT_STATUS_PENDING . ", " . APPOINTMENT_STATUS_CONFIRMED . ")
        AND cd.debut = :debut
    ";

    $stmt = $pdo->prepare($sqlCheckConflict);
    $stmt->execute([
        'patient_id' => $patientId,
        'debut' => $slot['debut']
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        sendError("Vous avez deja un rendez-vous prevu a cette date et heure", 409);
    }

    // Check that the reason exists
    $sqlCheckReason = "SELECT id FROM motif WHERE id = :reason_id";
    $stmt = $pdo->prepare($sqlCheckReason);
    $stmt->execute(['reason_id' => $reasonId]);

    if (!$stmt->fetch()) {
        $pdo->rollBack();
        sendError("Le motif de consultation selectionne est invalide", 400);
    }

    // Mark the slot as booked
    $sqlUpdateSlot = "
        UPDATE creneaux_disponibilite
        SET est_reserve = 1
        WHERE id = :slot_id
    ";

    $stmt = $pdo->prepare($sqlUpdateSlot);
    $stmt->execute(['slot_id' => $slotId]);

    // Create the appointment
    $sqlCreateAppointment = "
        INSERT INTO rendezvous (
            patient_id,
            praticien_id,
            creneaux_id,
            structure_id,
            motif,
            cree_par,
            statut,
            notes_patient
        ) VALUES (
            :patient_id,
            :praticien_id,
            :creneaux_id,
            :structure_id,
            :motif,
            :cree_par,
            :statut,
            :notes_patient
        )
    ";

    $stmt = $pdo->prepare($sqlCreateAppointment);
    $stmt->execute([
        'patient_id' => $patientId,
        'praticien_id' => $slot['praticien_id'],
        'creneaux_id' => $slotId,
        'structure_id' => $slot['structure_id'],
        'motif' => $reasonId,
        'cree_par' => $patientId,
        'statut' => APPOINTMENT_STATUS_CONFIRMED,
        'notes_patient' => $notes
    ]);

    $appointmentId = $pdo->lastInsertId();

    // Commit the transaction
    $pdo->commit();

    // Calculate the end time
    $startTime = new DateTime($slot['debut']);
    $endTime = clone $startTime;
    $endTime->add(new DateInterval('PT' . $slot['duree_minute'] . 'M'));

    // Return the confirmation
    sendSuccess([
        'appointment' => [
            'id' => intval($appointmentId),
            'slot_id' => intval($slotId),
            'practitioner' => [
                'id' => intval($slot['praticien_id']),
                'name' => $slot['praticien_prenom'] . ' ' . $slot['praticien_nom']
            ],
            'structure' => [
                'id' => intval($slot['structure_id']),
                'name' => $slot['structure_nom'],
                'address' => $slot['adresse'],
                'city' => $slot['ville']
            ],
            'datetime' => $slot['debut'],
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'duration_minutes' => intval($slot['duree_minute']),
            'reason_id' => $reasonId,
            'notes' => $notes,
            'status' => 'Confirme',
            'created_at' => date('Y-m-d H:i:s')
        ],
        'message' => 'Votre rendez-vous a ete confirme avec succes'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur PDO dans book_appointment.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de la reservation", 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur dans book_appointment.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de la reservation", 500);
}
?>