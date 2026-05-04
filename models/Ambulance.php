<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Ambulance — Entity + Repository Model.
 *
 * All SQL lives here. Uses Database::getInstance() (medchain DB).
 * The legacy USE sante; switch has been removed — tables now live in medchain.
 */
class Ambulance
{
    private int    $id             = 0;
    private string $immatriculation = '';
    private string $statut         = 'En service';
    private string $modele         = '';
    private int    $capacite       = 2;
    private bool   $estDisponible  = true;

    public function __construct(
        int    $id              = 0,
        string $immatriculation = '',
        string $statut          = 'En service',
        string $modele          = '',
        int    $capacite        = 2,
        bool   $estDisponible   = true
    ) {
        $this->id              = $id;
        $this->immatriculation = $immatriculation;
        $this->statut          = $statut;
        $this->modele          = $modele;
        $this->capacite        = $capacite;
        $this->estDisponible   = $estDisponible;
    }

    // ── Getters ───────────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getImmatriculation(): string { return $this->immatriculation; }
    public function getStatut(): string        { return $this->statut; }
    public function getModele(): string        { return $this->modele; }
    public function getCapacite(): int         { return $this->capacite; }
    public function isEstDisponible(): bool    { return $this->estDisponible; }

    // ── Setters ───────────────────────────────────────────────────
    public function setId(int $v): void               { $this->id = $v; }
    public function setImmatriculation(string $v): void { $this->immatriculation = $v; }
    public function setStatut(string $v): void        { $this->statut = $v; }
    public function setModele(string $v): void        { $this->modele = $v; }
    public function setCapacite(int $v): void         { $this->capacite = $v; }
    public function setEstDisponible(bool $v): void   { $this->estDisponible = $v; }

    public function changerStatut(string $nouveauStatut): void { $this->statut = $nouveauStatut; }

    public function toArray(): array {
        return [
            'id'              => $this->id,
            'immatriculation' => $this->immatriculation,
            'statut'          => $this->statut,
            'modele'          => $this->modele,
            'capacite'        => $this->capacite,
            'estDisponible'   => $this->estDisponible,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  REPOSITORY — Static methods (all SQL here)
    // ══════════════════════════════════════════════════════════════

    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function getAll(array $filters = []): array
    {
        try {
            $sql    = "SELECT a.*,
                              COUNT(m.idMission)     AS nb_missions,
                              SUM(m.estTerminee = 0) AS missions_en_cours,
                              SUM(m.estTerminee = 1) AS missions_terminees
                       FROM ambulance a
                       LEFT JOIN mission m ON m.idAmbulance = a.idAmbulance
                       WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (a.immatriculation LIKE :s OR a.modele LIKE :s OR a.statut LIKE :s)";
                $params[':s'] = '%' . $filters['search'] . '%';
            }
            if (isset($filters['disponible']) && $filters['disponible'] !== '') {
                $sql .= " AND a.estDisponible = :disp";
                $params[':disp'] = (int) $filters['disponible'];
            }
            if (!empty($filters['statut'])) {
                $sql .= " AND a.statut = :statut";
                $params[':statut'] = $filters['statut'];
            }

            $sql .= " GROUP BY a.idAmbulance ORDER BY a.idAmbulance DESC";
            $stmt = self::db()->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public static function findById(int $id): ?array
    {
        try {
            $stmt = self::db()->prepare('SELECT * FROM ambulance WHERE idAmbulance = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getStats(): array
    {
        try {
            $db        = self::db();
            $total     = (int) $db->query('SELECT COUNT(*) FROM ambulance')->fetchColumn();
            $available = (int) $db->query('SELECT COUNT(*) FROM ambulance WHERE estDisponible = 1')->fetchColumn();
            $enService = (int) $db->query("SELECT COUNT(*) FROM ambulance WHERE statut = 'En service'")->fetchColumn();
            return ['total' => $total, 'available' => $available, 'enService' => $enService];
        } catch (\Exception $e) {
            return ['total' => 0, 'available' => 0, 'enService' => 0];
        }
    }

    public static function create(array $data): array
    {
        try {
            if (empty($data['immatriculation']) || empty($data['modele'])) {
                return ['success' => false, 'message' => "L'immatriculation et le modèle sont obligatoires."];
            }
            self::db()->prepare(
                "INSERT INTO ambulance (immatriculation, statut, modele, capacite, estDisponible)
                 VALUES (:immat, :statut, :modele, :cap, :dispo)"
            )->execute([
                ':immat'  => htmlspecialchars(trim($data['immatriculation']), ENT_QUOTES, 'UTF-8'),
                ':statut' => $data['statut'] ?? 'En service',
                ':modele' => htmlspecialchars(trim($data['modele']), ENT_QUOTES, 'UTF-8'),
                ':cap'    => (int) ($data['capacite'] ?? 2),
                ':dispo'  => isset($data['estDisponible']) ? 1 : 0,
            ]);
            return ['success' => true, 'message' => 'Ambulance créée avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function update(int $id, array $data): array
    {
        try {
            self::db()->prepare(
                "UPDATE ambulance
                 SET immatriculation = :immat, statut = :statut, modele = :modele, capacite = :cap, estDisponible = :dispo
                 WHERE idAmbulance = :id"
            )->execute([
                ':immat'  => htmlspecialchars(trim($data['immatriculation'] ?? ''), ENT_QUOTES, 'UTF-8'),
                ':statut' => $data['statut'] ?? 'En service',
                ':modele' => htmlspecialchars(trim($data['modele'] ?? ''), ENT_QUOTES, 'UTF-8'),
                ':cap'    => (int) ($data['capacite'] ?? 2),
                ':dispo'  => isset($data['estDisponible']) ? 1 : 0,
                ':id'     => $id,
            ]);
            return ['success' => true, 'message' => 'Ambulance mise à jour avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function delete(int $id): array
    {
        try {
            $db = self::db();
            // missions are cascade-deleted by FK, but belt-and-suspenders:
            $db->prepare('DELETE FROM mission WHERE idAmbulance = :id')->execute([':id' => $id]);
            $db->prepare('DELETE FROM ambulance WHERE idAmbulance = :id')->execute([':id' => $id]);
            return ['success' => true, 'message' => 'Ambulance supprimée avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Returns simple array for <select> dropdowns. */
    public static function forSelect(): array
    {
        try {
            return self::db()
                ->query('SELECT idAmbulance, immatriculation, modele FROM ambulance ORDER BY immatriculation')
                ->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}
