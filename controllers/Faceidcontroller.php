<?php
/**
 * FaceIdController.php
 * Gestion de l'authentification par reconnaissance faciale via Face++ API
 * 
 * API utilisée : Face++ (Megvii)
 * Lien : https://www.faceplusplus.com/
 * Documentation : https://console.faceplusplus.com/documents/5679127
 * 
 * Étapes pour obtenir les clés API :
 * 1. Créer un compte sur https://www.faceplusplus.com/
 * 2. Aller dans "Apps" → "Create App"
 * 3. Copier API_KEY et API_SECRET dans config.php
 */

require_once __DIR__ . '/../config.php';

class FaceIdController {

    // ─── Endpoints Face++ ───────────────────────────────────────────────────
    private const FACEPP_DETECT    = 'https://api-us.faceplusplus.com/facepp/v3/detect';
    private const FACEPP_COMPARE   = 'https://api-us.faceplusplus.com/facepp/v3/compare';
    private const FACEPP_FACESET_CREATE  = 'https://api-us.faceplusplus.com/facepp/v3/faceset/create';
    private const FACEPP_FACESET_ADD     = 'https://api-us.faceplusplus.com/facepp/v3/faceset/addface';
    private const FACEPP_FACESET_REMOVE  = 'https://api-us.faceplusplus.com/facepp/v3/faceset/removeface';

    private $pdo;
    private $apiKey;
    private $apiSecret;

    public function __construct() {
        $this->pdo       = config::getConnexion();
        $this->apiKey    = defined('FACEPP_API_KEY')    ? FACEPP_API_KEY    : '';
        $this->apiSecret = defined('FACEPP_API_SECRET') ? FACEPP_API_SECRET : '';
    }

