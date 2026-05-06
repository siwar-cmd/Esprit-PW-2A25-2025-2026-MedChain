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
