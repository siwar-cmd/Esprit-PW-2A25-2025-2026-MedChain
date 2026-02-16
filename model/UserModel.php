<?php
// Fichier: model/UserModel.php

class UserModel {
    private $pdo;
    
    /**
     * Constructeur - Établit la connexion à la base de données
     */
    public function __construct() {
        try {
            // Configuration de la base de données - À MODIFIER SELON VOTRE CONFIGURATION
            $host = 'localhost';
            $dbname = 'medchain_db'; // Nom de votre base de données
            $username = 'root';       // Votre utilisateur MySQL
            $password = '';           // Votre mot de passe MySQL
            
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    /**
     * Vérifie si un email existe déjà
     * @param string $email L'email à vérifier
     * @return bool True si l'email existe, false sinon
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Enregistre un nouvel utilisateur
     * @param array $userData Les données de l'utilisateur
     * @return int|false L'ID de l'utilisateur créé ou false si échec
     */
    public function register($userData) {
        try {
            $sql = "INSERT INTO users (nom, prenom, email, password, telephone, role, status, created_at) 
                    VALUES (:nom, :prenom, :email, :password, :telephone, :role, :status, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            
            $success = $stmt->execute([
                ':nom' => $userData['nom'],
                ':prenom' => $userData['prenom'],
                ':email' => $userData['email'],
                ':password' => $userData['password'], // Déjà hashé
                ':telephone' => $userData['telephone'] ?? null,
                ':role' => $userData['role'] ?? 'user',
                ':status' => $userData['status'] ?? 'actif'
            ]);
            
            if ($success) {
                return (int)$this->pdo->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'inscription : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Connecte un utilisateur
     * @param string $email L'email de l'utilisateur
     * @param string $password Le mot de passe non hashé
     * @return array|false Les données de l'utilisateur ou false si échec
     */
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email AND status = 'actif'");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']); // On supprime le mot de passe pour la session
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la connexion : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un utilisateur par son ID
     * @param int $id L'ID de l'utilisateur
     * @return array|false Les données de l'utilisateur ou false
     */
    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, nom, prenom, email, telephone, role, status, created_at 
                                          FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sauvegarde le token "Se souvenir de moi"
     * @param int $userId L'ID de l'utilisateur
     * @param string $token Le token unique
     * @param int $expiry Date d'expiration en timestamp
     * @return bool Succès ou échec
     */
    public function saveRememberToken($userId, $token, $expiry = null) {
        try {
            // Supprimer les anciens tokens
            $this->deleteRememberToken($userId);
            
            // Si pas d'expiration fournie, 30 jours par défaut
            if ($expiry === null) {
                $expiry = time() + (86400 * 30);
            }
            
            $expiryDate = date('Y-m-d H:i:s', $expiry);
            
            $sql = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':expires_at' => $expiryDate
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du token : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime le token "Se souvenir de moi" d'un utilisateur
     * @param int $userId L'ID de l'utilisateur
     * @return bool Succès ou échec
     */
    public function deleteRememberToken($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_tokens WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du token : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un utilisateur par son token "Se souvenir de moi"
     * @param string $token Le token
     * @return array|false Les données de l'utilisateur ou false
     */
    public function getUserByRememberToken($token) {
        try {
            $sql = "SELECT u.* FROM users u 
                    INNER JOIN user_tokens t ON u.id = t.user_id 
                    WHERE t.token = :token AND t.expires_at > NOW() AND u.status = 'actif'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();
            
            if ($user) {
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération par token : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour les informations d'un utilisateur
     * @param int $userId L'ID de l'utilisateur
     * @param array $data Les données à mettre à jour
     * @return bool Succès ou échec
     */
    public function updateUser($userId, $data) {
        try {
            $fields = [];
            $params = [':id' => $userId];
            
            $allowedFields = ['nom', 'prenom', 'telephone', 'adresse', 'photo'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                return true;
            }
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Change le mot de passe d'un utilisateur
     * @param int $userId L'ID de l'utilisateur
     * @param string $oldPassword Ancien mot de passe
     * @param string $newPassword Nouveau mot de passe
     * @return bool|string Succès ou message d'erreur
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Vérifier l'ancien mot de passe
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return "Ancien mot de passe incorrect";
            }
            
            // Valider le nouveau mot de passe
            if (strlen($newPassword) < 8) {
                return "Le nouveau mot de passe doit contenir au moins 8 caractères";
            }
            
            if (!preg_match('/[A-Z]/', $newPassword)) {
                return "Le nouveau mot de passe doit contenir au moins une majuscule";
            }
            
            if (!preg_match('/[0-9]/', $newPassword)) {
                return "Le nouveau mot de passe doit contenir au moins un chiffre";
            }
            
            // Mettre à jour
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            
            if ($stmt->execute([':password' => $hashedPassword, ':id' => $userId])) {
                return true;
            }
            
            return "Erreur lors du changement de mot de passe";
        } catch (PDOException $e) {
            error_log("Erreur lors du changement de mot de passe : " . $e->getMessage());
            return "Erreur technique";
        }
    }
}
?>