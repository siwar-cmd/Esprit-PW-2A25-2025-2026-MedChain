<?php
require_once __DIR__ . '/../../config/env.php';

session_start();

// ══════════════════════════════════════════════════════════════
//  VÉRIFICATION SESSION ADMIN
// ══════════════════════════════════════════════════════════════
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// ══════════════════════════════════════════════════════════════
//  CONFIGURATION GROQ (remplacer la clé ci-dessous)
// ══════════════════════════════════════════════════════════════
define('GROQ_API_KEY', Env::required('GROQ_API_KEY'));
define('GROQ_MODEL',   'llama-3.3-70b-versatile'); // ou 'mixtral-8x7b-32768'
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// ── Prompt système MedChain (identique à l'original) ──────────
define('SYSTEM_INSTRUCTION', "Tu es MedChain AI, l'assistant administratif et médical de la plateforme MedChain.

Tu aides exclusivement les administrateurs de la plateforme avec :
- La gestion des utilisateurs (création, modification, activation, suppression)
- La validation des comptes médecins et la vérification RPPS
- La gestion des dossiers patients et leur conformité
- Les statistiques, exports PDF/Excel, et indicateurs KPI
- La sécurité informatique et la conformité RGPD (données de santé)
- Les bonnes pratiques d'administration d'une plateforme médicale

Règles importantes :
1. Réponds toujours en français, de façon claire et professionnelle
2. Utilise du markdown (titres ##, listes -, gras **) pour structurer tes réponses
3. Sois précis sur les obligations légales (RGPD, conservation 20 ans des données médicales, CNIL)
4. Si une question sort du domaine médical/administratif, redirige poliment vers les sujets que tu couvres
5. Ne divulgue jamais d'informations confidentielles ni de données personnelles
6. En cas d'incident de sécurité, recommande toujours de contacter le DPO");

// ══════════════════════════════════════════════════════════════
//  LECTURE DU CORPS DE LA REQUÊTE
// ══════════════════════════════════════════════════════════════
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide : champ "messages" manquant']);
    exit;
}

// ══════════════════════════════════════════════════════════════
//  CONVERSION FORMAT → GROQ (OpenAI-like)
// ══════════════════════════════════════════════════════════════
$groqMessages = [
    ['role' => 'system', 'content' => SYSTEM_INSTRUCTION]
];

foreach ($data['messages'] as $msg) {
    if (!isset($msg['role'], $msg['content'])) continue;
    $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
    $groqMessages[] = [
        'role'    => $role,
        'content' => $msg['content']
    ];
}

// ══════════════════════════════════════════════════════════════
//  CONSTRUCTION DU PAYLOAD GROQ
// ══════════════════════════════════════════════════════════════
$payload = [
    'model'       => GROQ_MODEL,
    'messages'    => $groqMessages,
    'temperature' => 0.7,
    'max_tokens'  => 1024,
    'top_p'       => 0.9,
];

// ══════════════════════════════════════════════════════════════
//  APPEL API GROQ VIA cURL
// ══════════════════════════════════════════════════════════════
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
    echo json_encode(['error' => 'Réponse vide de l\'API Groq']);
    exit;
}

// ══════════════════════════════════════════════════════════════
//  RENVOI DE LA RÉPONSE AU FORMAT ATTENDU PAR LE FRONT
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json');
echo json_encode([
    'content' => [
        ['type' => 'text', 'text' => $replyText]
    ]
]);
