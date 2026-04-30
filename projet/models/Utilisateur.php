<?php

class Utilisateur
{
    private $id_utilisateur;
    private $nom;
    private $prenom;
    private $email;
    private $mot_de_passe;
    private $dateNaissance;
    private $adresse;
    private $telephone;
    private $date_inscription;
    private $role;  // 'admin', 'patient' ou 'medecin'
    private $statut; // 'actif', 'inactif', 'en_attente'
    private $reset_token;
    private $reset_token_expires;
    private $historique_connexions;
    private $derniere_connexion;
    private $photo_profil;

    public function __construct(
        $nom = "", 
        $prenom = "", 
        $email = "", 
        $mot_de_passe = "", 
        $dateNaissance = null, 
        $adresse = null, 
        $role = "patient", 
        $statut = "actif"
    ) {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->dateNaissance = $dateNaissance;
        $this->adresse = $adresse;
        $this->role = $role;
        $this->statut = $statut;
        $this->date_inscription = date('Y-m-d H:i:s');
        $this->reset_token = null;
        $this->reset_token_expires = null;
        $this->photo_profil = null;
        $this->historique_connexions = null;
        $this->derniere_connexion = null;
        $this->telephone = null;
        
        if (!empty($mot_de_passe)) {
            if (!preg_match('/^\$2[ayb]\$.{56}$/', $mot_de_passe)) {
                $this->mot_de_passe = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            } else {
                $this->mot_de_passe = $mot_de_passe;
            }
        } else {
            $this->mot_de_passe = $mot_de_passe;
        }
    }