    // ════════════════════════════════════════════════════════════════════════
    //  1.  ENREGISTREMENT DU VISAGE (lors du profil ou de l'inscription)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Enregistre le visage d'un utilisateur à partir d'une image base64.
     * Appelé depuis save_face.php
     *
     * @param int    $userId     ID de l'utilisateur en BDD
     * @param string $imageBase64 Image capturée (data:image/jpeg;base64,...)
     * @return array ['success' => bool, 'message' => string]
     */
    public function enrollFace(int $userId, string $imageBase64): array {
        try {
            // 1. Nettoyer le préfixe data URI
            $imageData = $this->stripBase64Prefix($imageBase64);

            // 2. Détecter le visage via Face++
            $detection = $this->detectFace($imageData);
            if (!$detection['success']) {
                return $detection;
            }
            $faceToken = $detection['face_token'];

            // 3. Sauvegarder le face_token en base de données
            $this->saveFaceToken($userId, $faceToken, $imageData);

            return [
                'success' => true,
                'message' => 'Visage enregistré avec succès',
                'face_token' => $faceToken
            ];

        } catch (Exception $e) {
            error_log('ERREUR enrollFace: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du visage'];
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  2.  VÉRIFICATION / CONNEXION PAR VISAGE
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Vérifie le visage capturé par rapport au visage enregistré d'un utilisateur.
     * Retourne les données de session si la correspondance est confirmée.
     *
     * @param string $imageBase64  Image webcam (data:image/jpeg;base64,...)
     * @param string $email        Email saisi dans le formulaire de login
     * @return array
     */
    public function verifyFaceLogin(string $imageBase64, string $email): array {
        try {
            // 1. Vérifier que l'utilisateur existe et a un visage enregistré
            $user = $this->getUserWithFace($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Aucun visage enregistré pour cet email'];
            }

            // 2. Nettoyer l'image capturée
            $capturedData = $this->stripBase64Prefix($imageBase64);

            // 3. Comparer via Face++ (image capturée vs image stockée)
            $comparison = $this->compareFaces($capturedData, $user['face_image_data']);
            if (!$comparison['success']) {
                return $comparison;
            }

            // 4. Vérifier le seuil de confiance (80% minimum recommandé)
            $confidence = $comparison['confidence'];
            $threshold  = 80.0;

            if ($confidence < $threshold) {
                return [
                    'success'    => false,
                    'message'    => 'Visage non reconnu. Veuillez réessayer ou utiliser votre mot de passe.',
                    'confidence' => $confidence
                ];
            }

            // 5. Vérifier le statut du compte
            if ($user['statut'] !== 'actif') {
                return ['success' => false, 'message' => 'Votre compte est désactivé'];
            }

            // 6. Démarrer la session
            $this->startFaceSession($user);
            $this->updateLastConnexion($user['id_utilisateur']);

            return [
                'success'    => true,
                'message'    => 'Connexion par Face ID réussie !',
                'confidence' => $confidence,
                'user' => [
                    'id_utilisateur' => $user['id_utilisateur'],
                    'nom'    => $user['nom'],
                    'prenom' => $user['prenom'],
                    'email'  => $user['email'],
                    'role'   => $user['role'],
                    'statut' => $user['statut']
                ]
            ];

        } catch (Exception $e) {
            error_log('ERREUR verifyFaceLogin: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la vérification faciale'];
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  3.  SUPPRESSION DU VISAGE ENREGISTRÉ
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Supprime le visage enregistré d'un utilisateur.
     */
    public function deleteFace(int $userId): array {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE utilisateur SET face_token = NULL, face_image_data = NULL WHERE id_utilisateur = ?"
            );
            $stmt->execute([$userId]);

            return ['success' => true, 'message' => 'Visage supprimé avec succès'];
        } catch (Exception $e) {
            error_log('ERREUR deleteFace: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }

    /**
     * Vérifie si un utilisateur a un visage enregistré.
     */
    public function hasFaceRegistered(int $userId): bool {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT face_token FROM utilisateur WHERE id_utilisateur = ? AND face_token IS NOT NULL"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  MÉTHODES PRIVÉES — Appels Face++ API
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Détecte un visage dans une image et retourne son face_token.
     */
    private function detectFace(string $imageBase64): array {
        $response = $this->callFaceppApi(self::FACEPP_DETECT, [
            'image_base64' => $imageBase64,
            'return_attributes' => 'none'
        ]);

        if (!$response || isset($response['error_message'])) {
            $msg = $response['error_message'] ?? 'Erreur API Face++';
            error_log('Face++ detect error: ' . $msg);
            return ['success' => false, 'message' => 'Erreur de détection : ' . $msg];
        }

        if (empty($response['faces'])) {
            return ['success' => false, 'message' => 'Aucun visage détecté. Assurez-vous d\'être bien en face de la caméra.'];
        }

        if (count($response['faces']) > 1) {
            return ['success' => false, 'message' => 'Plusieurs visages détectés. Restez seul dans le cadre.'];
        }

        return [
            'success'    => true,
            'face_token' => $response['faces'][0]['face_token']
        ];
    }

    /**
     * Compare deux images via Face++ et retourne le score de confiance.
     */
    private function compareFaces(string $image1Base64, string $image2Base64): array {
        $response = $this->callFaceppApi(self::FACEPP_COMPARE, [
            'image_base64_1' => $image1Base64,
            'image_base64_2' => $image2Base64
        ]);

        if (!$response || isset($response['error_message'])) {
            $msg = $response['error_message'] ?? 'Erreur API Face++';
            error_log('Face++ compare error: ' . $msg);

            // Gérer le cas où aucun visage n'est détecté dans l'image en temps réel
            if (str_contains($msg, 'NO_FACE_FOUND')) {
                return ['success' => false, 'message' => 'Aucun visage détecté dans la caméra. Regardez bien l\'objectif.'];
            }

            return ['success' => false, 'message' => 'Erreur de comparaison : ' . $msg];
        }

        return [
            'success'    => true,
            'confidence' => (float) ($response['confidence'] ?? 0)
        ];
    }

    /**
     * Effectue un appel HTTP POST vers l'API Face++.
     */
    private function callFaceppApi(string $url, array $params): ?array {
        $params['api_key']    = $this->apiKey;
        $params['api_secret'] = $this->apiSecret;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,       // multipart/form-data (correct pour Face++)
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'FaceIDApp/1.0 PHP/' . PHP_VERSION,
            // Désactiver Expect: 100-continue qui peut bloquer certains serveurs
            CURLOPT_HTTPHEADER     => ['Expect:'],
        ]);

        $result   = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log('cURL error Face++ [' . $url . ']: ' . $error);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('Face++ HTTP ' . $httpCode . ' [' . $url . ']: ' . $result);
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Face++ JSON decode error: ' . $result);
            return null;
        }

        return $decoded;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  MÉTHODES PRIVÉES — Base de données
    // ════════════════════════════════════════════════════════════════════════

    private function saveFaceToken(int $userId, string $faceToken, string $imageData): void {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateur 
             SET face_token = ?, face_image_data = ?, face_registered_at = NOW()
             WHERE id_utilisateur = ?"
        );
        $stmt->execute([$faceToken, $imageData, $userId]);
    }

    private function getUserWithFace(string $email): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT id_utilisateur, nom, prenom, email, role, statut, face_token, face_image_data
             FROM utilisateur
             WHERE email = ? AND face_image_data IS NOT NULL"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function startFaceSession(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']     = $user['id_utilisateur'];
        $_SESSION['user_nom']    = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_role']   = $user['role'];
        $_SESSION['login_time']  = time();
        $_SESSION['login_method'] = 'face_id';
    }

    private function updateLastConnexion(int $userId): void {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = ?"
        );
        $stmt->execute([$userId]);
    }

    private function stripBase64Prefix(string $base64): string {
        // Supprimer "data:image/jpeg;base64," ou similaire
        if (str_contains($base64, ',')) {
            return explode(',', $base64, 2)[1];
        }
        return $base64;
    }
}