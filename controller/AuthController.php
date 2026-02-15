<?php
// Fichier: controller/AuthController.php

require_once 'model/UserModel.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new UserModel();
    }
    
    /**
     * Afficher le formulaire de connexion
     */
    public function showLogin() {
        // Si déjà connecté, rediriger vers le profil
        if (isset($_SESSION['user_id'])) {
            $this->redirectBasedOnRole();
            exit();
        }
        
        $error = $_GET['error'] ?? '';
        $success = $_GET['success'] ?? '';
        require_once 'view/frontoffice/auth/login.php';
    }
    
    /**
     * Traiter la connexion
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=showLogin');
            exit();
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation des champs
        $error = '';
        
        if (empty($email)) {
            $error = "L'email est requis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format d'email invalide";
        } elseif (empty($password)) {
            $error = "Le mot de passe est requis";
        }
        
        if (!empty($error)) {
            require_once 'view/frontoffice/auth/login.php';
            return;
        }
        
        $user = $this->userModel->login($email, $password);
        
        if ($user && isset($user['id'])) {
            // Connexion réussie
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_nom'] = htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
            $_SESSION['user_email'] = htmlspecialchars($user['email']);
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user_status'] = $user['status'] ?? 'actif';
            $_SESSION['user_photo'] = $user['photo'] ?? null;
            
            // Se souvenir de moi
            if ($remember) {
                $remember_token = bin2hex(random_bytes(32));
                $expiry = time() + (86400 * 30);
                setcookie('remember_token', $remember_token, $expiry, '/', '', false, true);
                $this->userModel->saveRememberToken($user['id'], $remember_token, $expiry);
            }
            
            $this->redirectBasedOnRole();
            exit();
        } else {
            $error = "Email ou mot de passe incorrect";
            require_once 'view/frontoffice/auth/login.php';
        }
    }
    
    /**
     * Afficher le formulaire d'inscription
     */
    public function showRegister() {
        // Si déjà connecté, rediriger vers le profil
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=profile');
            exit();
        }
        
        $errors = [];
        $form_data = [];
        require_once 'view/frontoffice/auth/register.php';
    }
    
    /**
     * Traiter l'inscription
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=showRegister');
            exit();
        }
        
        // Récupération et nettoyage des données
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['password_confirm'] ?? '';
        $telephone = trim($_POST['telephone'] ?? '');
        $date_naissance = $_POST['date_naissance'] ?? null;
        $sexe = $_POST['sexe'] ?? null;
        $adresse = trim($_POST['adresse'] ?? '');
        $role = $_POST['role'] ?? 'user';
        
        $form_data = compact('nom', 'prenom', 'email', 'telephone', 'date_naissance', 'sexe', 'adresse');
        $errors = [];
        
        // Validation du nom
        if (empty($nom)) {
            $errors[] = "Le nom est requis";
        } elseif (strlen($nom) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères";
        } elseif (strlen($nom) > 50) {
            $errors[] = "Le nom ne doit pas dépasser 50 caractères";
        }
        
        // Validation du prénom
        if (empty($prenom)) {
            $errors[] = "Le prénom est requis";
        } elseif (strlen($prenom) < 2) {
            $errors[] = "Le prénom doit contenir au moins 2 caractères";
        } elseif (strlen($prenom) > 50) {
            $errors[] = "Le prénom ne doit pas dépasser 50 caractères";
        }
        
        // Validation de l'email
        if (empty($email)) {
            $errors[] = "L'email est requis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide";
        } elseif (strlen($email) > 100) {
            $errors[] = "L'email ne doit pas dépasser 100 caractères";
        } elseif ($this->userModel->emailExists($email)) {
            $errors[] = "Cet email est déjà utilisé";
        }
        
        // Validation du mot de passe
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif (strlen($password) > 255) {
            $errors[] = "Le mot de passe est trop long";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        // Confirmation du mot de passe
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        
        // Validation du téléphone (optionnel)
        if (!empty($telephone)) {
            $telephone_clean = preg_replace('/[^0-9+]/', '', $telephone);
            if (strlen($telephone_clean) < 8 || strlen($telephone_clean) > 15) {
                $errors[] = "Le numéro de téléphone n'est pas valide";
            }
        }
        
        // Validation de la date de naissance
        if (!empty($date_naissance)) {
            $date = DateTime::createFromFormat('Y-m-d', $date_naissance);
            if (!$date || $date->format('Y-m-d') !== $date_naissance) {
                $errors[] = "La date de naissance n'est pas valide";
            } elseif ($date > new DateTime()) {
                $errors[] = "La date de naissance ne peut pas être dans le futur";
            }
        }
        
        // Si pas d'erreurs, on inscrit
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $userData = [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'prenom' => htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
                'password' => $hashedPassword,
                'telephone' => !empty($telephone) ? htmlspecialchars($telephone) : null,
                'role' => $role,
                'status' => 'actif'
            ];
            
            $userId = $this->userModel->register($userData);
            
            if ($userId) {
                header('Location: index.php?action=showLogin&success=registered');
                exit();
            } else {
                $errors[] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
                error_log("Erreur d'inscription pour l'email: " . $email);
            }
        }
        
        // En cas d'erreur, réafficher le formulaire
        require_once 'view/frontoffice/auth/register.php';
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        // Supprimer le cookie remember_token
        setcookie('remember_token', '', time() - 3600, '/');
        
        // Détruire la session
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        header('Location: index.php?action=showLogin&logout=success');
        exit();
    }
    
    /**
     * Afficher le profil utilisateur
     */
    public function showProfile() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=showLogin');
            exit();
        }
        
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        if (!$user) {
            session_destroy();
            header('Location: index.php?action=showLogin');
            exit();
        }
        
        require_once 'view/frontoffice/profile.php';
    }
    
    /**
     * Redirige selon le rôle de l'utilisateur
     */
    private function redirectBasedOnRole() {
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: index.php?action=profile');
        }
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     * @return bool
     */
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>