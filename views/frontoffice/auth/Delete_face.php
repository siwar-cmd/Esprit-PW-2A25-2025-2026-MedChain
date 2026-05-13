<?php
/**
 * delete_face.php
 * Endpoint AJAX — Suppression du visage enregistré
 *
 * Méthode : POST
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../../controllers/FaceIdController.php';

$controller = new FaceIdController();
$result     = $controller->deleteFace((int) $_SESSION['user_id']);

echo json_encode($result);
exit;