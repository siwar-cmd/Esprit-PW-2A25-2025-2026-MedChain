<?php

require_once __DIR__ . '/config/env.php';

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            try {
                $host = Env::get('DB_HOST', 'localhost');
                $dbname = Env::get('DB_NAME', 'user');
                $user = Env::get('DB_USER', 'root');
                $password = Env::get('DB_PASSWORD', '');

                self::$pdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbname),
                    $user,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (Exception $e) {
                die('Erreur: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}

// ── Face++ API Credentials ────────────────────────────────────────────────
// Lien pour obtenir vos clés :
// 1. Créer un compte sur  https://www.faceplusplus.com/
// 2. Aller dans           https://console.faceplusplus.com/app/apikey/list
// 3. Cliquer "Create API Key" et copier les valeurs ci-dessous

define('FACEPP_API_KEY',    'QX_4CwIk4Rtqm0sSLsyQQ682DhJinR4r');     // <-- remplacez
define('FACEPP_API_SECRET', 'fOD52_tOhbLgQP9CospCR8-KqZ1zRcau');  // <-- remplacez

// Seuil de confiance minimum pour valider une correspondance (0-100)
// 80 = recommandé par Face++ pour un contexte médical sécurisé
define('FACEPP_CONFIDENCE_THRESHOLD', 80.0);