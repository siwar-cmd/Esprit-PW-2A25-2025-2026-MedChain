<?php
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../config.php';

class ProfileController {
    private $pdo;
    private $uploadDir;
    private $webUploadPath;

    public function __construct() {
        $this->pdo = config::getConnexion();
        $this->uploadDir = __DIR__ . '/../../uploads/profiles/';
        $this->webUploadPath = '/uploads/profiles/';
        $this->ensureUploadDirExists();
    }
    
    private function ensureUploadDirExists() {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                error_log("Erreur: Impossible de créer le dossier " . $this->uploadDir);
                throw new Exception("Impossible de créer le dossier de stockage des photos");
            }
        }
        
        $indexFile = $this->uploadDir . 'index.html';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access this resource.</p></body></html>');
        }
        
        $htaccessPath = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "Order deny,allow\nDeny from all\n<Files ~ \"\\.(jpeg|jpg|png|gif|webp)$\">\nAllow from all\n</Files>";
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }

    public function updateProfile($user_id, array $data): array {
        try {
            if (!$this->validateRequiredFields($data, ['nom', 'prenom', 'email'])) {
                return [
                    "success" => false, 
                    "message" => "Tous les champs obligatoires doivent être remplis"
                ];
            }

            if ($this->emailExists($data['email'], $user_id)) {
                return [
                    "success" => false, 
                    "message" => "Cet email est déjà utilisé par un autre utilisateur"
                ];
            }

            $utilisateur = $this->getUserById($user_id);
            if (!$utilisateur) {
                return [
                    "success" => false, 
                    "message" => "Utilisateur non trouvé"
                ];
            }

            $this->updateUserProperties($utilisateur, $data);

            if ($this->saveUser($utilisateur)) {
                return [
                    "success" => true, 
                    "message" => "Profil mis à jour avec succès"
                ];
            }

            return [
                "success" => false, 
                "message" => "Erreur lors de la mise à jour"
            ];

        } catch (Exception $e) {
            error_log("Erreur updateProfile: " . $e->getMessage());
            return [
                "success" => false, 
                "message" => "Une erreur est survenue lors de la mise à jour du profil"
            ];
        }
    }

    public function updateProfilePhoto($user_id, array $photo_file): array {
        try {
            $validation = $this->validateUploadedFile($photo_file);
            if (!$validation['success']) {
                return $validation;
            }
        
            $utilisateur = $this->getUserById($user_id);
            if (!$utilisateur) {
                return [
                    "success" => false,
                    "message" => "Utilisateur non trouvé"
                ];
            }

            $this->deleteOldProfilePhoto($utilisateur);

            $new_filename = $this->generateProfileFilename($user_id, $photo_file['name']);
            $file_path = $this->uploadDir . $new_filename;

            if (!move_uploaded_file($photo_file['tmp_name'], $file_path)) {
                return [
                    "success" => false,
                    "message" => "Erreur lors de l'enregistrement du fichier"
                ];
            }

            $utilisateur->setPhotoProfil($new_filename);

            if ($this->saveUserPhoto($utilisateur)) {
                return [
                    "success" => true,
                    "message" => "Photo de profil mise à jour avec succès",
                    "filename" => $new_filename,
                    "photo_url" => $this->getProfilePhotoUrl($new_filename)
                ];
            } else {
                $this->deletePhotoFile($new_filename);
                return [
                    "success" => false,
                    "message" => "Erreur lors de la mise à jour de la photo dans la base de données"
                ];
            }

        } catch (Exception $e) {
            error_log("Erreur updateProfilePhoto: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Une erreur est survenue lors de la mise à jour de la photo"
            ];
        }
    }

    public function deleteProfilePhoto($user_id): array {
        try {
            $utilisateur = $this->getUserById($user_id);
            if (!$utilisateur) {
                return [
                    "success" => false, 
                    "message" => "Utilisateur non trouvé"
                ];
            }

            $this->deleteOldProfilePhoto($utilisateur);
            $utilisateur->setPhotoProfil(null);
            
            if ($this->saveUserPhoto($utilisateur)) {
                return [
                    "success" => true, 
                    "message" => "Photo de profil supprimée avec succès"
                ];
            }

            return [
                "success" => false, 
                "message" => "Erreur lors de la suppression de la photo"
            ];

        } catch (Exception $e) {
            error_log("Erreur deleteProfilePhoto: " . $e->getMessage());
            return [
                "success" => false, 
                "message" => "Une erreur est survenue lors de la suppression de la photo"
            ];
        }
    }

    private function getUserById($user_id): ?Utilisateur {
        try {
            $query = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row ? $this->createUserFromRow($row) : null;
            
        } catch (Exception $e) {
            error_log("Erreur getUserById: " . $e->getMessage());
            return null;
        }
    }

    private function createUserFromRow(array $row): Utilisateur {
        $utilisateur = new Utilisateur(
            $row['nom'],
            $row['prenom'],
            $row['email'],
            '',
            $row['dateNaissance'] ?? null,
            $row['adresse'] ?? null,
            $row['role'] ?? 'patient',
            $row['statut'] ?? 'actif'
        );
        
        $utilisateur->setId($row['id_utilisateur'])
                   ->setDateInscription($row['date_inscription'] ?? date('Y-m-d H:i:s'))
                   ->setMotDePasse($row['mot_de_passe'], true)
                   ->setResetToken($row['reset_token'] ?? null)
                   ->setResetTokenExpires($row['reset_token_expires'] ?? null)
                   ->setHistoriqueConnexions($row['historique_connexions'] ?? null)
                   ->setDerniereConnexion($row['derniere_connexion'] ?? null)
                   ->setPhotoProfil($row['photo_profil'] ?? null)
                   ->setTelephone($row['telephone'] ?? null);
        
        return $utilisateur;
    }

    private function saveUser(Utilisateur $utilisateur): bool {
        try {
            $query = "UPDATE utilisateur SET 
                     nom = ?, prenom = ?, email = ?, dateNaissance = ?, 
                     adresse = ?, telephone = ?, role = ?, statut = ?
                     WHERE id_utilisateur = ?";
            
            $stmt = $this->pdo->prepare($query);
            
            return $stmt->execute([
                $utilisateur->getNom(),
                $utilisateur->getPrenom(),
                $utilisateur->getEmail(),
                $utilisateur->getDateNaissance(),
                $utilisateur->getAdresse(),
                $utilisateur->getTelephone(),
                $utilisateur->getRole(),
                $utilisateur->getStatut(),
                $utilisateur->getId()
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur saveUser: " . $e->getMessage());
            return false;
        }
    }

    private function saveUserPhoto(Utilisateur $utilisateur): bool {
        try {
            $query = "UPDATE utilisateur SET photo_profil = ? WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([
                $utilisateur->getPhotoProfil(),
                $utilisateur->getId()
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur saveUserPhoto: " . $e->getMessage());
            return false;
        }
    }

    private function emailExists($email, $exclude_user_id = null): bool {
        try {
            if ($exclude_user_id) {
                $query = "SELECT COUNT(*) FROM utilisateur WHERE email = ? AND id_utilisateur != ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$email, $exclude_user_id]);
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

    private function validateRequiredFields(array $data, array $required_fields): bool {
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    private function updateUserProperties(Utilisateur $utilisateur, array $data): void {
        $utilisateur->setNom($data['nom'])
                   ->setPrenom($data['prenom'])
                   ->setEmail($data['email'])
                   ->setDateNaissance($data['dateNaissance'] ?? null)
                   ->setAdresse($data['adresse'] ?? null);
        
        if (isset($data['telephone'])) {
            $utilisateur->setTelephone($data['telephone']);
        }
    }

    private function validateUploadedFile(array $file): array {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                "success" => false,
                "message" => $this->getUploadErrorMessage($file['error'])
            ];
        }

        if ($file['size'] > $max_file_size) {
            return [
                "success" => false,
                "message" => "Le fichier est trop volumineux (max 2MB)"
            ];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($file_type, $allowed_types)) {
            return [
                "success" => false,
                "message" => "Format de fichier non supporté"
            ];
        }

        if (!getimagesize($file['tmp_name'])) {
            return [
                "success" => false,
                "message" => "Le fichier n'est pas une image valide"
            ];
        }

        return ["success" => true];
    }

    private function generateProfileFilename($user_id, $original_filename): string {
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        
        return sprintf(
            'profile_%d_%s_%s.%s',
            $user_id,
            time(),
            bin2hex(random_bytes(8)),
            $extension
        );
    }

    private function deleteOldProfilePhoto(Utilisateur $utilisateur): void {
        $old_photo = $utilisateur->getPhotoProfil();
        if ($old_photo) {
            $this->deletePhotoFile($old_photo);
        }
    }

    private function deletePhotoFile($filename): bool {
        $file_path = $this->uploadDir . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }

    private function getUploadErrorMessage($error_code): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier est trop volumineux',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture du fichier',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
        ];
        
        return $errors[$error_code] ?? 'Erreur inconnue lors de l\'upload';
    }

    public function getProfilePhotoUrl($photo_filename): ?string {
        if (empty($photo_filename)) {
            return null;
        }

        $file_path = $this->uploadDir . $photo_filename;
        
        if (!file_exists($file_path)) {
            return null;
        }

        return $this->webUploadPath . $photo_filename . '?t=' . filemtime($file_path);
    }

    public function profilePhotoExists($photo_filename) {
        if (empty($photo_filename)) {
            return false;
        }

        $file_path = $this->uploadDir . $photo_filename;
        return file_exists($file_path) && is_file($file_path);
    }
    
    public function debugConfiguration() {
        return [
            'uploadDir' => $this->uploadDir,
            'webUploadPath' => $this->webUploadPath,
            'uploadDirExists' => is_dir($this->uploadDir),
            'uploadDirWritable' => is_writable($this->uploadDir),
            'serverDocumentRoot' => $_SERVER['DOCUMENT_ROOT'] ?? 'Non défini'
        ];
    }
}