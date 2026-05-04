<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * RendezVous — Entity + Repository Model.
 *
 * All SQL lives here. Controllers never write SQL directly.
 * Uses Database::getInstance() (the canonical PDO singleton).
 */
class RendezVous
{
    // ── Entity Properties ─────────────────────────────────────────
    private ?int    $idRDV            = null;
    private ?string $dateHeureDebut   = null;
    private ?string $dateHeureFin     = null;
    private string  $statut           = 'planifie';
    private ?string $typeConsultation = null;
    private ?string $motif            = null;
    private ?int    $idClient         = null;
    private ?int    $idMedecin        = null;

    // ── Constructor ───────────────────────────────────────────────
    public function __construct(
        ?string $dateHeureDebut   = null,
        ?string $dateHeureFin     = null,
        string  $statut           = 'planifie',
        ?string $typeConsultation = null,
        ?string $motif            = null,
        ?int    $idClient         = null,
        ?int    $idMedecin        = null,
        ?int    $idRDV            = null
    ) {
        $this->idRDV            = $idRDV;
        $this->dateHeureDebut   = $dateHeureDebut;
        $this->dateHeureFin     = $dateHeureFin;
        $this->statut           = $statut;
        $this->typeConsultation = $typeConsultation;
        $this->motif            = $motif;
        $this->idClient         = $idClient;
        $this->idMedecin        = $idMedecin;
    }

    // ── Getters ───────────────────────────────────────────────────
    public function getIdRDV(): ?int            { return $this->idRDV; }
    public function getDateHeureDebut(): ?string { return $this->dateHeureDebut; }
    public function getDateHeureFin(): ?string   { return $this->dateHeureFin; }
    public function getStatut(): string          { return $this->statut; }
    public function getTypeConsultation(): ?string { return $this->typeConsultation; }
    public function getMotif(): ?string          { return $this->motif; }
    public function getIdClient(): ?int          { return $this->idClient; }
    public function getIdMedecin(): ?int         { return $this->idMedecin; }

    // ── Setters ───────────────────────────────────────────────────
    public function setIdRDV(?int $v): static            { $this->idRDV = $v; return $this; }
    public function setDateHeureDebut(?string $v): static { $this->dateHeureDebut = $v; return $this; }
    public function setDateHeureFin(?string $v): static   { $this->dateHeureFin = $v; return $this; }
    public function setStatut(string $v): static          { $this->statut = $v; return $this; }
    public function setTypeConsultation(?string $v): static { $this->typeConsultation = $v; return $this; }
    public function setMotif(?string $v): static          { $this->motif = $v; return $this; }
    public function setIdClient(?int $v): static          { $this->idClient = $v; return $this; }
    public function setIdMedecin(?int $v): static         { $this->idMedecin = $v; return $this; }

