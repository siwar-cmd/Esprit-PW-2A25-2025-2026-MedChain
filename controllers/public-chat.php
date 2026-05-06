<?php
require_once __DIR__ . '/../config/env.php';

/**
 * public-chat.php
 * Endpoint public pour le chatbot de la page d'accueil.
 * Utilise l'API Groq (LLaMA 3.3) avec un prompt système adapté aux visiteurs.
 */

// Désactiver l'affichage des erreurs pour une réponse JSON propre
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------------------
// 1. CONFIGURATION API GROQ (identique à chatbot-proxy.php)
// ------------------------------------------------------------------
define('GROQ_API_KEY', Env::required('GROQ_API_KEY'));
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// ------------------------------------------------------------------
// 2. PROMPT SYSTÈME – version public (pas d'infos admin)
// ------------------------------------------------------------------
define('SYSTEM_PROMPT', "Tu es MedChain AI, l'assistant officiel de la plateforme MedChain.

Tu aides les visiteurs et les patients avec :
- Les explications sur le fonctionnement de MedChain (blockchain, sécurité, partage de données)
- Les avantages du dossier médical numérique
- Les tarifs, l'inscription, la prise de rendez-vous
- Les réponses générales sur la plateforme
- Des conseils de santé généraux (jamais de diagnostic)

Règles strictes :
1. Réponds toujours en français, de façon claire et bienveillante.
2. Utilise du markdown léger (listes -, **gras**) pour structurer.
3. Ne donne JAMAIS de diagnostic médical – oriente toujours vers un médecin.
4. Reste professionnel, rassurant et respecte le secret médical.
5. Si tu ne sais pas, dis-le honnêtement.");

// ------------------------------------------------------------------
// 3. LECTURE DE LA REQUÊTE JSON
// ------------------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message manquant.']);
    exit;
}

$userMessage = trim($input['message']);
if (strlen($userMessage) < 2) {
    echo json_encode(['reply' => 'Merci de poser une question un peu plus développée.']);
    exit;
}

// ------------------------------------------------------------------
// 4. CONSTRUCTION DU PAYLOAD GROQ
// ------------------------------------------------------------------
$messages = [
    ['role' => 'system', 'content' => SYSTEM_PROMPT],
    ['role' => 'user',   'content' => $userMessage]
];

$payload = [
    'model'       => GROQ_MODEL,
    'messages'    => $messages,
    'temperature' => 0.7,
    'max_tokens'  => 800,
    'top_p'       => 0.9,
];

// ------------------------------------------------------------------
// 5. APPEL CURL VERS GROQ
// ------------------------------------------------------------------
$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur réseau : ' . $curlErr]);
    exit;
}

if ($httpCode !== 200) {
    $errData = json_decode($response, true);
    $errMsg = $errData['error']['message'] ?? 'Erreur API Groq (HTTP ' . $httpCode . ')';
    http_response_code($httpCode);
    echo json_encode(['error' => $errMsg]);
    exit;
}

$data = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? null;

if (!$reply) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse vide de l\'IA']);
    exit;
}

echo json_encode(['reply' => $reply]);
