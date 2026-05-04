<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * FicheRendezVous — Entity + Repository Model.
 *
 * All SQL lives here. Uses Database::getInstance().
 */
class FicheRendezVous
{
    private ?int    $idFiche                    = null;
    private ?int    $idRDV                      = null;
    private ?string $dateGeneration             = null;
    private ?string $piecesAApporter            = null;
    private ?string $consignesAvantConsultation = null;
    private ?float  $tarifConsultation          = null;
    private ?string $modeRemboursement          = null;
    private int     $emailEnvoye                = 0;
    private int     $calendrierAjoute           = 0;

    public function __construct(
        ?int    $idRDV                      = null,
        ?string $dateGeneration             = null,
        ?string $piecesAApporter            = null,
        ?string $consignesAvantConsultation = null,
        ?float  $tarifConsultation          = null,
        ?string $modeRemboursement          = null,
        int     $emailEnvoye                = 0,
        int     $calendrierAjoute           = 0,
        ?int    $idFiche                    = null
    ) {
        $this->idFiche                    = $idFiche;
        $this->idRDV                      = $idRDV;
        $this->dateGeneration             = $dateGeneration;
        $this->piecesAApporter            = $piecesAApporter;
        $this->consignesAvantConsultation = $consignesAvantConsultation;
        $this->tarifConsultation          = $tarifConsultation;
        $this->modeRemboursement          = $modeRemboursement;
        $this->emailEnvoye                = $emailEnvoye;
        $this->calendrierAjoute           = $calendrierAjoute;
    }

    // ── Getters ───────────────────────────────────────────────────
    public function getIdFiche(): ?int                    { return $this->idFiche; }
    public function getIdRDV(): ?int                      { return $this->idRDV; }
    public function getDateGeneration(): ?string          { return $this->dateGeneration; }
    public function getPiecesAApporter(): ?string         { return $this->piecesAApporter; }
    public function getConsignesAvantConsultation(): ?string { return $this->consignesAvantConsultation; }
    public function getTarifConsultation(): ?float        { return $this->tarifConsultation; }
    public function getModeRemboursement(): ?string       { return $this->modeRemboursement; }
    public function getEmailEnvoye(): int                 { return $this->emailEnvoye; }
    public function getCalendrierAjoute(): int            { return $this->calendrierAjoute; }

    // ── Setters ───────────────────────────────────────────────────
    public function setIdFiche(?int $v): static                    { $this->idFiche = $v; return $this; }
    public function setIdRDV(?int $v): static                      { $this->idRDV = $v; return $this; }
    public function setDateGeneration(?string $v): static          { $this->dateGeneration = $v; return $this; }
    public function setPiecesAApporter(?string $v): static         { $this->piecesAApporter = $v; return $this; }
    public function setConsignesAvantConsultation(?string $v): static { $this->consignesAvantConsultation = $v; return $this; }
    public function setTarifConsultation(?float $v): static        { $this->tarifConsultation = $v; return $this; }
    public function setModeRemboursement(?string $v): static       { $this->modeRemboursement = $v; return $this; }
    public function setEmailEnvoye(int $v): static                 { $this->emailEnvoye = $v; return $this; }
    public function setCalendrierAjoute(int $v): static            { $this->calendrierAjoute = $v; return $this; }

    public function toArray(): array {
        return [
            'idFiche'                    => $this->idFiche,
            'idRDV'                      => $this->idRDV,
            'dateGeneration'             => $this->dateGeneration,
            'piecesAApporter'            => $this->piecesAApporter,
            'consignesAvantConsultation' => $this->consignesAvantConsultation,
            'tarifConsultation'          => $this->tarifConsultation,
            'modeRemboursement'          => $this->modeRemboursement,
            'emailEnvoye'                => $this->emailEnvoye,
            'calendrierAjoute'           => $this->calendrierAjoute,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  REPOSITORY — Static methods
    // ══════════════════════════════════════════════════════════════

    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function findByRdvId(int $idRDV): ?array
    {
        try {
            $stmt = self::db()->prepare('SELECT * FROM ficherendezvous WHERE idRDV = :id LIMIT 1');
            $stmt->execute([':id' => $idRDV]);
            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            error_log('FicheRendezVous::findByRdvId — ' . $e->getMessage());
            return null;
        }
    }

    public static function findById(int $id): ?array
    {
        try {
            $stmt = self::db()->prepare('SELECT * FROM ficherendezvous WHERE idFiche = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            error_log('FicheRendezVous::findById — ' . $e->getMessage());
            return null;
        }
    }

    public static function create(array $data): array
    {
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO ficherendezvous
                   (idRDV, dateGeneration, piecesAApporter, consignesAvantConsultation,
                    tarifConsultation, modeRemboursement, emailEnvoye, calendrierAjoute)
                 VALUES (:rdv, :dgen, :pieces, :consignes, :tarif, :mode, :email, :cal)'
            );
            $stmt->execute([
                ':rdv'       => $data['idRDV'],
                ':dgen'      => $data['dateGeneration']             ?? date('Y-m-d'),
                ':pieces'    => $data['piecesAApporter']            ?? null,
                ':consignes' => $data['consignesAvantConsultation'] ?? null,
                ':tarif'     => isset($data['tarifConsultation'])   ? (float) $data['tarifConsultation'] : null,
                ':mode'      => $data['modeRemboursement']          ?? null,
                ':email'     => isset($data['emailEnvoye'])         ? 1 : 0,
                ':cal'       => isset($data['calendrierAjoute'])    ? 1 : 0,
            ]);

            return ['success' => true, 'message' => 'Fiche créée avec succès.', 'id' => (int) self::db()->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function update(int $id, array $data): array
    {
        try {
            $stmt = self::db()->prepare(
                'UPDATE ficherendezvous
                 SET piecesAApporter = :pieces,
                     consignesAvantConsultation = :consignes,
                     tarifConsultation = :tarif,
                     modeRemboursement = :mode,
                     emailEnvoye = :email,
                     calendrierAjoute = :cal
                 WHERE idFiche = :id'
            );
            $stmt->execute([
                ':pieces'    => $data['piecesAApporter']            ?? null,
                ':consignes' => $data['consignesAvantConsultation'] ?? null,
                ':tarif'     => isset($data['tarifConsultation'])   ? (float) $data['tarifConsultation'] : null,
                ':mode'      => $data['modeRemboursement']          ?? null,
                ':email'     => isset($data['emailEnvoye'])         ? 1 : 0,
                ':cal'       => isset($data['calendrierAjoute'])    ? 1 : 0,
                ':id'        => $id,
            ]);

            return ['success' => true, 'message' => 'Fiche mise à jour avec succès.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
