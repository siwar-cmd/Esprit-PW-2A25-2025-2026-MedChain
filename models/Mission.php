<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Mission — Entity + Repository Model.
 *
 * All SQL lives here. Uses Database::getInstance() (medchain DB).
 */
class Mission
{
    private int    $id          = 0;
    private string $dateDebut   = '';
    private string $dateFin     = '';
    private string $typeMission = '';
    private string $lieuDepart  = '';
    private string $lieuArrivee = '';
    private string $equipe      = '';
    private bool   $estTerminee = false;
    private int    $idAmbulance = 0;

    public function __construct(
        int    $id          = 0,
        string $dateDebut   = '',
        string $dateFin     = '',
        string $typeMission = '',
        string $lieuDepart  = '',
        string $lieuArrivee = '',
        string $equipe      = '',
        bool   $estTerminee = false,
        int    $idAmbulance = 0
    ) {
        $this->id          = $id;
        $this->dateDebut   = $dateDebut;
        $this->dateFin     = $dateFin;
        $this->typeMission = $typeMission;
        $this->lieuDepart  = $lieuDepart;
        $this->lieuArrivee = $lieuArrivee;
        $this->equipe      = $equipe;
        $this->estTerminee = $estTerminee;
        $this->idAmbulance = $idAmbulance;
    }

    // ── Getters ───────────────────────────────────────────────────
    public function getId(): int          { return $this->id; }
    public function getDateDebut(): string { return $this->dateDebut; }
    public function getDateFin(): string  { return $this->dateFin; }
    public function getTypeMission(): string { return $this->typeMission; }
    public function getLieuDepart(): string { return $this->lieuDepart; }
    public function getLieuArrivee(): string { return $this->lieuArrivee; }
    public function getEquipe(): string   { return $this->equipe; }
    public function isEstTerminee(): bool { return $this->estTerminee; }
    public function getIdAmbulance(): int { return $this->idAmbulance; }

    // ── Setters ───────────────────────────────────────────────────
    public function setId(int $v): void           { $this->id = $v; }
    public function setDateDebut(string $v): void  { $this->dateDebut = $v; }
    public function setDateFin(string $v): void    { $this->dateFin = $v; }
    public function setTypeMission(string $v): void { $this->typeMission = $v; }
    public function setLieuDepart(string $v): void  { $this->lieuDepart = $v; }
    public function setLieuArrivee(string $v): void { $this->lieuArrivee = $v; }
    public function setEquipe(string $v): void    { $this->equipe = $v; }
    public function setEstTerminee(bool $v): void  { $this->estTerminee = $v; }
    public function setIdAmbulance(int $v): void   { $this->idAmbulance = $v; }

    public function calculerDuree(): ?string
    {
        if (empty($this->dateDebut) || empty($this->dateFin)) return null;
        try {
            $diff  = (new \DateTime($this->dateDebut))->diff(new \DateTime($this->dateFin));
            $parts = [];
            if ($diff->days > 0) $parts[] = $diff->days . 'j';
            if ($diff->h > 0)    $parts[] = $diff->h . 'h';
            if ($diff->i > 0)    $parts[] = $diff->i . 'min';
            return $parts ? implode(' ', $parts) : '< 1 min';
        } catch (\Exception) {
            return null;
        }
    }

