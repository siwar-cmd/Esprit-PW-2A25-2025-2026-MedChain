<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 4_000_000) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Image trop volumineuse']);
    exit;
}

$input = json_decode($rawBody, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

$image = trim((string) ($input['image'] ?? ''));
if ($image === '') {
    echo json_encode(['success' => false, 'message' => 'Image manquante']);
    exit;
}

require_once __DIR__ . '/../../../controllers/Faceidcontroller.php';

$controller = new FaceIdController();
$result = $controller->enrollFace((int) $_SESSION['user_id'], $image);

echo json_encode($result);
exit;
