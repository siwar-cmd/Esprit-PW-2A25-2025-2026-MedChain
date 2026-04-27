<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';

class AuthController {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    public function register($userData): array {
        try {
            $errors = $this->validateRegistrationData($userData);
            if (!empty($errors)) {
                return ["success" => false, "message" => "Données invalides", "errors" => $errors];
            }

            if ($this->emailExists($userData['email'])) {
                return ["success" => false, "message" => "Cet email existe déjà"];
            }

            $user = new Utilisateur(
                $userData['nom'],
                $userData['prenom'],
                $userData['email'],
                $userData['mot_de_passe'],
                $userData['dateNaissance'] ?? null,
                $userData['adresse'] ?? null,
                $userData['role'] ?? 'patient',
                $userData['statut'] ?? 'actif'
            );

            if (isset($userData['telephone'])) {
                $user->setTelephone($userData['telephone']);
            }

            error_log("DEBUG - Inscription utilisateur: " . $user->getEmail());

            if ($this->saveUser($user)) {
                $freshUser = $this->findUserByEmail($userData['email']);
                
                if (!$freshUser) {
                    return ["success" => false, "message" => "Compte créé mais impossible de récupérer les données"];
                }
                
                return $this->performAutoLogin($freshUser, $userData['mot_de_passe']);
            }

            return ["success" => false, "message" => "Erreur lors de l'inscription"];
        } catch (Exception $e) {
            error_log("ERREUR register: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    private function performAutoLogin(Utilisateur $user, $plainPassword): array {
        try {
            if (!password_verify($plainPassword, $user->getMotDePasse())) {
                return [
                    "success" => true,
                    "message" => "Compte créé avec succès ! Veuillez vous connecter avec vos identifiants.",
                    "user_created" => true,
                    "email" => $user->getEmail()
                ];
            }
            
            if ($user->getStatut() !== 'actif') {
                return [
                    "success" => true,
                    "message" => "Compte créé avec succès ! Veuillez vous connecter.",
                    "user_created" => true,
                    "email" => $user->getEmail()
                ];
            }

            $this->startUserSession($user);

            return [
                "success" => true,
                "message" => "Inscription et connexion réussies !",
                "user" => [
                    'id_utilisateur' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'statut' => $user->getStatut()
                ]
            ];
        } catch (Exception $e) {
            error_log("ERREUR performAutoLogin: " . $e->getMessage());
            return [
                "success" => true,
                "message" => "Compte créé avec succès ! Veuillez vous connecter.",
                "user_created" => true,
                "email" => $user->getEmail()
            ];
        }
    }

    public function login($email, $password): array {
        try {
            $user = $this->findUserByEmail($email);
            
            if (!$user) {
                return ["success" => false, "message" => "Email ou mot de passe incorrect"];
            }
            
            if (!password_verify($password, $user->getMotDePasse())) {
                return ["success" => false, "message" => "Email ou mot de passe incorrect"];
            }
            
            if ($user->getStatut() !== 'actif') {
                if ($user->getStatut() === 'en_attente') {
                    return ["success" => false, "message" => "Votre compte est en attente d'activation"];
                }
                return ["success" => false, "message" => "Votre compte est désactivé"];
            }

            $this->startUserSession($user);
            $this->updateLastConnexion($user->getId());

            return [
                "success" => true,
                "message" => "Connexion réussie",
                "user" => [
                    'id_utilisateur' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'statut' => $user->getStatut()
                ]
            ];
        } catch (Exception $e) {
            error_log("ERREUR login: " . $e->getMessage());
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function logout(): array {
        $this->destroySession();
        return ["success" => true, "message" => "Déconnexion réussie"];
    }

    public function getCurrentUser(): ?Utilisateur {
        if (!$this->isLoggedIn()) return null;
        return $this->findUserById($_SESSION['user_id']);
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public function hasRole($role): bool {
        if (!$this->isLoggedIn()) return false;
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    private function emailExists($email, $excludeId = null): bool {
        try {
            if ($excludeId) {
                $query = "SELECT COUNT(*) FROM utilisateur WHERE email = ? AND id_utilisateur != ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$email, $excludeId]);
            } else {
                $query = "SELECT COUNT(*) FROM utilisateur WHERE email = ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$email]);
            }
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Erreur emailExists: " . $e->getMessage());
            return false;
        }
    }

    private function saveUser(Utilisateur $user): bool {
        try {
            $query = "INSERT INTO utilisateur (
                nom, prenom, email, mot_de_passe, dateNaissance, adresse, telephone,
                role, statut, date_inscription, reset_token, reset_token_expires,
                historique_connexions, derniere_connexion, photo_profil
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
            
            $stmt = $this->pdo->prepare($query);
            
            $result = $stmt->execute([
                $user->getNom(),
                $user->getPrenom(),
                $user->getEmail(),
                $user->getMotDePasse(),
                $user->getDateNaissance(),
                $user->getAdresse(),
                $user->getTelephone(),
                $user->getRole(),
                $user->getStatut(),
                $user->getDateInscription(),
                $user->getResetToken(),
                $user->getResetTokenExpires(),
                $user->getHistoriqueConnexions(),
                $user->getDerniereConnexion(),
                $user->getPhotoProfil()
            ]);
            
            if ($result) {
                $user->setId($this->pdo->lastInsertId());
                error_log("DEBUG - Utilisateur inséré avec succès, ID: " . $user->getId());
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("ERREUR saveUser: " . $e->getMessage());
            return false;
        }
    }

    private function findUserByEmail($email): ?Utilisateur {
        try {
            $query = "SELECT * FROM utilisateur WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) return null;
            
            return $this->rowToUser($row);
        } catch (Exception $e) {
            error_log("ERREUR findUserByEmail: " . $e->getMessage());
            return null;
        }
    }

    private function findUserById($id): ?Utilisateur {
        try {
            $query = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) return null;
            
            return $this->rowToUser($row);
        } catch (Exception $e) {
            error_log("Erreur findUserById: " . $e->getMessage());
            return null;
        }
    }

    private function rowToUser($row): Utilisateur {
        $user = new Utilisateur(
            $row['nom'],
            $row['prenom'],
            $row['email'],
            '', // mot de passe sera défini séparément
            $row['dateNaissance'] ?? null,
            $row['adresse'] ?? null,
            $row['role'] ?? 'patient',
            $row['statut'] ?? 'actif'
        );
        
        $user->setId($row['id_utilisateur']);
        $user->setMotDePasse($row['mot_de_passe'], true);
        $user->setDateInscription($row['date_inscription'] ?? date('Y-m-d H:i:s'));
        $user->setResetToken($row['reset_token'] ?? null);
        $user->setResetTokenExpires($row['reset_token_expires'] ?? null);
        $user->setHistoriqueConnexions($row['historique_connexions'] ?? null);
        $user->setDerniereConnexion($row['derniere_connexion'] ?? null);
        $user->setPhotoProfil($row['photo_profil'] ?? null);
        $user->setTelephone($row['telephone'] ?? null);
        
        return $user;
    }
    
    private function updateLastConnexion($userId): void {
        try {
            $query = "UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Erreur updateLastConnexion: " . $e->getMessage());
        }
    }

    private function validateRegistrationData($data): array {
        $errors = [];

        if (empty(trim($data['nom']))) {
            $errors['nom'] = 'Le nom est obligatoire';
        } elseif (strlen(trim($data['nom'])) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        }

        if (empty(trim($data['prenom']))) {
            $errors['prenom'] = 'Le prénom est obligatoire';
        } elseif (strlen(trim($data['prenom'])) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        if (empty($data['mot_de_passe']) || strlen($data['mot_de_passe']) < 6) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 6 caractères';
        }

        if (!empty($data['telephone'])) {
            if (!preg_match('/^[0-9+\-\s]{8,20}$/', $data['telephone'])) {
                $errors['telephone'] = 'Format de téléphone invalide';
            }
        }

        if (!empty($data['dateNaissance'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['dateNaissance']);
            if (!$date || $date->format('Y-m-d') !== $data['dateNaissance']) {
                $errors['dateNaissance'] = 'Date de naissance invalide';
            }
        }

        return $errors;
    }

    private function startUserSession(Utilisateur $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_nom'] = $user->getNom();
        $_SESSION['user_prenom'] = $user->getPrenom();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['login_time'] = time();
        
        error_log("DEBUG - Session démarrée pour: " . $user->getEmail());
    }

    private function destroySession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        
        error_log("DEBUG - Session détruite");
    }
}