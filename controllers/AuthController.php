<?php
<<<<<<< Updated upstream
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
=======
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Database.php';
>>>>>>> Stashed changes
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../core/BaseController.php';

/**
 * AuthController — Handles login, registration, logout and session management.
 *
 * All database interactions are delegated to the Utilisateur model.
 * This controller is clean of raw SQL — strict MVC.
 */
class AuthController extends BaseController
{
    private PDO $db;

<<<<<<< Updated upstream
    public function __construct()
    {
        $this->db = Database::getInstance();
=======
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
>>>>>>> Stashed changes
    }

    // ──────────────────────────────────────────────────────────────
    //  PUBLIC ACTIONS
    // ──────────────────────────────────────────────────────────────

    /**
     * GET  → show login form
     * POST → process login credentials
     */
    public function login(): void
    {
        // Already logged in? Redirect to the right dashboard
        if ($this->isLoggedIn()) {
            $this->redirectAfterLogin();
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password = trim($_POST['password'] ?? '');

            $result = $this->attemptLogin($email, $password);

            if ($result['success']) {
                $this->redirectAfterLogin();
            }

            $error = $result['message'];
        }

        $this->view(VIEWS_FRONT . '/auth/login.php', ['error' => $error]);
    }

    /**
     * GET  → show registration form
     * POST → process registration
     */
    public function register(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirectAfterLogin();
        }

        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $_POST;
            $errors = $this->validateRegistrationData($_POST);

            if (empty($errors)) {
                $result = $this->createAccount($_POST);

                if ($result['success']) {
                    $this->redirectAfterLogin();
                }

                $errors['general'] = $result['message'];
            }
        }

        $this->view(VIEWS_FRONT . '/auth/register.php', ['errors' => $errors, 'old' => $old]);
    }

    /**
     * Destroy session and redirect to login.
     */
    public function logout(): never
    {
        $this->destroySession();
        $this->redirect('/midchaine/index.php?controller=auth&action=login');
    }

    // ──────────────────────────────────────────────────────────────
    //  CORE AUTH LOGIC (private)
    // ──────────────────────────────────────────────────────────────

    private function attemptLogin(string $email, string $password): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        $user = Utilisateur::findByEmail($email, $this->db);

        if ($user === null) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        if (!password_verify($password, $user->getMotDePasse())) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        if ($user->getStatut() === 'en_attente') {
            return ['success' => false, 'message' => 'Votre compte est en attente d\'activation par un administrateur.'];
        }

        if ($user->getStatut() !== 'actif') {
            return ['success' => false, 'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.'];
        }

        $this->startUserSession($user);
        Utilisateur::updateLastLogin($user->getId(), $this->db);

        return ['success' => true];
    }

    private function createAccount(array $data): array
    {
        // Check email uniqueness
        if (Utilisateur::emailExists($data['email'], $this->db)) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }

        $user = new Utilisateur(
            trim($data['nom']),
            trim($data['prenom']),
            strtolower(trim($data['email'])),
            $data['mot_de_passe'],          // hashed in Utilisateur constructor
            $data['dateNaissance']  ?? null,
            $data['adresse']        ?? null,
            'patient',                      // self-registration always creates a patient
            'actif'
        );

        if (!empty($data['telephone'])) {
            $user->setTelephone($data['telephone']);
        }

        $id = Utilisateur::insert($user, $this->db);

        if ($id === null) {
            return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
        }

        $user->setId($id);
        $this->startUserSession($user);

        return ['success' => true];
    }

    // ──────────────────────────────────────────────────────────────
    //  SESSION MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    private function startUserSession(Utilisateur $user): void
    {
        session_regenerate_id(true);           // Prevent session fixation

        $_SESSION['user_id']     = $user->getId();
        $_SESSION['user_nom']    = $user->getNom();
        $_SESSION['user_prenom'] = $user->getPrenom();
        $_SESSION['user_email']  = $user->getEmail();
        $_SESSION['user_role']   = $user->getRole();
        $_SESSION['login_time']  = time();
    }

    private function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    private function redirectAfterLogin(): never
    {
        $role = $_SESSION['user_role'] ?? 'patient';

        match ($role) {
            'admin'   => $this->redirect('/midchaine/index.php?office=back&controller=admin&action=dashboard'),
            'medecin' => $this->redirect('/midchaine/index.php?office=front&controller=objet&action=list'),
            default   => $this->redirect('/midchaine/index.php?office=front&controller=objet&action=list'),
        };
    }

    // ──────────────────────────────────────────────────────────────
    //  VALIDATION
    // ──────────────────────────────────────────────────────────────

    private function validateRegistrationData(array $data): array
    {
        $errors = [];

        if (empty(trim($data['nom'] ?? '')) || mb_strlen(trim($data['nom'])) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (empty(trim($data['prenom'] ?? '')) || mb_strlen(trim($data['prenom'])) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères.';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Veuillez saisir un email valide.';
        }

        if (empty($data['mot_de_passe']) || strlen($data['mot_de_passe']) < 8) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if (!empty($data['telephone']) && !preg_match('/^[0-9+\-\s]{8,20}$/', $data['telephone'])) {
            $errors['telephone'] = 'Format de téléphone invalide.';
        }

        if (!empty($data['dateNaissance'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $data['dateNaissance']);
            if (!$d || $d->format('Y-m-d') !== $data['dateNaissance']) {
                $errors['dateNaissance'] = 'Date de naissance invalide.';
            }
        }

        return $errors;
    }
}
