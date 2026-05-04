<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Utilisateur — Full entity + repository model.
 *
 * Properties match the unified `utilisateur` table in `medchain` DB.
 * Static methods act as the repository layer — all SQL lives here.
 *
 * Constructor hashes the password if it is not already a bcrypt hash.
 */
class Utilisateur
{
    // ── Entity Properties ─────────────────────────────────────────
    private ?int    $id_utilisateur;
    private string  $nom;
    private string  $prenom;
    private string  $email;
    private string  $mot_de_passe;
    private ?string $dateNaissance;
    private ?string $adresse;
    private ?string $telephone;
    private string  $date_inscription;
    private string  $role;    // admin | patient | medecin
    private string  $statut;  // actif | inactif | en_attente
    private ?string $reset_token;
    private ?string $reset_token_expires;
    private ?string $historique_connexions;
    private ?string $derniere_connexion;
    private ?string $photo_profil;

    // ── Constructor ───────────────────────────────────────────────
    public function __construct(
        string  $nom              = '',
        string  $prenom           = '',
        string  $email            = '',
        string  $mot_de_passe     = '',
        ?string $dateNaissance    = null,
        ?string $adresse          = null,
        string  $role             = 'patient',
        string  $statut           = 'actif'
    ) {
        $this->nom              = htmlspecialchars(trim($nom),    ENT_QUOTES, 'UTF-8');
        $this->prenom           = htmlspecialchars(trim($prenom), ENT_QUOTES, 'UTF-8');
        $this->email            = strtolower(trim($email));
        $this->dateNaissance    = $dateNaissance;
        $this->adresse          = $adresse;
        $this->role             = in_array($role, ['admin', 'patient', 'medecin']) ? $role : 'patient';
        $this->statut           = in_array($statut, ['actif', 'inactif', 'en_attente']) ? $statut : 'actif';
        $this->date_inscription = date('Y-m-d H:i:s');
        $this->reset_token      = null;
        $this->reset_token_expires = null;
        $this->photo_profil        = null;
        $this->historique_connexions = null;
        $this->derniere_connexion    = null;
        $this->telephone             = null;
        $this->id_utilisateur        = null;

        // Hash password only if not already a bcrypt hash
        $this->mot_de_passe = (!empty($mot_de_passe) && !preg_match('/^\$2[ayb]\$.{56}$/', $mot_de_passe))
            ? password_hash($mot_de_passe, PASSWORD_BCRYPT, ['cost' => 12])
            : $mot_de_passe;
    }

    // ── Getters ───────────────────────────────────────────────────
    public function getId(): ?int             { return $this->id_utilisateur; }
    public function getNom(): string          { return $this->nom; }
    public function getPrenom(): string       { return $this->prenom; }
    public function getEmail(): string        { return $this->email; }
    public function getMotDePasse(): string   { return $this->mot_de_passe; }
    public function getDateNaissance(): ?string { return $this->dateNaissance; }
    public function getAdresse(): ?string     { return $this->adresse; }
    public function getTelephone(): ?string   { return $this->telephone; }
    public function getDateInscription(): string { return $this->date_inscription; }
    public function getRole(): string         { return $this->role; }
    public function getStatut(): string       { return $this->statut; }
    public function getResetToken(): ?string  { return $this->reset_token; }
    public function getResetTokenExpires(): ?string { return $this->reset_token_expires; }
    public function getHistoriqueConnexions(): ?string { return $this->historique_connexions; }
    public function getDerniereConnexion(): ?string    { return $this->derniere_connexion; }
    public function getPhotoProfil(): ?string { return $this->photo_profil; }

