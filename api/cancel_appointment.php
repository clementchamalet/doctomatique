<?php
/**
 * API pour annuler un rendez-vous - VERSION PROPRE
 * Endpoint: /api/cancel_appointment.php
 * Methode: POST
 * Authentification: Requise (patient connecte)
 * Body JSON:
 *  - appointment_id: ID du rendez-vous (requis)
 */

require_once __DIR__ . '/../pages/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion de la requete OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verification de la methode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Methode non autorisee. Utilisez POST", 405);
}

// Verification de l'authentification
if (!isLoggedIn()) {
    sendError("Vous devez etre connecte pour annuler un rendez-vous", 401);
}

if (!isPatient()) {
    sendError("Seuls les patients peuvent annuler leurs rendez-vous", 403);
}

// Recuperation des donnees JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['appointment_id'])) {
    sendError("L'identifiant du rendez-vous est requis");
}

$appointmentId = intval($input['appointment_id']);
$patientId = intval($_SESSION['user_id']);

$pdo = null;

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Erreur de connexion a la base de donnees");
    }

    // Debut de la transaction
    $pdo->beginTransaction();

    // Verification que le rendez-vous existe et appartient au patient
    $sqlCheck = "
        SELECT 
            r.id,
            r.creneaux_id,
            r.statut,
            cd.debut,
            st.description as statut_desc
        FROM rendezvous r
        INNER JOIN creneaux_disponibilite cd ON r.creneaux_id = cd.id
        INNER JOIN statut st ON r.statut = st.id
        WHERE r.id = :appointment_id
        AND r.patient_id = :patient_id
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sqlCheck);
    $stmt->execute([
        'appointment_id' => $appointmentId,
        'patient_id' => $patientId
    ]);

    $appointment = $stmt->fetch();

    if (!$appointment) {
        $pdo->rollBack();
        sendError("Ce rendez-vous n'existe pas ou ne vous appartient pas", 404);
    }

    // Verification que le rendez-vous n'est pas deja annule
    if ($appointment['statut'] == APPOINTMENT_STATUS_CANCELLED) {
        $pdo->rollBack();
        sendError("Ce rendez-vous est deja annule", 400);
    }

    // Verification que le rendez-vous n'est pas dans le passe
    $appointmentDate = new DateTime($appointment['debut']);
    $now = new DateTime();

    if ($appointmentDate < $now) {
        $pdo->rollBack();
        sendError("Vous ne pouvez pas annuler un rendez-vous passe", 400);
    }

    // Mise a jour du statut du rendez-vous
    $sqlUpdateAppointment = "
        UPDATE rendezvous
        SET statut = :status
        WHERE id = :appointment_id
    ";

    $stmt = $pdo->prepare($sqlUpdateAppointment);
    $stmt->execute([
        'appointment_id' => $appointmentId,
        'status' => APPOINTMENT_STATUS_CANCELLED
    ]);

    // Liberation du creneau
    $sqlUpdateSlot = "
        UPDATE creneaux_disponibilite
        SET est_reserve = 0
        WHERE id = :slot_id
    ";

    $stmt = $pdo->prepare($sqlUpdateSlot);
    $stmt->execute(['slot_id' => $appointment['creneaux_id']]);

    // Validation de la transaction
    $pdo->commit();

    sendSuccess([
        'message' => 'Votre rendez-vous a ete annule avec succes',
        'appointment_id' => $appointmentId,
        'cancelled_at' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur PDO dans cancel_appointment.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de l'annulation", 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur dans cancel_appointment.php: " . $e->getMessage());
    sendError("Une erreur est survenue lors de l'annulation", 500);
}
?>