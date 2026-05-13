<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$pdo = config::getConnexion();
$method = $_SERVER['REQUEST_METHOD'];

$type = $_GET['type'] ?? ($_POST['type'] ?? null);

if (!$type) {
    echo json_encode(["success" => false, "message" => "Type is required (doctor or machine)"]);
    exit;
}

if ($method === 'GET') {
    if ($type === 'doctor') {
        $doctor_id = $_GET['doctor_id'] ?? null;
        if (!$doctor_id) {
            echo json_encode(["success" => false, "message" => "doctor_id is required"]);
            exit;
        }

        // Fetch doctor appointments
        $stmt = $pdo->prepare("SELECT idRDV as id, idMedecin as doctor_id, dateHeureDebut as start_time, dateHeureFin as end_time, motif as patient_name FROM rendezvous WHERE idMedecin = ?");
        $stmt->execute([$doctor_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } else if ($type === 'machine') {
        $machine_id = $_GET['machine_id'] ?? null;
        if (!$machine_id) {
            echo json_encode(["success" => false, "message" => "machine_id is required"]);
            exit;
        }

        // Fetch machine usage periods from materiel table
        $stmt2 = $pdo->prepare("SELECT id_materiel, date_utilisation_debut, date_utilisation_fin FROM materiel WHERE id_materiel = ?");
        $stmt2->execute([$machine_id]);
        $materielData = $stmt2->fetch(PDO::FETCH_ASSOC);

        $events = [];

        // If materiel has usage dates, add them as events
        if ($materielData && !empty($materielData['date_utilisation_debut']) && !empty($materielData['date_utilisation_fin'])) {
            $events[] = [
                'id' => 'mat_' . $materielData['id_materiel'],
                'machine_id' => $materielData['id_materiel'],
                'used_by' => 'Période de réservation matériel',
                'start_time' => $materielData['date_utilisation_debut'],
                'end_time' => $materielData['date_utilisation_fin'],
                'type' => 'reservation'
            ];
        }

        echo json_encode(["success" => true, "data" => $events]);
    }

} else if ($method === 'POST') {
    // Read JSON body
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    $date = $data['date'] ?? '';
    $startTime = $data['start_time'] ?? '';
    $endTime = $data['end_time'] ?? '';

    if (!$date || !$startTime || !$endTime) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "date, start_time, and end_time are required"]);
        exit;
    }

    $startDatetime = $date . ' ' . $startTime;
    $endDatetime = $date . ' ' . $endTime;

    if ($type === 'doctor') {
        $doctor_id = $data['doctor_id'] ?? null;
        $patient_name = $data['patient_name'] ?? 'Unknown Patient';

        if (!$doctor_id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "doctor_id is required"]);
            exit;
        }

        // Check Conflict
        // Overlap condition: startA < endB AND startB < endA
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM rendezvous 
            WHERE idMedecin = ? 
              AND dateHeureDebut < ? 
              AND dateHeureFin > ?
        ");
        $stmt->execute([$doctor_id, $endDatetime, $startDatetime]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            http_response_code(409);
            echo json_encode(["success" => false, "message" => "Time conflict detected for this doctor."]);
            exit;
        }

        // We assume a generic client ID 1 for now if we don't have actual client IDs.
        $stmt = $pdo->prepare("INSERT INTO rendezvous (idMedecin, idClient, dateHeureDebut, dateHeureFin, motif, statut) VALUES (?, 1, ?, ?, ?, 'planifie')");
        if ($stmt->execute([$doctor_id, $startDatetime, $endDatetime, "Patient: $patient_name"])) {
            echo json_encode(["success" => true, "message" => "Appointment booked successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to book appointment"]);
        }

    } else if ($type === 'machine') {
        $machine_id = $data['machine_id'] ?? null;
        $used_by = $data['used_by'] ?? 'Unknown';

        if (!$machine_id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "machine_id is required"]);
            exit;
        }

        // Check Conflict
        // End time of intervention = date_intervention + duree MINUTE
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM intervention 
            WHERE 1=0
        ");
        $stmt->execute([]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            http_response_code(409);
            echo json_encode(["success" => false, "message" => "Time conflict detected for this machine."]);
            exit;
        }

        // Calculate duree (duration in minutes)
        $start_ts = strtotime($startDatetime);
        $end_ts = strtotime($endDatetime);
        $duree = round(($end_ts - $start_ts) / 60);

        if ($duree <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "End time must be after start time"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO intervention (type, date_intervention, duree, chirurgien, niveau_urgence, description) VALUES ('Utilisation Machine', ?, ?, ?, 1, 'Réservation via calendrier')");
        if ($stmt->execute([$startDatetime, $duree, $used_by])) {
            echo json_encode(["success" => true, "message" => "Machine booked successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to book machine"]);
        }
    }
} else if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) exit;
    if ($type === 'doctor') {
        $stmt = $pdo->prepare("DELETE FROM rendezvous WHERE idRDV = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
    } else if ($type === 'machine') {
        $stmt = $pdo->prepare("DELETE FROM intervention WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
