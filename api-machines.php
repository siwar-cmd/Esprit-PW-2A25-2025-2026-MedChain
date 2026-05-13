<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $pdo = config::getConnexion();
    
    // Fetch all machines with their current status
    // Status Logic:
    // If there is an intervention NOW -> in_use
    // If there is an intervention later TODAY -> reserved
    // Else -> available
    $sql = "
        SELECT 
            m.idMateriel, m.id_materiel, m.categorie, m.bloc, m.pos_x, m.pos_y, m.pos_z,
            'available' as current_status
        FROM materiel m
    ";
    
    $stmt = $pdo->query($sql);
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform to match the expected format for the 3D map
    $machines = array_map(function($m) {
        return [
            'id' => $m['idMateriel'],
            'name' => $m['id_materiel'],
            'type' => $m['categorie'],
            'bloc' => $m['bloc'],
            'pos_x' => (float)$m['pos_x'],
            'pos_y' => (float)$m['pos_y'],
            'pos_z' => (float)$m['pos_z'],
            'status' => $m['current_status'] ?? 'available'
        ];
    }, $materiels);

    echo json_encode($machines);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
