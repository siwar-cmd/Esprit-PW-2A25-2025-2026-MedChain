<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Utilisateur.php';

/**
 * ProfileController
 *
 * Front-office controller for patient/medecin profile management.
 * All DB operations delegate to Utilisateur static repository.
 * Photo uploads are handled here (business logic), but file I/O is self-contained.
 *
 * Routes:
 *   GET  ?controller=utilisateur&action=profile  → show profile
 *   POST ?controller=utilisateur&action=update   → update info
 *   POST ?controller=utilisateur&action=photo    → upload photo
 *   POST ?controller=utilisateur&action=deletePhoto → remove photo
 */
class ProfileController extends BaseController
{
    private PDO    $db;
    private string $uploadDir;
    private string $webUploadPath;

    public function __construct()
    {
        $this->db            = Database::getInstance();
        $this->uploadDir     = BASE_PATH . '/user/uploads/profiles/';
        $this->webUploadPath = '/midchaine/user/uploads/profiles/';
        $this->ensureUploadDir();
    }

    // ── Profile views ─────────────────────────────────────────────

    public function profile(): void
    {
        $this->requireAuth();

        $user   = Utilisateur::findById($this->currentUserId(), $this->db);
        $errors = [];

        if (!$user) {
            // Session is corrupt — log out
            $this->redirect('/midchaine/index.php?controller=auth&action=logout');
        }

        $pageTitle  = 'Mon Profil';
        $currentNav = '';
        require VIEWS_FRONT . '/profile/profile.php';
    }

    // ── Update profile info ───────────────────────────────────────

    public function update(): void
    {
        $this->requireAuth();

        $userId = $this->currentUserId();
        $errors = [];

        // Basic validation
        if (empty(trim($_POST['nom'] ?? ''))) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }
        if (empty(trim($_POST['prenom'] ?? ''))) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }
        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        } elseif (Utilisateur::emailExists($_POST['email'], $this->db, $userId)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }

        if (empty($errors)) {
            $result = Utilisateur::adminUpdate($userId, $_POST, $this->db);

            if ($result['success']) {
                // Refresh session display name
                $_SESSION['user_nom']    = htmlspecialchars(trim($_POST['nom']),    ENT_QUOTES, 'UTF-8');
                $_SESSION['user_prenom'] = htmlspecialchars(trim($_POST['prenom']), ENT_QUOTES, 'UTF-8');
                $_SESSION['user_email']  = strtolower(trim($_POST['email']));

                redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'success' => 'updated']);
            }

            $errors['general'] = $result['message'];
        }

        $user = Utilisateur::findById($userId, $this->db);
        $pageTitle  = 'Mon Profil';
        $currentNav = '';
        require VIEWS_FRONT . '/profile/profile.php';
    }

    // ── Photo upload ──────────────────────────────────────────────

    public function photo(): void
    {
        $this->requireAuth();

        if (empty($_FILES['photo_profil']) || $_FILES['photo_profil']['error'] === UPLOAD_ERR_NO_FILE) {
            redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'error' => 'no_file']);
        }

        $file       = $_FILES['photo_profil'];
        $validation = $this->validateUploadedFile($file);

        if (!$validation['success']) {
            redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'error' => urlencode($validation['message'])]);
        }

        $userId = $this->currentUserId();
        $user   = Utilisateur::findById($userId, $this->db);

        if (!$user) {
            redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'error' => 'not_found']);
        }

        // Delete old photo
        if ($user->getPhotoProfil()) {
            $old = $this->uploadDir . $user->getPhotoProfil();
            if (file_exists($old)) @unlink($old);
        }

        $filename = sprintf('profile_%d_%d_%s.%s',
            $userId,
            time(),
            bin2hex(random_bytes(8)),
            strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'jpeg' ? 'jpg'
                : strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
        );

        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'error' => 'upload_failed']);
        }

        // Persist filename to DB
        $stmt = $this->db->prepare('UPDATE utilisateur SET photo_profil = :p WHERE id_utilisateur = :id');
        $stmt->execute([':p' => $filename, ':id' => $userId]);

        redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'success' => 'photo_updated']);
    }

    // ── Delete photo ──────────────────────────────────────────────

    public function deletePhoto(): void
    {
        $this->requireAuth();

        $userId = $this->currentUserId();
        $user   = Utilisateur::findById($userId, $this->db);

        if ($user && $user->getPhotoProfil()) {
            $path = $this->uploadDir . $user->getPhotoProfil();
            if (file_exists($path)) @unlink($path);

            $this->db->prepare('UPDATE utilisateur SET photo_profil = NULL WHERE id_utilisateur = :id')
                     ->execute([':id' => $userId]);
        }

        redirectToRoute('utilisateur', 'profile', ['office' => 'front', 'success' => 'photo_deleted']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function ensureUploadDir(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        // Prevent directory listing
        $idx = $this->uploadDir . 'index.html';
        if (!file_exists($idx)) {
            file_put_contents($idx, '<!DOCTYPE html><html><body>403 Forbidden</body></html>');
        }
        // Allow only images
        $ht = $this->uploadDir . '.htaccess';
        if (!file_exists($ht)) {
            file_put_contents($ht, "Order deny,allow\nDeny from all\n<Files ~ \"\\.(jpeg|jpg|png|gif|webp)$\">\nAllow from all\n</Files>");
        }
    }

    private function validateUploadedFile(array $file): array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erreur lors de l\'upload.'];
        }
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Fichier trop volumineux (max 2 MB).'];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed, true)) {
            return ['success' => false, 'message' => 'Format non supporté (JPEG, PNG, GIF, WebP uniquement).'];
        }
        if (!getimagesize($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Fichier image invalide.'];
        }

        return ['success' => true];
    }

    public function getPhotoUrl(?string $filename): string
    {
        if (empty($filename) || !file_exists($this->uploadDir . $filename)) {
            return '/midchaine/user/uploads/default-avatar.png';
        }
        return $this->webUploadPath . $filename . '?t=' . filemtime($this->uploadDir . $filename);
    }
}
