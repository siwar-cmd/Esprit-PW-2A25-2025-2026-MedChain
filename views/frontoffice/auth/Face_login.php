<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
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
$email = trim((string) ($input['email'] ?? ''));

if ($image === '') {
    echo json_encode(['success' => false, 'message' => 'Image manquante']);
    exit;
}

if ($email === '' || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide ou manquant']);
    exit;
}

require_once __DIR__ . '/../../../controllers/Faceidcontroller.php';

$controller = new FaceIdController();
$result = $controller->verifyFaceLogin($image, $email);

if (empty($result['success']) && isset($result['message'])) {
    $msg = strtolower($result['message']);
    if (
        str_contains($msg, 'aucun visage') ||
        str_contains($msg, 'no face') ||
        str_contains($msg, 'not found') ||
        str_contains($msg, 'not enrolled') ||
        str_contains($msg, 'introuvable')
    ) {
        $result['message'] = 'Aucun visage enregistré pour cet email. Connectez-vous avec votre mot de passe puis activez Face ID depuis votre profil.';
        $result['no_face_registered'] = true;
    }
}

echo json_encode($result);
exit;