    // ── Setters ───────────────────────────────────────────────────
    public function setId(int $id): static   { $this->id_utilisateur = $id; return $this; }
    public function setNom(string $v): static           { $this->nom = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setPrenom(string $v): static        { $this->prenom = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setEmail(string $v): static         { $this->email = strtolower(trim($v)); return $this; }
    public function setMotDePasse(string $v, bool $hashed = false): static {
        $this->mot_de_passe = (!$hashed && !empty($v))
            ? password_hash($v, PASSWORD_BCRYPT, ['cost' => 12])
            : $v;
        return $this;
    }
    public function setDateNaissance(?string $v): static  { $this->dateNaissance = $v; return $this; }
    public function setAdresse(?string $v): static        { $this->adresse = $v; return $this; }
    public function setTelephone(?string $v): static      { $this->telephone = $v; return $this; }
    public function setDateInscription(string $v): static { $this->date_inscription = $v; return $this; }
    public function setRole(string $v): static {
        if (in_array($v, ['admin', 'patient', 'medecin'])) $this->role = $v;
        return $this;
    }
    public function setStatut(string $v): static {
        if (in_array($v, ['actif', 'inactif', 'en_attente'])) $this->statut = $v;
        return $this;
    }
    public function setResetToken(?string $v): static        { $this->reset_token = $v; return $this; }
    public function setResetTokenExpires(?string $v): static { $this->reset_token_expires = $v; return $this; }
    public function setHistoriqueConnexions(?string $v): static { $this->historique_connexions = $v; return $this; }
    public function setDerniereConnexion(?string $v): static    { $this->derniere_connexion = $v; return $this; }
    public function setPhotoProfil(?string $v): static          { $this->photo_profil = $v; return $this; }

    // ── Utility Methods ────────────────────────────────────────────
    public function getNomComplet(): string     { return $this->prenom . ' ' . $this->nom; }
    public function estAdmin(): bool            { return $this->role === 'admin'; }
    public function estPatient(): bool          { return $this->role === 'patient'; }
    public function estMedecin(): bool          { return $this->role === 'medecin'; }
    public function estActif(): bool            { return $this->statut === 'actif'; }
    public function validerMotDePasse(string $p): bool { return password_verify($p, $this->mot_de_passe); }

    public function getRoleLabel(): string {
        return ['patient' => 'Patient', 'medecin' => 'Médecin', 'admin' => 'Administrateur'][$this->role] ?? $this->role;
    }

    public function getStatutLabel(): string {
        return ['actif' => 'Actif', 'inactif' => 'Inactif', 'en_attente' => 'En attente'][$this->statut] ?? $this->statut;
    }

    public function getPhotoProfilUrl(): string {
        if (!empty($this->photo_profil)) {
            return strpos($this->photo_profil, 'http') === 0
                ? $this->photo_profil
                : '/midchaine/user/uploads/' . $this->photo_profil;
        }
        return '/midchaine/user/uploads/default-avatar.png';
    }

    public function generateResetToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->reset_token         = $token;
        $this->reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        return $token;
    }

    public function isResetTokenValid(): bool {
        if (empty($this->reset_token) || empty($this->reset_token_expires)) return false;
        return new \DateTime() < new \DateTime($this->reset_token_expires);
    }

    public function toArray(): array {
        return [
            'id_utilisateur'        => $this->id_utilisateur,
            'nom'                   => $this->nom,
            'prenom'                => $this->prenom,
            'email'                 => $this->email,
            'dateNaissance'         => $this->dateNaissance,
            'adresse'               => $this->adresse,
            'telephone'             => $this->telephone,
            'date_inscription'      => $this->date_inscription,
            'role'                  => $this->role,
            'statut'                => $this->statut,
            'reset_token'           => $this->reset_token,
            'reset_token_expires'   => $this->reset_token_expires,
            'historique_connexions' => $this->historique_connexions,
            'derniere_connexion'    => $this->derniere_connexion,
            'photo_profil'          => $this->photo_profil,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  REPOSITORY — Static methods (all SQL lives here)
    // ══════════════════════════════════════════════════════════════

    private static function hydrate(array $row): self
    {
        $u = new self(
            $row['nom'],
            $row['prenom'],
            $row['email'],
            '',                          // password set separately (already hashed)
            $row['dateNaissance']  ?? null,
            $row['adresse']        ?? null,
            $row['role']           ?? 'patient',
            $row['statut']         ?? 'actif'
        );
        $u->setId((int) $row['id_utilisateur']);
        $u->setMotDePasse($row['mot_de_passe'], true);
        $u->setDateInscription($row['date_inscription']    ?? date('Y-m-d H:i:s'));
        $u->setTelephone($row['telephone']                 ?? null);
        $u->setResetToken($row['reset_token']              ?? null);
        $u->setResetTokenExpires($row['reset_token_expires'] ?? null);
        $u->setHistoriqueConnexions($row['historique_connexions'] ?? null);
        $u->setDerniereConnexion($row['derniere_connexion']       ?? null);
        $u->setPhotoProfil($row['photo_profil']            ?? null);
        return $u;
    }

    // ── Finders ───────────────────────────────────────────────────

    public static function findByEmail(string $email, PDO $db): ?self
    {
        $stmt = $db->prepare('SELECT * FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findById(int $id, PDO $db): ?self
    {
        $stmt = $db->prepare('SELECT * FROM utilisateur WHERE id_utilisateur = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByResetToken(string $token, PDO $db): ?self
    {
        $stmt = $db->prepare(
            'SELECT * FROM utilisateur
             WHERE reset_token = :token AND reset_token_expires > NOW() LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    // ── Checks ────────────────────────────────────────────────────

    public static function emailExists(string $email, PDO $db, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email AND id_utilisateur != :id');
            $stmt->execute([':email' => strtolower(trim($email)), ':id' => $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email');
            $stmt->execute([':email' => strtolower(trim($email))]);
        }
        return $stmt->fetchColumn() > 0;
    }

    // ── Write operations ──────────────────────────────────────────

    /**
     * Insert a new Utilisateur row; returns the new auto-increment ID or null.
     */
    public static function insert(self $u, PDO $db): ?int
    {
        $stmt = $db->prepare(
            'INSERT INTO utilisateur
               (nom, prenom, email, mot_de_passe, dateNaissance, adresse, telephone,
                role, statut, date_inscription,
                reset_token, reset_token_expires, historique_connexions, derniere_connexion, photo_profil)
             VALUES
               (:nom, :prenom, :email, :mdp, :dob, :adresse, :tel,
                :role, :statut, :inscrit,
                :rt, :rte, :hist, :dc, :photo)'
        );

        $ok = $stmt->execute([
            ':nom'     => $u->getNom(),
            ':prenom'  => $u->getPrenom(),
            ':email'   => $u->getEmail(),
            ':mdp'     => $u->getMotDePasse(),
            ':dob'     => $u->getDateNaissance(),
            ':adresse' => $u->getAdresse(),
            ':tel'     => $u->getTelephone(),
            ':role'    => $u->getRole(),
            ':statut'  => $u->getStatut(),
            ':inscrit' => $u->getDateInscription(),
            ':rt'      => $u->getResetToken(),
            ':rte'     => $u->getResetTokenExpires(),
            ':hist'    => $u->getHistoriqueConnexions(),
            ':dc'      => $u->getDerniereConnexion(),
            ':photo'   => $u->getPhotoProfil(),
        ]);

        return $ok ? (int) $db->lastInsertId() : null;
    }

    public static function updateLastLogin(int $id, PDO $db): void
    {
        $db->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = :id')
           ->execute([':id' => $id]);
    }

    public static function updateResetToken(int $id, ?string $token, ?string $expires, PDO $db): bool
    {
        $stmt = $db->prepare(
            'UPDATE utilisateur SET reset_token = :t, reset_token_expires = :e WHERE id_utilisateur = :id'
        );
        return $stmt->execute([':t' => $token, ':e' => $expires, ':id' => $id]);
    }

    public static function updatePassword(int $id, string $hashedPassword, PDO $db): bool
    {
        $stmt = $db->prepare(
            'UPDATE utilisateur SET mot_de_passe = :p, reset_token = NULL, reset_token_expires = NULL
             WHERE id_utilisateur = :id'
        );
        return $stmt->execute([':p' => $hashedPassword, ':id' => $id]);
    }

    public static function setStatut(int $id, string $statut, PDO $db): bool
    {
        $stmt = $db->prepare('UPDATE utilisateur SET statut = :s WHERE id_utilisateur = :id');
        return $stmt->execute([':s' => $statut, ':id' => $id]);
    }

    // ── Admin CRUD (returns result array for controller feedback) ─

    public static function adminCreate(array $data, PDO $db): array
    {
        if (self::emailExists($data['email'] ?? '', $db)) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }

        $u = new self(
            $data['nom']           ?? '',
            $data['prenom']        ?? '',
            $data['email']         ?? '',
            $data['mot_de_passe']  ?? '',
            $data['dateNaissance'] ?? null,
            $data['adresse']       ?? null,
            $data['role']          ?? 'patient',
            $data['statut']        ?? 'actif'
        );

        if (!empty($data['telephone'])) $u->setTelephone($data['telephone']);

        $id = self::insert($u, $db);
        return $id !== null
            ? ['success' => true, 'message' => 'Utilisateur créé avec succès.', 'id' => $id]
            : ['success' => false, 'message' => 'Erreur lors de la création.'];
    }

    public static function adminUpdate(int $id, array $data, PDO $db): array
    {
        $allowed = ['nom', 'prenom', 'email', 'adresse', 'telephone', 'dateNaissance', 'role', 'statut'];
        $sets    = [];
        $params  = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $val = match ($field) {
                    'nom', 'prenom', 'adresse', 'telephone' => htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8'),
                    'email'   => strtolower(trim($data[$field])),
                    default   => $data[$field],
                };
                $sets[]           = "$field = :$field";
                $params[":$field"] = $val;
            }
        }

        if (!empty($data['mot_de_passe'])) {
            $sets[]           = 'mot_de_passe = :mdp';
            $params[':mdp']   = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($sets)) {
            return ['success' => false, 'message' => 'Aucune donnée à mettre à jour.'];
        }

        $stmt = $db->prepare('UPDATE utilisateur SET ' . implode(', ', $sets) . ' WHERE id_utilisateur = :id');
        return $stmt->execute($params)
            ? ['success' => true, 'message' => 'Utilisateur mis à jour.']
            : ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
    }

    public static function adminDelete(int $id, PDO $db): array
    {
        $row = self::findById($id, $db);
        if ($row === null) return ['success' => false, 'message' => 'Utilisateur introuvable.'];

        // Prevent deleting last admin
        if ($row->getRole() === 'admin') {
            $count = (int) $db->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'admin'")->fetchColumn();
            if ($count <= 1) {
                return ['success' => false, 'message' => 'Impossible de supprimer le dernier administrateur.'];
            }
        }

        $stmt = $db->prepare('DELETE FROM utilisateur WHERE id_utilisateur = :id');
        return $stmt->execute([':id' => $id])
            ? ['success' => true, 'message' => 'Utilisateur supprimé.']
            : ['success' => false, 'message' => 'Erreur lors de la suppression.'];
    }

    // ── Listings (return plain arrays for views) ──────────────────

    public static function getAll(array $filters = [], PDO $db): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]             = '(nom LIKE :s OR prenom LIKE :s OR email LIKE :s)';
            $params[':s']        = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['role'])) {
            $where[]           = 'role = :role';
            $params[':role']   = $filters['role'];
        }
        if (!empty($filters['statut'])) {
            $where[]             = 'statut = :statut';
            $params[':statut']   = $filters['statut'];
        }

        $stmt = $db->prepare(
            'SELECT * FROM utilisateur WHERE ' . implode(' AND ', $where) . ' ORDER BY date_inscription DESC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getRecent(int $limit, PDO $db): array
    {
        $stmt = $db->prepare(
            'SELECT id_utilisateur, nom, prenom, email, role, statut, date_inscription
             FROM utilisateur ORDER BY date_inscription DESC LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getPending(PDO $db): array
    {
        $stmt = $db->prepare(
            "SELECT * FROM utilisateur WHERE statut = 'en_attente' ORDER BY date_inscription DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getMedecins(PDO $db): array
    {
        $stmt = $db->prepare(
            "SELECT id_utilisateur, nom, prenom, email FROM utilisateur
             WHERE role = 'medecin' AND statut = 'actif' ORDER BY nom, prenom"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getStats(PDO $db): array
    {
        $total         = (int) $db->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
        $newThisMonth  = (int) $db->query(
            "SELECT COUNT(*) FROM utilisateur
             WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())"
        )->fetchColumn();
        $byRole        = $db->query('SELECT role, COUNT(*) AS cnt FROM utilisateur GROUP BY role')->fetchAll();
        $byStatut      = $db->query('SELECT statut, COUNT(*) AS cnt FROM utilisateur GROUP BY statut')->fetchAll();

        return compact('total', 'newThisMonth', 'byRole', 'byStatut');
    }
}
