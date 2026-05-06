<?php
require_once __DIR__ . '/../../../config/env.php';

// ══════════════════════════════════════════════════════════════
//  spell-proxy.php — Correcteur orthographique IA
//  À placer dans : views/frontoffice/auth/
//  Accessible sans session (utilisé lors de l'inscription)
// ══════════════════════════════════════════════════════════════

session_start();
header('Content-Type: application/json');

// Méthode uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Lecture du corps
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

// ══════════════════════════════════════════════════════════════
//  CONFIGURATION GROQ
//  Remplacez la clé ci-dessous par votre clé Groq
// ══════════════════════════════════════════════════════════════
define('GROQ_API_KEY', Env::required('GROQ_API_KEY'));
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// Prompt système dédié à la correction orthographique
$systemPrompt = "Tu es un correcteur orthographique automatique pour un formulaire médical français. 
Règles STRICTES :
- Corrige UNIQUEMENT les fautes d'orthographe et de frappe
- Mets la première lettre de chaque mot en majuscule pour les prénoms/noms
- Ne change pas les mots déjà corrects
- Ne traduis pas, ne reformule pas
- Réponds UNIQUEMENT avec le texte corrigé, sans guillemets, sans explication";

// Construction des messages Groq
$groqMessages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

foreach ($data['messages'] as $msg) {
    if (!isset($msg['role'], $msg['content'])) continue;
    $groqMessages[] = [
        'role'    => ($msg['role'] === 'assistant') ? 'assistant' : 'user',
        'content' => $msg['content']
    ];
}

// Payload Groq
$payload = [
    'model'       => GROQ_MODEL,
    'messages'    => $groqMessages,
    'temperature' => 0.1,   // très bas pour rester fidèle au texte
    'max_tokens'  => 150,
    'top_p'       => 0.9,
];

// Appel cURL vers Groq
$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur réseau : ' . $curlErr]);
    exit;
}

$responseData = json_decode($result, true);

if ($httpCode !== 200) {
    $errMsg = $responseData['error']['message'] ?? 'Erreur API Groq (HTTP ' . $httpCode . ')';
    http_response_code($httpCode);
    echo json_encode(['error' => $errMsg]);
    exit;
}

$replyText = $responseData['choices'][0]['message']['content'] ?? null;

if (!$replyText) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse vide']);
    exit;
}

// Réponse au format attendu par register.php
echo json_encode([
    'content' => [
        ['type' => 'text', 'text' => trim($replyText)]
    ]
]);
