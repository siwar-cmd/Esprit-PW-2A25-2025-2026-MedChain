<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Utilisateur.php';

/**
 * PasswordController
 *
 * Handles forgot-password and reset-password flows.
 * All DB calls delegate to Utilisateur static repository methods.
 *
 * Routes:
 *   GET  ?controller=password&action=forgot     → show forgot form
 *   POST ?controller=password&action=sendReset  → send reset link
 *   GET  ?controller=password&action=reset&token=X → show reset form
 *   POST ?controller=password&action=doReset    → apply new password
 */
class PasswordController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Forgot password ───────────────────────────────────────────

    public function forgot(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/midchaine/index.php?office=front&controller=objet&action=list');
        }

        $message = '';
        $pageTitle = 'Mot de passe oublié';
        require VIEWS_FRONT . '/auth/forgot-password.php';
    }

    public function sendReset(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/midchaine/index.php?office=front&controller=objet&action=list');
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $message = '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = ['type' => 'error', 'text' => 'Veuillez saisir un email valide.'];
        } else {
            $user = Utilisateur::findByEmail($email, $this->db);

            // Security: always show the same message regardless of whether email exists
            if ($user) {
                $token   = $user->generateResetToken();
                Utilisateur::updateResetToken($user->getId(), $token, $user->getResetTokenExpires(), $this->db);

                $resetLink = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                           . '/midchaine/index.php?controller=password&action=reset&token='
                           . urlencode($token);

                // TODO: replace with real EmailService::send() when SMTP is configured
                error_log("[MedChain] Password reset link for {$email}: {$resetLink}");
            }

            $message = [
                'type' => 'success',
                'text' => 'Si cet email existe, un lien de réinitialisation vient d\'être envoyé.',
            ];
        }

        $pageTitle = 'Mot de passe oublié';
        require VIEWS_FRONT . '/auth/forgot-password.php';
    }

    // ── Reset password ────────────────────────────────────────────

    public function reset(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/midchaine/index.php?office=front&controller=objet&action=list');
        }

        $token = trim($_GET['token'] ?? '');
        $user  = Utilisateur::findByResetToken($token, $this->db);

        if (!$user || !$user->isResetTokenValid()) {
            $message   = ['type' => 'error', 'text' => 'Lien de réinitialisation invalide ou expiré.'];
            $pageTitle = 'Lien expiré';
            require VIEWS_FRONT . '/auth/forgot-password.php';
            return;
        }

        $errors    = [];
        $pageTitle = 'Réinitialiser le mot de passe';
        require VIEWS_FRONT . '/auth/reset-password.php';
    }

    public function doReset(): void
    {
        $token       = trim($_POST['token']        ?? '');
        $newPassword = trim($_POST['mot_de_passe'] ?? '');
        $confirm     = trim($_POST['confirm']       ?? '');

        $user = Utilisateur::findByResetToken($token, $this->db);

        if (!$user || !$user->isResetTokenValid()) {
            $message   = ['type' => 'error', 'text' => 'Lien de réinitialisation invalide ou expiré.'];
            $pageTitle = 'Lien expiré';
            require VIEWS_FRONT . '/auth/forgot-password.php';
            return;
        }

        $errors = [];
        if (strlen($newPassword) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($newPassword !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        if (!empty($errors)) {
            $pageTitle = 'Réinitialiser le mot de passe';
            require VIEWS_FRONT . '/auth/reset-password.php';
            return;
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Utilisateur::updatePassword($user->getId(), $hashed, $this->db);

        // Auto-login after successful reset
        session_regenerate_id(true);
        $_SESSION['user_id']     = $user->getId();
        $_SESSION['user_nom']    = $user->getNom();
        $_SESSION['user_prenom'] = $user->getPrenom();
        $_SESSION['user_email']  = $user->getEmail();
        $_SESSION['user_role']   = $user->getRole();
        $_SESSION['login_time']  = time();

        redirectToRoute('objet', 'list', ['office' => 'front', 'success' => 'password_reset']);
    }

    // ── Change password (logged-in user) ─────────────────────────

    public function changePassword(): void
    {
        $this->requireAuth();

        $errors  = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current    = $_POST['current_password'] ?? '';
            $newPass    = $_POST['mot_de_passe']     ?? '';
            $confirm    = $_POST['confirm']          ?? '';

            $user = Utilisateur::findById($this->currentUserId(), $this->db);

            if (!$user || !$user->validerMotDePasse($current)) {
                $errors[] = 'Mot de passe actuel incorrect.';
            } elseif (strlen($newPass) < 8) {
                $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            } elseif ($newPass !== $confirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            } else {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                Utilisateur::updatePassword($user->getId(), $hashed, $this->db);
                $success = true;
            }
        }

        $pageTitle  = 'Changer mon mot de passe';
        $currentNav = '';
        require VIEWS_FRONT . '/auth/change-password.php';
    }
}
