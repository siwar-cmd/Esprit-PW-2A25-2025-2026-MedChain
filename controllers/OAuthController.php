<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';

class OAuthController {

    // =========================================================================
    //  GOOGLE
    // =========================================================================

    // 🔵 FACEBOOK

    // =========================================================================
    //  URLs
    // =========================================================================
    private const GOOGLE_REDIRECT_URI   = 'http://localhost/projet/views/frontoffice/auth/google-callback.php';
    private const GOOGLE_AUTH_URL       = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL      = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_USERINFO_URL   = 'https://www.googleapis.com/oauth2/v3/userinfo';

    private const FACEBOOK_REDIRECT_URI = 'http://localhost/projet/views/frontoffice/auth/facebook-callback.php';
    private const FACEBOOK_AUTH_URL     = 'https://www.facebook.com/v19.0/dialog/oauth';
    private const FACEBOOK_TOKEN_URL    = 'https://graph.facebook.com/v19.0/oauth/access_token';
    private const FACEBOOK_USERINFO_URL = 'https://graph.facebook.com/me?fields=id,name,first_name,last_name,picture';

    private $pdo;
    private string $googleClientId;
    private string $googleClientSecret;
    private string $facebookAppId;
    private string $facebookAppSecret;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->googleClientId = Env::required('GOOGLE_CLIENT_ID');
        $this->googleClientSecret = Env::required('GOOGLE_CLIENT_SECRET');
        $this->facebookAppId = Env::required('FACEBOOK_APP_ID');
        $this->facebookAppSecret = Env::required('FACEBOOK_APP_SECRET');
        $this->pdo = config::getConnexion();
    }

    // =========================================================================
    //  GOOGLE
    // =========================================================================
    public function getGoogleAuthUrl(): string {
        $state = $this->generateState();
        $_SESSION['oauth_state_google'] = $state;

        $params = http_build_query([
            'client_id'     => $this->googleClientId,
            'redirect_uri'  => self::GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'profile email openid',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return self::GOOGLE_AUTH_URL . '?' . $params;
    }

    public function handleGoogleCallback(string $code, string $state): array {
        if (!$this->validateState($state, 'google')) {
            return ['success' => false, 'message' => 'Requête invalide (CSRF détecté).'];
        }
        unset($_SESSION['oauth_state_google']);

        $tokenData = $this->exchangeCodeForToken(
            $code,
            self::GOOGLE_TOKEN_URL,
            $this->googleClientId,
            $this->googleClientSecret,
            self::GOOGLE_REDIRECT_URI
        );

        if (!$tokenData || empty($tokenData['access_token'])) {
            return ['success' => false, 'message' => 'Impossible d\'obtenir le token Google.'];
        }

        $profile = $this->fetchUserProfile(self::GOOGLE_USERINFO_URL, $tokenData['access_token']);

        if (!$profile || empty($profile['email'])) {
            return ['success' => false, 'message' => 'Impossible de récupérer le profil Google.'];
        }

        return $this->findOrCreateOAuthUser([
            'email'          => $profile['email'],
            'nom'            => $profile['family_name']  ?? explode(' ', $profile['name'] ?? 'Utilisateur')[1] ?? 'Utilisateur',
            'prenom'         => $profile['given_name']   ?? explode(' ', $profile['name'] ?? 'Utilisateur')[0] ?? 'Google',
            'oauth_id'       => $profile['sub'],
            'oauth_provider' => 'google',
            'photo'          => $profile['picture'] ?? null,
        ]);
    }

    // =========================================================================
    //  FACEBOOK
    // =========================================================================
    public function getFacebookAuthUrl(): string {
        $state = $this->generateState();
        $_SESSION['oauth_state_facebook'] = $state;

        $params = http_build_query([
            'client_id'     => $this->facebookAppId,
            'redirect_uri'  => self::FACEBOOK_REDIRECT_URI,
            'scope'         => 'public_profile',
            'state'         => $state,
            'response_type' => 'code',
        ]);

        return self::FACEBOOK_AUTH_URL . '?' . $params;
    }

    public function handleFacebookCallback(string $code, string $state): array {
        // Validation CSRF réactivée (sécurité indispensable)
        if (!$this->validateState($state, 'facebook')) {
            return ['success' => false, 'message' => 'Requête invalide (CSRF détecté).'];
        }
        unset($_SESSION['oauth_state_facebook']);

        $tokenData = $this->exchangeCodeForToken(
            $code,
            self::FACEBOOK_TOKEN_URL,
            $this->facebookAppId,
            $this->facebookAppSecret,
            self::FACEBOOK_REDIRECT_URI
        );

        if (!$tokenData || empty($tokenData['access_token'])) {
            return ['success' => false, 'message' => 'Impossible d\'obtenir le token Facebook.'];
        }

        $url     = self::FACEBOOK_USERINFO_URL . '&access_token=' . urlencode($tokenData['access_token']);
        $profile = $this->fetchUserProfile($url, null);

        if (!$profile || empty($profile['id'])) {
            return ['success' => false, 'message' => 'Impossible de récupérer le profil Facebook.'];
        }

        // Génération d'un email fictif si Facebook ne renvoie pas l'email (scope public_profile)
        $email = $profile['email'] ?? ('fb_' . $this->facebookAppId . '_' . $profile['id'] . '@facebook.local');

        return $this->findOrCreateOAuthUser([
            'email'          => $email,
            'nom'            => $profile['last_name']  ?? explode(' ', $profile['name'] ?? 'Utilisateur')[1] ?? 'Utilisateur',
            'prenom'         => $profile['first_name'] ?? explode(' ', $profile['name'] ?? 'Utilisateur')[0] ?? 'Facebook',
            'oauth_id'       => $profile['id'],
            'oauth_provider' => 'facebook',
            'photo'          => $profile['picture']['data']['url'] ?? null,
        ]);
    }

    // =========================================================================
    //  LOGIQUE COMMUNE (avec réactivation automatique)
    // =========================================================================
    private function findOrCreateOAuthUser(array $oauthData): array {
        try {
            $user = $this->findUserByEmail($oauthData['email']);

            if ($user) {
                // Si le compte est désactivé, on le réactive automatiquement
                if ($user->getStatut() !== 'actif') {
                    $this->reactivateUser($user->getId());
                    // Recharger l'utilisateur pour obtenir le statut mis à jour
                    $user = $this->findUserByEmail($oauthData['email']);
                    if (!$user || $user->getStatut() !== 'actif') {
                        return ['success' => false, 'message' => 'Impossible de réactiver votre compte.'];
                    }
                }
                $this->updateLastConnexion($user->getId());
                $this->startUserSession($user);
                return [
                    'success' => true,
                    'message' => 'Connexion réussie via ' . ucfirst($oauthData['oauth_provider']) . ' !',
                    'user'    => $this->userToArray($user),
                    'is_new'  => false,
                ];
            }

            // Création d'un nouvel utilisateur
            $created = $this->createOAuthUser($oauthData);
            if (!$created['success']) return $created;

            $newUser = $this->findUserByEmail($oauthData['email']);
            if (!$newUser) {
                return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
            }

            $this->startUserSession($newUser);
            return [
                'success' => true,
                'message' => 'Compte créé et connexion réussie via ' . ucfirst($oauthData['oauth_provider']) . ' !',
                'user'    => $this->userToArray($newUser),
                'is_new'  => true,
            ];

        } catch (Exception $e) {
            error_log('OAuthController::findOrCreateOAuthUser - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la connexion OAuth.'];
        }
    }

    /**
     * Réactive un compte désactivé
     */
    private function reactivateUser(int $userId): void {
        try {
            $stmt = $this->pdo->prepare('UPDATE utilisateur SET statut = "actif" WHERE id_utilisateur = ?');
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log('reactivateUser - ' . $e->getMessage());
        }
    }

    private function createOAuthUser(array $data): array {
        try {
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, statut, date_inscription, photo_profil)
                    VALUES (?, ?, ?, ?, 'patient', 'actif', NOW(), ?)";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $randomPassword, $data['photo'] ?? null,
            ]);
            return $success
                ? ['success' => true, 'id' => $this->pdo->lastInsertId()]
                : ['success' => false, 'message' => 'Échec de l\'insertion en base.'];
        } catch (Exception $e) {
            error_log('OAuthController::createOAuthUser - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()];
        }
    }

    // =========================================================================
    //  HELPERS HTTP
    // =========================================================================
    private function exchangeCodeForToken(string $code, string $tokenUrl, string $clientId, string $clientSecret, string $redirectUri): ?array {
        $postData = http_build_query([
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("exchangeCodeForToken HTTP $httpCode — $response");
            return null;
        }
        return json_decode($response, true);
    }

    private function fetchUserProfile(string $url, ?string $accessToken): ?array {
        $headers = ['Accept: application/json'];
        if ($accessToken) $headers[] = 'Authorization: Bearer ' . $accessToken;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("fetchUserProfile HTTP $httpCode — $response");
            return null;
        }
        return json_decode($response, true);
    }

    // =========================================================================
    //  SÉCURITÉ / SESSION
    // =========================================================================
    private function generateState(): string {
        return bin2hex(random_bytes(16));
    }

    private function validateState(string $state, string $provider): bool {
        $sessionKey = 'oauth_state_' . $provider;
        if (!isset($_SESSION[$sessionKey])) {
            error_log("oauth_state manquant en session pour le provider : $provider");
            return false;
        }
        return hash_equals($_SESSION[$sessionKey], $state);
    }

    private function startUserSession(Utilisateur $user): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user_id']     = $user->getId();
        $_SESSION['user_nom']    = $user->getNom();
        $_SESSION['user_prenom'] = $user->getPrenom();
        $_SESSION['user_email']  = $user->getEmail();
        $_SESSION['user_role']   = $user->getRole();
        $_SESSION['login_time']  = time();
    }

    // =========================================================================
    //  BASE DE DONNÉES
    // =========================================================================
    private function findUserByEmail(string $email): ?Utilisateur {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->rowToUser($row) : null;
        } catch (Exception $e) {
            error_log('findUserByEmail - ' . $e->getMessage());
            return null;
        }
    }

    private function updateLastConnexion(int $userId): void {
        try {
            $stmt = $this->pdo->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = ?');
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log('updateLastConnexion - ' . $e->getMessage());
        }
    }

    private function rowToUser(array $row): Utilisateur {
        $user = new Utilisateur(
            $row['nom'], $row['prenom'], $row['email'], '',
            $row['dateNaissance'] ?? null, $row['adresse'] ?? null,
            $row['role'] ?? 'patient', $row['statut'] ?? 'actif'
        );
        $user->setId($row['id_utilisateur']);
        $user->setMotDePasse($row['mot_de_passe'], true);
        $user->setDateInscription($row['date_inscription'] ?? date('Y-m-d H:i:s'));
        $user->setPhotoProfil($row['photo_profil'] ?? null);
        $user->setTelephone($row['telephone'] ?? null);
        return $user;
    }

    private function userToArray(Utilisateur $user): array {
        return [
            'id_utilisateur' => $user->getId(),
            'nom'            => $user->getNom(),
            'prenom'         => $user->getPrenom(),
            'email'          => $user->getEmail(),
            'role'           => $user->getRole(),
            'statut'         => $user->getStatut(),
        ];
    }
}
