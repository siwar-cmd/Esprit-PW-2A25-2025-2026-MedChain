<?php
/**
 * PasswordController.php
 * Chemin : projet/controllers/PasswordController.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Mailer.php';

class PasswordController {
    private $pdo;
    private Mailer $mailer;

    private const BASE_URL = 'http://localhost/projet';

    public function __construct() {
        $this->pdo    = config::getConnexion();
        $this->mailer = new Mailer();
    }

    public function forgotPassword($email): array {
        try {
            $user = $this->findUserByEmail($email);
            if (!$user) {
                return ["success" => true, "message" => "Si cet email existe dans notre base de données, vous recevrez un lien de réinitialisation."];
            }

            $token = $user->generateResetToken();
            if (!$this->saveResetToken($user->getId(), $token, $user->getResetTokenExpires())) {
                return ["success" => false, "message" => "Erreur lors de la génération du lien."];
            }

            $resetLink = self::BASE_URL . '/views/frontoffice/auth/reset-password.php?token=' . urlencode($token);
            $fullName  = $user->getPrenom() . ' ' . $user->getNom();

            $sent = $this->mailer->sendPasswordReset($user->getEmail(), $fullName, $resetLink);

            if ($sent) {
                return ["success" => true, "message" => "Un email de réinitialisation a été envoyé à votre adresse."];
            }

            return ["success" => true, "message" => "Erreur d'envoi email. Lien de secours ci-dessous (dev uniquement).", "reset_link" => $resetLink];

        } catch (Exception $e) {
            error_log("Erreur forgotPassword: " . $e->getMessage());
            return ["success" => false, "message" => "Une erreur est survenue. Veuillez réessayer."];
        }
    }

    public function sendResetLink($email): array {
        return $this->forgotPassword($email);
    }

    public function resetPassword($token, $newPassword): array {
        try {
            $userId = $this->validateTokenAndGetUserId($token);
            if (!$userId) return ["success" => false, "message" => "Lien invalide ou expiré."];

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_token_expires = NULL WHERE id_utilisateur = ?");
            $ok = $stmt->execute([$hashed, $userId]);

            return $ok ? ["success" => true, "message" => "Mot de passe réinitialisé avec succès."]
                       : ["success" => false, "message" => "Erreur lors de la réinitialisation."];
        } catch (Exception $e) {
            error_log("Erreur resetPassword: " . $e->getMessage());
            return ["success" => false, "message" => "Une erreur est survenue."];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword): array {
        try {
            $user = $this->findUserById($userId);
            if (!$user) return ["success" => false, "message" => "Utilisateur non trouvé"];
            if (!$user->validerMotDePasse($currentPassword)) return ["success" => false, "message" => "Le mot de passe actuel est incorrect"];
            if (strlen($newPassword) < 6) return ["success" => false, "message" => "Le nouveau mot de passe doit contenir au moins 6 caractères"];

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?");
            $ok = $stmt->execute([$hashed, $userId]);

            return $ok ? ["success" => true, "message" => "Mot de passe changé avec succès"]
                       : ["success" => false, "message" => "Erreur lors du changement"];
        } catch (Exception $e) {
            error_log("Erreur changePassword: " . $e->getMessage());
            return ["success" => false, "message" => "Une erreur est survenue."];
        }
    }

    public function validateToken($token): array {
        $userId = $this->validateTokenAndGetUserId($token);
        return $userId ? ["success" => true, "message" => "Token valide"]
                       : ["success" => false, "message" => "Lien invalide ou expiré."];
    }

    private function findUserByEmail($email): ?Utilisateur {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->rowToUser($row) : null;
        } catch (Exception $e) { error_log("Erreur findUserByEmail: " . $e->getMessage()); return null; }
    }

    private function findUserById($id): ?Utilisateur {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->rowToUser($row) : null;
        } catch (Exception $e) { error_log("Erreur findUserById: " . $e->getMessage()); return null; }
    }

    private function saveResetToken($userId, $token, $expires): bool {
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET reset_token = ?, reset_token_expires = ? WHERE id_utilisateur = ?");
            return $stmt->execute([$token, $expires, $userId]);
        } catch (Exception $e) { error_log("Erreur saveResetToken: " . $e->getMessage()); return false; }
    }

    private function validateTokenAndGetUserId($token): ?int {
        try {
            $stmt = $this->pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE reset_token = ? AND reset_token_expires > NOW()");
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['id_utilisateur'] : null;
        } catch (Exception $e) { error_log("Erreur validateTokenAndGetUserId: " . $e->getMessage()); return null; }
    }

    private function rowToUser($row): Utilisateur {
        $user = new Utilisateur($row['nom'], $row['prenom'], $row['email'], '', $row['dateNaissance'] ?? null, $row['adresse'] ?? null, $row['role'] ?? 'user', $row['statut'] ?? 'actif');
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
}