    public function toArray(): array {
        return [
            'id'          => $this->id,
            'dateDebut'   => $this->dateDebut,
            'dateFin'     => $this->dateFin,
            'typeMission' => $this->typeMission,
            'lieuDepart'  => $this->lieuDepart,
            'lieuArrivee' => $this->lieuArrivee,
            'equipe'      => $this->equipe,
            'estTerminee' => $this->estTerminee,
            'idAmbulance' => $this->idAmbulance,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  REPOSITORY — Static methods
    // ══════════════════════════════════════════════════════════════

    private static function db(): PDO
    {
        return Database::getInstance();
    }

    public static function getAll(array $filters = []): array
    {
        try {
            $sql    = "SELECT m.idMission, m.dateDebut, m.dateFin, m.typeMission,
                              m.lieuDepart, m.lieuArrivee, m.equipe, m.estTerminee, m.idAmbulance,
                              a.immatriculation AS amb_immatriculation,
                              a.modele          AS amb_modele,
                              a.statut          AS amb_statut,
                              a.estDisponible   AS amb_estDisponible
                       FROM mission m
                       LEFT JOIN ambulance a ON a.idAmbulance = m.idAmbulance
                       WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (m.typeMission LIKE :s OR m.lieuDepart LIKE :s OR m.lieuArrivee LIKE :s OR m.equipe LIKE :s OR a.immatriculation LIKE :s)";
                $params[':s'] = '%' . $filters['search'] . '%';
            }
            if (isset($filters['estTerminee']) && $filters['estTerminee'] !== '') {
                $sql .= " AND m.estTerminee = :term";
                $params[':term'] = (int) $filters['estTerminee'];
            }
            if (!empty($filters['typeMission'])) {
                $sql .= " AND m.typeMission = :type";
                $params[':type'] = $filters['typeMission'];
            }
            if (!empty($filters['idAmbulance'])) {
                $sql .= " AND m.idAmbulance = :amb";
                $params[':amb'] = (int) $filters['idAmbulance'];
            }

            $sql .= " ORDER BY m.idMission DESC";
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
            $stmt = self::db()->prepare(
                "SELECT m.*, a.immatriculation, a.modele
                 FROM mission m
                 LEFT JOIN ambulance a ON m.idAmbulance = a.idAmbulance
                 WHERE m.idMission = :id"
            );
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
            $total     = (int) $db->query('SELECT COUNT(*) FROM mission')->fetchColumn();
            $ongoing   = (int) $db->query('SELECT COUNT(*) FROM mission WHERE estTerminee = 0')->fetchColumn();
            $completed = (int) $db->query('SELECT COUNT(*) FROM mission WHERE estTerminee = 1')->fetchColumn();
            return ['total' => $total, 'ongoing' => $ongoing, 'completed' => $completed];
        } catch (\Exception $e) {
            return ['total' => 0, 'ongoing' => 0, 'completed' => 0];
        }
    }

    public static function create(array $data): array
    {
        try {
            if (empty($data['dateDebut']) || empty($data['typeMission']) || empty($data['idAmbulance'])) {
                return ['success' => false, 'message' => "La date de début, le type de mission et l'ambulance sont obligatoires."];
            }
            self::db()->prepare(
                "INSERT INTO mission (dateDebut, dateFin, typeMission, lieuDepart, lieuArrivee, equipe, estTerminee, idAmbulance)
                 VALUES (:deb, :fin, :type, :dep, :arr, :eq, :term, :amb)"
            )->execute([
                ':deb'  => $data['dateDebut'],
                ':fin'  => !empty($data['dateFin']) ? $data['dateFin'] : null,
                ':type' => htmlspecialchars(trim($data['typeMission']), ENT_QUOTES, 'UTF-8'),
                ':dep'  => htmlspecialchars(trim($data['lieuDepart']  ?? ''), ENT_QUOTES, 'UTF-8'),
                ':arr'  => htmlspecialchars(trim($data['lieuArrivee'] ?? ''), ENT_QUOTES, 'UTF-8'),
                ':eq'   => htmlspecialchars(trim($data['equipe']      ?? ''), ENT_QUOTES, 'UTF-8'),
                ':term' => isset($data['estTerminee']) ? 1 : 0,
                ':amb'  => (int) $data['idAmbulance'],
            ]);
            return ['success' => true, 'message' => 'Mission créée avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function update(int $id, array $data): array
    {
        try {
            self::db()->prepare(
                "UPDATE mission
                 SET dateDebut = :deb, dateFin = :fin, typeMission = :type,
                     lieuDepart = :dep, lieuArrivee = :arr, equipe = :eq,
                     estTerminee = :term, idAmbulance = :amb
                 WHERE idMission = :id"
            )->execute([
                ':deb'  => $data['dateDebut'] ?? '',
                ':fin'  => !empty($data['dateFin']) ? $data['dateFin'] : null,
                ':type' => htmlspecialchars(trim($data['typeMission']  ?? ''), ENT_QUOTES, 'UTF-8'),
                ':dep'  => htmlspecialchars(trim($data['lieuDepart']   ?? ''), ENT_QUOTES, 'UTF-8'),
                ':arr'  => htmlspecialchars(trim($data['lieuArrivee']  ?? ''), ENT_QUOTES, 'UTF-8'),
                ':eq'   => htmlspecialchars(trim($data['equipe']       ?? ''), ENT_QUOTES, 'UTF-8'),
                ':term' => isset($data['estTerminee']) ? 1 : 0,
                ':amb'  => (int) ($data['idAmbulance'] ?? 0),
                ':id'   => $id,
            ]);
            return ['success' => true, 'message' => 'Mission mise à jour avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function delete(int $id): array
    {
        try {
            self::db()->prepare('DELETE FROM mission WHERE idMission = :id')->execute([':id' => $id]);
            return ['success' => true, 'message' => 'Mission supprimée avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
