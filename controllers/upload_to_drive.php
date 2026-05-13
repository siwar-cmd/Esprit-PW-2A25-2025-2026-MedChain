<?php
// Endpoint pour recevoir un fichier PDF via POST et l'uploader sur Google Drive
// Exige un fichier de compte de service JSON placé dans projet/config/google-service-account.json

header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$serviceJsonPath = __DIR__ . '/../config/google-service-account.json';
if (!file_exists($serviceJsonPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Service account JSON not found. Place your service account JSON at projet/config/google-service-account.json']);
    exit;
}

$service = json_decode(file_get_contents($serviceJsonPath), true);
if (empty($service['client_email']) || empty($service['private_key'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid service account JSON (missing client_email or private_key)']);
    exit;
}

// Create JWT
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$now = time();
$jwtHeader = ['alg' => 'RS256', 'typ' => 'JWT'];
$jwtClaim = [
    'iss' => $service['client_email'],
    'scope' => 'https://www.googleapis.com/auth/drive.file',
    'aud' => 'https://oauth2.googleapis.com/token',
    'exp' => $now + 3600,
    'iat' => $now
];

$unsigned = base64url_encode(json_encode($jwtHeader)) . '.' . base64url_encode(json_encode($jwtClaim));
$signature = null;
$privateKey = $service['private_key'];
$res = openssl_pkey_get_private($privateKey);
if (!$res) {
    $err = error_get_last();
    file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - private key load failed: " . print_r($err, true) . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load private key', 'details' => $err]);
    exit;
}
if (!openssl_sign($unsigned, $sig, $res, OPENSSL_ALGO_SHA256)) {
    $err = error_get_last();
    file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - openssl_sign failed: " . print_r($err, true) . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to sign JWT', 'details' => $err]);
    openssl_free_key($res);
    exit;
}
openssl_free_key($res);
$jwt = $unsigned . '.' . base64url_encode($sig);

// Exchange JWT for access token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]));
$resp = curl_exec($ch);
if ($resp === false) {
    $curlErr = curl_error($ch);
    file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - token request curl error: " . $curlErr . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Token request failed', 'curl_error' => $curlErr]);
    curl_close($ch);
    exit;
}
curl_close($ch);
$tokenData = json_decode($resp, true);
if (empty($tokenData['access_token'])) {
    file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - token response: " . print_r($tokenData, true) . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Unable to obtain access token', 'response' => $tokenData]);
    exit;
}
$accessToken = $tokenData['access_token'];

// Upload file to Drive (multipart upload)
$tmpPath = $_FILES['file']['tmp_name'];
$originalName = isset($_POST['filename']) && trim($_POST['filename']) !== '' ? basename($_POST['filename']) : basename($_FILES['file']['name']);
$mime = 'application/pdf';

$metadata = ['name' => $originalName];
// Optionnel: dossier destination
if (!empty($_POST['folderId'])) {
    $metadata['parents'] = [$_POST['folderId']];
}

$boundary = '-------' . uniqid();
$delimiter = "\r\n--" . $boundary . "\r\n";
$closeDelimiter = "\r\n--" . $boundary . "--";

$body = '';
$body .= $delimiter;
$body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
$body .= json_encode($metadata);
$body .= $delimiter;
$body .= "Content-Type: " . $mime . "\r\n\r\n";
$body .= file_get_contents($tmpPath);
$body .= $closeDelimiter;

$url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: multipart/related; boundary=' . $boundary,
    'Content-Length: ' . strlen($body)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

$resp = curl_exec($ch);
if ($resp === false) {
    $curlErr = curl_error($ch);
    file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - upload curl error: " . $curlErr . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Upload request failed', 'curl_error' => $curlErr]);
    curl_close($ch);
    exit;
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($resp, true);
if ($status >= 200 && $status < 300) {
    echo json_encode(['success' => true, 'file' => $result]);
    exit;
}

file_put_contents(__DIR__ . '/../config/drive-upload.log', date('c') . " - upload failed: http_code=" . $status . " response=" . print_r($result, true) . PHP_EOL, FILE_APPEND);
http_response_code(500);
echo json_encode(['error' => 'Upload failed', 'response' => $result, 'http_code' => $status]);
exit;

?>
