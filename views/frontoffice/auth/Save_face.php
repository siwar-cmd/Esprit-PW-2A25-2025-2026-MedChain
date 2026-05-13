<?php
/**
 * save_face.php
 * Endpoint AJAX — Enregistrement du visage d'un utilisateur connecté
 * Appelé depuis profile.php ou une page dédiée "Configurer Face ID"
 *
 * Méthode : POST
 * Paramètres (JSON body) :
 *   - image : string (base64)
 */

session_start();
header('Content-Type: application/json');

// Seuls les utilisateurs connectés peuvent enregistrer leur visage
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/../../../controllers/FaceIdController.php';

$input = json_decode(file_get_contents('php://input'), true);
$image = trim($input['image'] ?? '');

if (empty($image)) {
    echo json_encode(['success' => false, 'message' => 'Image manquante']);
    exit;
}

$controller = new FaceIdController();
$result     = $controller->enrollFace((int) $_SESSION['user_id'], $image);

echo json_encode($result);
exit;