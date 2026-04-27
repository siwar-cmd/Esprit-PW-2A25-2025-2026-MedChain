<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';

class PasswordController {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    public function forgotPassword($email): array {
        try {
            // Vérifier si l'email existe
            $user = $this->findUserByEmail($email);
            if (!$user) {
                // Pour des raisons de sécurité, on ne révèle pas si l'email existe
                return [
                    "success" => true,
                    "message" => "Si cet email existe dans notre base de données, vous recevrez un lien de réinitialisation."
                ];
            }

            // Générer un token de réinitialisation
            $token = $user->generateResetToken();
            
            // Sauvegarder le token dans la base de données
            if (!$this->saveResetToken($user->getId(), $token, $user->getResetTokenExpires())) {
                return [
                    "success" => false,
                    "message" => "Erreur lors de la génération du lien de réinitialisation"
                ];
            }

            // Ici vous devriez envoyer un email avec le lien
            // Pour le moment, on retourne juste le lien (à remplacer par envoi d'email)
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/projet/views/frontoffice/auth/reset-password.php?token=" . urlencode($token);
            
            // TODO: Implémenter l'envoi d'email plus tard
            // Pour le développement, on retourne le lien directement
            return [
                "success" => true,
                "message" => "Un lien de réinitialisation a été généré. (Mode développement)",
                "reset_link" => $resetLink,
                "token" => $token // À retirer en production
            ];
            
        } catch (Exception $e) {
            error_log("Erreur forgotPassword: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Une erreur est survenue. Veuillez réessayer plus tard."
            ];
        }
    }

    public function resetPassword($token, $newPassword): array {
        try {
            // Vérifier la validité du token
            $userId = $this->validateTokenAndGetUserId($token);
            if (!$userId) {
                return [
                    "success" => false,
                    "message" => "Lien de réinitialisation invalide ou expiré."
                ];
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $query = "UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_token_expires = NULL WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([$hashedPassword, $userId]);
            
            if ($success) {
                return [
                    "success" => true,
                    "message" => "Votre mot de passe a été réinitialisé avec succès."
                ];
            }
            
            return [
                "success" => false,
                "message" => "Erreur lors de la réinitialisation du mot de passe."
            ];
            
        } catch (Exception $e) {
            error_log("Erreur resetPassword: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Une erreur est survenue. Veuillez réessayer plus tard."
            ];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword): array {
        try {
            // Vérifier l'utilisateur
            $user = $this->findUserById($userId);
            if (!$user) {
                return [
                    "success" => false,
                    "message" => "Utilisateur non trouvé"
                ];
            }

            // Vérifier l'ancien mot de passe
            if (!$user->validerMotDePasse($currentPassword)) {
                return [
                    "success" => false,
                    "message" => "Le mot de passe actuel est incorrect"
                ];
            }

            // Vérifier la longueur du nouveau mot de passe
            if (strlen($newPassword) < 6) {
                return [
                    "success" => false,
                    "message" => "Le nouveau mot de passe doit contenir au moins 6 caractères"
                ];
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([$hashedPassword, $userId]);
            
            if ($success) {
                return [
                    "success" => true,
                    "message" => "Votre mot de passe a été changé avec succès"
                ];
            }
            
            return [
                "success" => false,
                "message" => "Erreur lors du changement de mot de passe"
            ];
            
        } catch (Exception $e) {
            error_log("Erreur changePassword: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Une erreur est survenue. Veuillez réessayer plus tard."
            ];
        }
    }

    public function validateToken($token): array {
        try {
            $userId = $this->validateTokenAndGetUserId($token);
            if ($userId) {
                return [
                    "success" => true,
                    "message" => "Token valide"
                ];
            }
            
            return [
                "success" => false,
                "message" => "Lien de réinitialisation invalide ou expiré."
            ];
            
        } catch (Exception $e) {
            error_log("Erreur validateToken: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Erreur lors de la validation du token"
            ];
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
            error_log("Erreur findUserByEmail: " . $e->getMessage());
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
            '',
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

    private function saveResetToken($userId, $token, $expires): bool {
        try {
            $query = "UPDATE utilisateur SET reset_token = ?, reset_token_expires = ? WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$token, $expires, $userId]);
        } catch (Exception $e) {
            error_log("Erreur saveResetToken: " . $e->getMessage());
            return false;
        }
    }

    private function validateTokenAndGetUserId($token): ?int {
        try {
            $query = "SELECT id_utilisateur FROM utilisateur WHERE reset_token = ? AND reset_token_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row ? (int)$row['id_utilisateur'] : null;
        } catch (Exception $e) {
            error_log("Erreur validateTokenAndGetUserId: " . $e->getMessage());
            return null;
        }
    }
}