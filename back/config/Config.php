<?php
class Config {
    private static $pdo = null;

    public static function getConnexion() {
        if (self::$pdo === null) {
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "2A25";
            
            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $e) {
                die("Erreur de connexion: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