    public function toArray(): array {
        return [
            'idRDV'            => $this->idRDV,
            'dateHeureDebut'   => $this->dateHeureDebut,
            'dateHeureFin'     => $this->dateHeureFin,
            'statut'           => $this->statut,
            'typeConsultation' => $this->typeConsultation,
            'motif'            => $this->motif,
            'idClient'         => $this->idClient,
            'idMedecin'        => $this->idMedecin,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  REPOSITORY — Static methods (all SQL here)
    // ══════════════════════════════════════════════════════════════

    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    // ── SELECT helpers ────────────────────────────────────────────
    private static function baseSelect(): string
    {
        return "SELECT r.*,
                       u1.nom    AS client_nom,   u1.prenom AS client_prenom,
                       u2.nom    AS medecin_nom,  u2.prenom AS medecin_prenom
                FROM rendezvous r
                LEFT JOIN utilisateur u1 ON r.idClient  = u1.id_utilisateur
                LEFT JOIN utilisateur u2 ON r.idMedecin = u2.id_utilisateur";
    }

    /**
     * Find all RDVs with optional filters.
     * Pass $role + $userId to scope results to a patient or medecin.
     *
     * @return array{success:bool, rdvs:array, count:int}|array{success:false, message:string}
     */
    public static function getAll(array $filters = [], string $role = 'admin', ?int $userId = null): array
    {
        try {
            $where  = ['1=1'];
            $params = [];

            if ($role === 'patient' && $userId !== null) {
                $where[]  = 'r.idClient = :uid';
                $params[':uid'] = $userId;
            } elseif ($role === 'medecin' && $userId !== null) {
                $where[]  = 'r.idMedecin = :uid';
                $params[':uid'] = $userId;
            }

            if (!empty($filters['search'])) {
                $where[] = '(u1.nom LIKE :s OR u1.prenom LIKE :s OR u2.nom LIKE :s OR r.motif LIKE :s OR r.typeConsultation LIKE :s)';
                $params[':s'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['statut'])) {
                $where[]           = 'r.statut = :statut';
                $params[':statut'] = $filters['statut'];
            }

            $sql  = self::baseSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY r.dateHeureDebut DESC';
            $stmt = self::db()->prepare($sql);
            $stmt->execute($params);
            $rdvs = $stmt->fetchAll();

            return ['success' => true, 'rdvs' => $rdvs, 'count' => count($rdvs)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function findById(int $id): ?array
    {
        try {
            $stmt = self::db()->prepare(self::baseSelect() . ' WHERE r.idRDV = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            error_log('RendezVous::findById — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array{success:bool, message:string}
     */
    public static function create(array $data): array
    {
        try {
            $required = ['dateHeureDebut', 'dateHeureFin', 'typeConsultation', 'idClient', 'idMedecin'];
            foreach ($required as $f) {
                if (empty($data[$f])) {
                    return ['success' => false, 'message' => "Le champ $f est obligatoire."];
                }
            }

            $stmt = self::db()->prepare(
                'INSERT INTO rendezvous (dateHeureDebut, dateHeureFin, statut, typeConsultation, motif, idClient, idMedecin)
                 VALUES (:deb, :fin, :statut, :type, :motif, :client, :med)'
            );
            $stmt->execute([
                ':deb'    => $data['dateHeureDebut'],
                ':fin'    => $data['dateHeureFin'],
                ':statut' => $data['statut'] ?? 'planifie',
                ':type'   => htmlspecialchars($data['typeConsultation'], ENT_QUOTES, 'UTF-8'),
                ':motif'  => htmlspecialchars($data['motif'] ?? '', ENT_QUOTES, 'UTF-8'),
                ':client' => (int) $data['idClient'],
                ':med'    => (int) $data['idMedecin'],
            ]);

            return ['success' => true, 'message' => 'Rendez-vous créé avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success:bool, message:string}
     */
    public static function update(int $id, array $data): array
    {
        try {
            $allowed = ['dateHeureDebut', 'dateHeureFin', 'statut', 'typeConsultation', 'motif', 'idMedecin'];
            $sets    = [];
            $params  = [':id' => $id];

            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[]         = "$field = :$field";
                    $params[":$field"] = is_string($data[$field])
                        ? htmlspecialchars($data[$field], ENT_QUOTES, 'UTF-8')
                        : $data[$field];
                }
            }

            if (empty($sets)) {
                return ['success' => false, 'message' => 'Aucune donnée à mettre à jour.'];
            }

            $stmt = self::db()->prepare('UPDATE rendezvous SET ' . implode(', ', $sets) . ' WHERE idRDV = :id');
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Rendez-vous mis à jour avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success:bool, message:string}
     */
    public static function delete(int $id): array
    {
        try {
            // fiche is cascade-deleted by FK, but we do it explicitly for safety
            $db = self::db();
            $db->prepare('DELETE FROM ficherendezvous WHERE idRDV = :id')->execute([':id' => $id]);
            $db->prepare('DELETE FROM rendezvous WHERE idRDV = :id')->execute([':id' => $id]);

            return ['success' => true, 'message' => 'Rendez-vous supprimé avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Stats for the current user (scoped by role).
     */
    public static function getStats(string $role = 'admin', ?int $userId = null): array
    {
        try {
            $cond   = '1=1';
            $params = [];
            if ($role === 'patient' && $userId) {
                $cond         = 'idClient = :uid';
                $params[':uid'] = $userId;
            } elseif ($role === 'medecin' && $userId) {
                $cond         = 'idMedecin = :uid';
                $params[':uid'] = $userId;
            }

            $db     = self::db();
            $total  = (int) $db->prepare("SELECT COUNT(*) FROM rendezvous WHERE $cond")->execute($params) && true
                ? $db->prepare("SELECT COUNT(*) FROM rendezvous WHERE $cond")->execute($params) && $db->prepare("SELECT COUNT(*) FROM rendezvous WHERE $cond")
                : 0;

            // Simplified stats using one query per metric
            $s  = $db->prepare("SELECT COUNT(*) FROM rendezvous WHERE $cond");
            $s->execute($params);
            $total = (int) $s->fetchColumn();

            $s2 = $db->prepare("SELECT statut, COUNT(*) AS cnt FROM rendezvous WHERE $cond GROUP BY statut");
            $s2->execute($params);
            $byStatus = $s2->fetchAll();

            $s3 = $db->prepare("SELECT COUNT(*) FROM rendezvous WHERE $cond AND MONTH(dateHeureDebut) = MONTH(CURDATE()) AND YEAR(dateHeureDebut) = YEAR(CURDATE())");
            $s3->execute($params);
            $ceMois = (int) $s3->fetchColumn();

            return ['total' => $total, 'ce_mois' => $ceMois, 'by_status' => $byStatus];
        } catch (\Exception $e) {
            return ['total' => 0, 'ce_mois' => 0, 'by_status' => []];
        }
    }
}
