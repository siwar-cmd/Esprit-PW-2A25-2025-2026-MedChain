<?php
/**
 * face_login.php
 * Endpoint AJAX — Connexion par reconnaissance faciale
 * Emplacement : views/frontoffice/auth/face_login.php
 *
 * Méthode : POST
 * Paramètres attendus (JSON body) :
 *   - image  : string  (base64 de la webcam)
 *   - email  : string  (email saisi dans le formulaire)
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Répondre immédiatement aux pre-flight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Sécurité : accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// ── Chargement du contrôleur ─────────────────────────────────────────────────
// Ce fichier est dans : views/frontoffice/auth/
// FaceIdController.php est dans : controllers/
// => remonter 3 niveaux (auth -> frontoffice -> views -> racine) puis controllers/
$controllerPath = __DIR__ . '/../../../controllers/FaceIdController.php';

if (!file_exists($controllerPath)) {
    error_log('[FaceID] FaceIdController introuvable : ' . $controllerPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration serveur (contrôleur introuvable)']);
    exit;
}

require_once $controllerPath;

// ── Lecture du body JSON ─────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

$image = trim($input['image'] ?? '');
$email = trim($input['email'] ?? '');

// ── Validation ───────────────────────────────────────────────────────────────
if (empty($image)) {
    echo json_encode(['success' => false, 'message' => 'Image manquante']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide ou manquant']);
    exit;
}

// ── Vérification faciale ─────────────────────────────────────────────────────
$controller = new FaceIdController();
$result     = $controller->verifyFaceLogin($image, $email);

// ── Enrichissement du message si aucun visage enregistré ─────────────────────
if (!empty($result['success']) === false && isset($result['message'])) {
    $msg = strtolower($result['message']);
    if (
        strpos($msg, 'aucun visage') !== false ||
        strpos($msg, 'no face')      !== false ||
        strpos($msg, 'not found')    !== false ||
        strpos($msg, 'not enrolled') !== false ||
        strpos($msg, 'introuvable')  !== false
    ) {
        $result['message']     = 'Aucun visage enregistré pour cet email. Connectez-vous avec votre mot de passe puis activez Face ID depuis votre profil.';
        $result['no_face_registered'] = true;
    }
}

// ── Réponse JSON ─────────────────────────────────────────────────────────────
echo json_encode($result);
exit;