    // ==================== GETTERS ====================
    public function getId() { return $this->id_utilisateur; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getEmail() { return $this->email; }
    public function getMotDePasse() { return $this->mot_de_passe; }
    public function getDateNaissance() { return $this->dateNaissance; }
    public function getAdresse() { return $this->adresse; }
    public function getTelephone() { return $this->telephone; }
    public function getDateInscription() { return $this->date_inscription; }
    public function getRole() { return $this->role; }
    public function getStatut() { return $this->statut; }
    public function getResetToken() { return $this->reset_token; }
    public function getResetTokenExpires() { return $this->reset_token_expires; }
    public function getHistoriqueConnexions() { return $this->historique_connexions; }
    public function getDerniereConnexion() { return $this->derniere_connexion; }
    public function getPhotoProfil() { return $this->photo_profil; }

    // ==================== SETTERS ====================
    public function setId($id) { $this->id_utilisateur = (int)$id; return $this; }
    public function setNom($nom) { $this->nom = htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setPrenom($prenom) { $this->prenom = htmlspecialchars(trim($prenom), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setEmail($email) { 
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) 
            $this->email = strtolower(trim($email)); 
        return $this; 
    }
    public function setMotDePasse($mot_de_passe, $hashed = false) { 
        $this->mot_de_passe = (!$hashed && !empty($mot_de_passe)) ? password_hash($mot_de_passe, PASSWORD_DEFAULT) : $mot_de_passe; 
        return $this; 
    }
    public function setDateNaissance($dateNaissance) { 
        if(empty($dateNaissance) || DateTime::createFromFormat('Y-m-d', $dateNaissance) !== false) 
            $this->dateNaissance = $dateNaissance; 
        return $this; 
    }
    public function setAdresse($adresse) { 
        $this->adresse = htmlspecialchars(trim($adresse), ENT_QUOTES, 'UTF-8'); 
        return $this; 
    }
    public function setTelephone($telephone) { 
        $this->telephone = htmlspecialchars(trim($telephone), ENT_QUOTES, 'UTF-8'); 
        return $this; 
    }
    public function setDateInscription($date) { $this->date_inscription = $date; return $this; }
    public function setRole($role) { 
        $allowed_roles = ['admin', 'patient', 'medecin']; 
        if(in_array($role, $allowed_roles)) 
            $this->role = $role; 
        return $this; 
    }
    public function setStatut($statut) { 
        $allowed_status = ['actif', 'inactif', 'en_attente']; 
        if(in_array($statut, $allowed_status)) 
            $this->statut = $statut; 
        return $this; 
    }
    public function setResetToken($token) { $this->reset_token = $token; return $this; }
    public function setResetTokenExpires($expires) { $this->reset_token_expires = $expires; return $this; }
    public function setHistoriqueConnexions($historique) { $this->historique_connexions = $historique; return $this; }
    public function setDerniereConnexion($derniere_connexion) { $this->derniere_connexion = $derniere_connexion; return $this; }
    public function setPhotoProfil($photo_profil) { $this->photo_profil = $photo_profil; return $this; }

    // ==================== MÉTHODES UTILITAIRES ====================
    
    public function getPhotoProfilUrl(): string {
        if (!empty($this->photo_profil)) {
            return strpos($this->photo_profil, 'http') === 0 ? $this->photo_profil : '/uploads/profiles/' . $this->photo_profil;
        }
        return '/assets/images/default-avatar.png';
    }

    public function hasPhotoProfil(): bool {
        return !empty($this->photo_profil);
    }

    public function getNomComplet(): string {
        return $this->prenom . ' ' . $this->nom;
    }

    public function estAdmin(): bool {
        return $this->role === 'admin';
    }
    
    public function estPatient(): bool {
        return $this->role === 'patient';
    }
    
    public function estMedecin(): bool {
        return $this->role === 'medecin';
    }
    
    public function estActif(): bool {
        return $this->statut === 'actif';
    }
    
    public function estEnAttente(): bool {
        return $this->statut === 'en_attente';
    }
    
    public function estInactif(): bool {
        return $this->statut === 'inactif';
    }
    
    public function getAge(): ?int {
        if (empty($this->dateNaissance)) {
            return null;
        }
        
        try {
            $birthDate = new DateTime($this->dateNaissance);
            $today = new DateTime();
            return $today->diff($birthDate)->y;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getDateNaissanceFormatee(): ?string {
        if (empty($this->dateNaissance)) {
            return null;
        }
        
        try {
            $date = new DateTime($this->dateNaissance);
            return $date->format('d/m/Y');
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getDateInscriptionFormatee(): string {
        try {
            $date = new DateTime($this->date_inscription);
            return $date->format('d/m/Y à H:i');
        } catch (Exception $e) {
            return $this->date_inscription;
        }
    }
    
    public function getDerniereConnexionFormatee(): string {
        if (empty($this->derniere_connexion)) {
            return "Jamais";
        }
        
        try {
            $date = new DateTime($this->derniere_connexion);
            return $date->format('d/m/Y à H:i');
        } catch (Exception $e) {
            return "Jamais";
        }
    }
    
    public function validerMotDePasse($password): bool {
        return password_verify($password, $this->mot_de_passe);
    }
    
    public function generateResetToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->reset_token = $token;
        $this->reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        return $token;
    }
    
    public function isResetTokenValid(): bool {
        if (empty($this->reset_token) || empty($this->reset_token_expires)) {
            return false;
        }
        
        $now = new DateTime();
        $expires = new DateTime($this->reset_token_expires);
        
        return $now < $expires;
    }
    
    public function clearResetToken(): void {
        $this->reset_token = null;
        $this->reset_token_expires = null;
    }
    
    public function addConnexionHistory($connexion_data) {
        $history = $this->getConnexionHistoryArray();
        $history[] = $connexion_data;
        $this->historique_connexions = json_encode($history, JSON_PRETTY_PRINT);
        return $this;
    }
    
    public function getConnexionHistoryArray(): array {
        if (empty($this->historique_connexions)) {
            return [];
        }
        
        $history = json_decode($this->historique_connexions, true);
        return is_array($history) ? $history : [];
    }
    
    public function getStatutLabel(): string {
        $labels = [
            'actif' => 'Actif',
            'inactif' => 'Inactif',
            'en_attente' => 'En attente'
        ];
        
        return $labels[$this->statut] ?? $this->statut;
    }
    
    public function getRoleLabel(): string {
        $labels = [
            'patient' => 'Patient',
            'medecin' => 'Médecin',
            'admin' => 'Administrateur'
        ];
        
        return $labels[$this->role] ?? $this->role;
    }
    
    public function toArray(): array {
        return [
            'id_utilisateur' => $this->id_utilisateur,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'dateNaissance' => $this->dateNaissance,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'date_inscription' => $this->date_inscription,
            'role' => $this->role,
            'statut' => $this->statut,
            'reset_token' => $this->reset_token,
            'reset_token_expires' => $this->reset_token_expires,
            'historique_connexions' => $this->historique_connexions,
            'derniere_connexion' => $this->derniere_connexion,
            'photo_profil' => $this->photo_profil
        ];
    }
} 