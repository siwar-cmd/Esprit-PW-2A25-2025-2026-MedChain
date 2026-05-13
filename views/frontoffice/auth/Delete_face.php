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

require_once __DIR__ . '/../../../controllers/Faceidcontroller.php';

$controller = new FaceIdController();
$result = $controller->deleteFace((int) $_SESSION['user_id']);

echo json_encode($result);
exit;
