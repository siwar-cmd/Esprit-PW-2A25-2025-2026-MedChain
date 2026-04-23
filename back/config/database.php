<?php
class Database {
    private $host = 'localhost';
    private $dbname = '2A25';
    private $username = 'root';
    private $password = '';
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4", 
                                $this->username, 
                                $this->password,
                                [
                                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                    PDO::ATTR_EMULATE_PREPARES => false
                                ]);
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            die("Erreur de requête: " . $e->getMessage());
        }
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?>
