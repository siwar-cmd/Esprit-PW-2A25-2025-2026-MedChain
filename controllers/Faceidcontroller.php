<?php

require_once __DIR__ . '/../config.php';

class FaceIdController
{
    private const FACEPP_DETECT = 'https://api-us.faceplusplus.com/facepp/v3/detect';
    private const FACEPP_COMPARE = 'https://api-us.faceplusplus.com/facepp/v3/compare';
    private const MAX_IMAGE_BYTES = 2_500_000;

    private PDO $pdo;
    private string $apiKey;
    private string $apiSecret;
    private float $confidenceThreshold;

    public function __construct()
    {
        $this->pdo = config::getConnexion();
        $this->apiKey = defined('FACEPP_API_KEY') ? trim((string) FACEPP_API_KEY) : '';
        $this->apiSecret = defined('FACEPP_API_SECRET') ? trim((string) FACEPP_API_SECRET) : '';
        $this->confidenceThreshold = defined('FACEPP_CONFIDENCE_THRESHOLD')
            ? (float) FACEPP_CONFIDENCE_THRESHOLD
            : 80.0;
    }

    public function enrollFace(int $userId, string $imageBase64): array
    {
        try {
            $imageData = $this->normalizeBase64Image($imageBase64);
            $detection = $this->detectFace($imageData);

            if (!$detection['success']) {
                return $detection;
            }

            $this->saveFaceToken($userId, $detection['face_token']);

            return [
                'success' => true,
                'message' => 'Visage enregistré avec succès',
            ];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('FaceID enroll error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du visage'];
        }
    }

    public function verifyFaceLogin(string $imageBase64, string $email): array
    {
        try {
            $user = $this->getUserWithFace($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Aucun visage enregistré pour cet email'];
            }

            $capturedData = $this->normalizeBase64Image($imageBase64);
            $comparison = $this->compareCapturedFaceToToken($capturedData, $user['face_token']);

            if (!$comparison['success']) {
                return $comparison;
            }

            $confidence = $comparison['confidence'];
            if ($confidence < $this->confidenceThreshold) {
                return [
                    'success' => false,
                    'message' => 'Visage non reconnu. Veuillez réessayer ou utiliser votre mot de passe.',
                    'confidence' => $confidence,
                ];
            }

            if ($user['statut'] !== 'actif') {
                return ['success' => false, 'message' => 'Votre compte est désactivé'];
            }

            $this->startFaceSession($user);
            $this->updateLastConnexion((int) $user['id_utilisateur']);

            return [
                'success' => true,
                'message' => 'Connexion par Face ID réussie',
                'confidence' => $confidence,
                'user' => [
                    'id_utilisateur' => $user['id_utilisateur'],
                    'nom' => $user['nom'],
                    'prenom' => $user['prenom'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'statut' => $user['statut'],
                ],
            ];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('FaceID verify error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la vérification faciale'];
        }
    }

    public function deleteFace(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE utilisateur
                 SET face_token = NULL, face_image_data = NULL, face_registered_at = NULL
                 WHERE id_utilisateur = ?'
            );
            $stmt->execute([$userId]);

            return ['success' => true, 'message' => 'Visage supprimé avec succès'];
        } catch (Throwable $e) {
            error_log('FaceID delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }

    public function hasFaceRegistered(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT face_token FROM utilisateur WHERE id_utilisateur = ? AND face_token IS NOT NULL'
            );
            $stmt->execute([$userId]);

            return $stmt->fetchColumn() !== false;
        } catch (Throwable $e) {
            error_log('FaceID hasFaceRegistered error: ' . $e->getMessage());
            return false;
        }
    }

    private function detectFace(string $imageBase64): array
    {
        $response = $this->callFaceppApi(self::FACEPP_DETECT, [
            'image_base64' => $imageBase64,
            'return_attributes' => 'none',
        ]);

        if (!$response || isset($response['error_message'])) {
            $this->logFaceppError('detect', $response);
            return ['success' => false, 'message' => 'Impossible de vérifier le visage pour le moment'];
        }

        if (empty($response['faces'])) {
            return ['success' => false, 'message' => 'Aucun visage détecté. Assurez-vous d\'être bien en face de la caméra.'];
        }

        if (count($response['faces']) > 1) {
            return ['success' => false, 'message' => 'Plusieurs visages détectés. Restez seul dans le cadre.'];
        }

        return [
            'success' => true,
            'face_token' => $response['faces'][0]['face_token'],
        ];
    }

    private function compareCapturedFaceToToken(string $capturedImageBase64, string $storedFaceToken): array
    {
        $response = $this->callFaceppApi(self::FACEPP_COMPARE, [
            'image_base64_1' => $capturedImageBase64,
            'face_token2' => $storedFaceToken,
        ]);

        if (!$response || isset($response['error_message'])) {
            $message = (string) ($response['error_message'] ?? '');
            $this->logFaceppError('compare', $response);

            if (str_contains($message, 'NO_FACE_FOUND')) {
                return ['success' => false, 'message' => 'Aucun visage détecté dans la caméra. Regardez bien l\'objectif.'];
            }

            return ['success' => false, 'message' => 'Impossible de vérifier le visage pour le moment'];
        }

        return [
            'success' => true,
            'confidence' => (float) ($response['confidence'] ?? 0),
        ];
    }

    private function callFaceppApi(string $url, array $params): ?array
    {
        if ($this->apiKey === '' || $this->apiSecret === '') {
            error_log('FaceID configuration missing FACEPP_API_KEY or FACEPP_API_SECRET');
            return null;
        }

        $params['api_key'] = $this->apiKey;
        $params['api_secret'] = $this->apiSecret;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'MedChainFaceID/1.0',
            CURLOPT_HTTPHEADER => ['Expect:'],
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log('FaceID cURL error: ' . $error);
            return null;
        }

        $decoded = json_decode((string) $result, true);
        if (!is_array($decoded)) {
            error_log('FaceID invalid JSON response, HTTP ' . $httpCode);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('FaceID API HTTP ' . $httpCode . ': ' . ($decoded['error_message'] ?? 'unknown error'));
        }

        return $decoded;
    }

    private function saveFaceToken(int $userId, string $faceToken): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateur
             SET face_token = ?, face_image_data = NULL, face_registered_at = NOW()
             WHERE id_utilisateur = ?'
        );
        $stmt->execute([$faceToken, $userId]);
    }

    private function getUserWithFace(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email, role, statut, face_token
             FROM utilisateur
             WHERE email = ? AND face_token IS NOT NULL'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function startFaceSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['login_method'] = 'face_id';
    }

    private function updateLastConnexion(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = ?'
        );
        $stmt->execute([$userId]);
    }

    private function normalizeBase64Image(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            throw new InvalidArgumentException('Image manquante');
        }

        if (str_contains($image, ',')) {
            [$meta, $image] = explode(',', $image, 2);
            if (!preg_match('#^data:image/(jpeg|jpg|png);base64$#i', $meta)) {
                throw new InvalidArgumentException('Format d\'image non autorisé');
            }
        }

        if (!preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $image)) {
            throw new InvalidArgumentException('Image invalide');
        }

        $decoded = base64_decode($image, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Image invalide');
        }

        if (strlen($decoded) > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Image trop volumineuse');
        }

        return $image;
    }

    private function logFaceppError(string $operation, ?array $response): void
    {
        $message = is_array($response) ? ($response['error_message'] ?? 'unknown error') : 'empty response';
        error_log('FaceID Face++ ' . $operation . ' error: ' . $message);
    }
}
