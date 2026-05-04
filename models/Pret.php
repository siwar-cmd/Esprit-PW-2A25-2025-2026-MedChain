<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/EmailService.php';

/**
 * Pret — Entity model for leisure-object loans.
 *
 * Uses Project B's `config::getConnexion()` for all database access.
 * All SQL queries are encapsulated here (strict MVC).
 *
 * Key schema changes vs. original:
 *  - `nom_patient`  → REMOVED
 *  - `id_patient`   → FK to utilisateur(id_utilisateur) ON DELETE CASCADE
 *  - `id_medecin`   → FK to utilisateur(id_utilisateur) ON DELETE SET NULL
 *  - `motif_emprunt` → TEXT, optional
 */
class Pret
{
    // ─── Properties ────────────────────────────────────────────
    private ?int    $id_pret               = null;
    private ?int    $id_objet              = null;
    private ?int    $id_patient            = null;
    private ?int    $id_medecin            = null;
    private ?string $motif_emprunt         = null;
    private ?string $date_pret             = null;
    private ?string $date_retour_prevue    = null;
    private ?string $date_retour_effective = null;
    private string  $statut                = 'en_attente';

    // ─── Constructor ───────────────────────────────────────────
    public function __construct(
        ?int    $id_pret               = null,
        ?int    $id_objet              = null,
        ?int    $id_patient            = null,
        ?int    $id_medecin            = null,
        ?string $motif_emprunt         = null,
        ?string $date_pret             = null,
        ?string $date_retour_prevue    = null,
        ?string $date_retour_effective = null,
        string  $statut                = 'en_attente'
    ) {
        $this->id_pret               = $id_pret;
        $this->id_objet              = $id_objet;
        $this->id_patient            = $id_patient;
        $this->id_medecin            = $id_medecin;
        $this->motif_emprunt         = $motif_emprunt;
        $this->date_pret             = $date_pret;
        $this->date_retour_prevue    = $date_retour_prevue;
        $this->date_retour_effective = $date_retour_effective;
        $this->statut                = $statut;
    }

    // ─── Getters ───────────────────────────────────────────────
    public function getId(): ?int                   { return $this->id_pret; }
    public function getIdPret(): ?int               { return $this->id_pret; }
    public function getIdObjet(): ?int              { return $this->id_objet; }
    public function getIdPatient(): ?int            { return $this->id_patient; }
    public function getIdMedecin(): ?int            { return $this->id_medecin; }
    public function getMotifEmprunt(): ?string      { return $this->motif_emprunt; }
    public function getDatePret(): ?string          { return $this->date_pret; }
    public function getDateRetourPrevue(): ?string  { return $this->date_retour_prevue; }
    public function getDateRetourEffective(): ?string { return $this->date_retour_effective; }
    public function getStatut(): string             { return $this->statut; }

    // ─── Setters ───────────────────────────────────────────────
    public function setId(?int $id): void                    { $this->id_pret = $id; }
    public function setIdPret(?int $v): void                 { $this->id_pret = $v; }
    public function setIdObjet(?int $v): void                { $this->id_objet = $v; }
    public function setIdPatient(?int $v): void              { $this->id_patient = $v; }
    public function setIdMedecin(?int $v): void              { $this->id_medecin = $v; }
    public function setMotifEmprunt(?string $v): void        { $this->motif_emprunt = $v; }
    public function setDatePret(?string $v): void            { $this->date_pret = $v; }
    public function setDateRetourPrevue(?string $v): void    { $this->date_retour_prevue = $v; }
    public function setDateRetourEffective(?string $v): void { $this->date_retour_effective = $v; }
    public function setStatut(string $v): void               { $this->statut = $v; }

    // ═══════════════════════════════════════════════════════════
    //  REPOSITORY METHODS (static) — all DB access goes here
    // ═══════════════════════════════════════════════════════════

    /**
     * Obtain the shared PDO instance via Project B's config class.
     */
    private static function db(): PDO
    {
        return Database::getInstance();
    }

    // ─── Read ──────────────────────────────────────────────────

    /**
     * Fetch loans matching a raw SQL condition.
     * Joins objet_loisir for object details and utilisateur for patient name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findByCondition(string $condition): array
    {
        $sql = "SELECT p.*,
                       o.nom_objet   AS objet_nom,
                       o.type_objet  AS objet_type,
                       CONCAT(u.prenom, ' ', u.nom) AS patient_nom,
                       u.email       AS patient_email
                FROM pret p
                INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
                INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
                WHERE {$condition}
                ORDER BY p.date_pret DESC, p.id_pret DESC";

        $stmt = self::db()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Paginated listing with status filter.
     * @return array{rows: array, total: int, pages: int, page: int}
     */
    public static function findPaginated(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $db = self::db();
        $where = '1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            $where .= ' AND p.statut = :statut';
            $params[':statut'] = $status;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM pret p WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*,
                       o.nom_objet AS objet_nom, o.type_objet AS objet_type,
                       CONCAT(u.prenom, ' ', u.nom) AS patient_nom,
                       u.email AS patient_email
                FROM pret p
                INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
                INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
                WHERE {$where}
                ORDER BY p.date_pret DESC, p.id_pret DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /**
     * Find overdue loans (en_cours past return date).
     * @return array<int, array<string, mixed>>
     */
    public static function findOverdue(): array
    {
        return self::db()->query(
            "SELECT p.*,
                    o.nom_objet AS objet_nom,
                    CONCAT(u.prenom, ' ', u.nom) AS patient_nom,
                    u.email AS patient_email
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.statut = 'en_cours'
               AND p.date_retour_prevue < CURDATE()
             ORDER BY p.date_retour_prevue"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark a loan as overdue.
     */
    public static function markOverdue(int $id): bool
    {
        $stmt = self::db()->prepare("UPDATE pret SET statut = 'en_retard' WHERE id_pret = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Reject a loan with a cancellation reason and send email.
     * @return array{success: bool, error?: string}
     */
    public static function rejectById(int $id, string $motif = ''): array
    {
        $db = self::db();
        try {
            $db->beginTransaction();
            $pret = self::findForUpdate($id);
            if ($pret === null) { $db->rollBack(); return ['success' => false, 'error' => 'loan_not_found']; }
            if ($pret['statut'] !== 'en_attente') { $db->rollBack(); return ['success' => false, 'error' => 'already_processed']; }

            $stmt = $db->prepare("UPDATE pret SET statut = 'annule', motif_annulation = :motif WHERE id_pret = :id");
            $stmt->execute([':motif' => $motif, ':id' => $id]);
            $db->commit();

            // Send rejection email (non-blocking)
            try {
                $full = self::findByCondition('p.id_pret = ' . $id);
                if (!empty($full)) {
                    $row = $full[0];
                    EmailService::notifyLoanRejected(
                        $row['patient_email'], $row['patient_nom'], $row['objet_nom'], $motif
                    );
                }
            } catch (\Throwable $e) { /* email failure must not crash */ }

            return ['success' => true];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    /**
     * Fetch all loans belonging to a given patient (by user ID).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findByPatientId(int $patientId): array
    {
        $stmt = self::db()->prepare(
            "SELECT p.*,
                    o.nom_objet   AS objet_nom,
                    o.type_objet  AS objet_type,
                    CONCAT(u.prenom, ' ', u.nom) AS patient_nom
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.id_patient = :id_patient
             ORDER BY p.date_pret DESC, p.id_pret DESC"
        );
        $stmt->execute([':id_patient' => $patientId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single loan for a pessimistic-lock update.
     *
     * @return array<string, mixed>|null
     */
    public static function findForUpdate(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM pret WHERE id_pret = :id_pret FOR UPDATE');
        $stmt->execute([':id_pret' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Count loans by status.
     */
    public static function countByStatus(string $status): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM pret WHERE statut = :statut');
        $stmt->execute([':statut' => $status]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch the N most recent pending loans (for the admin dashboard).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentPending(int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            "SELECT p.*,
                    o.nom_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS patient_nom
             FROM pret p
             LEFT JOIN objet_loisir o ON p.id_objet = o.id_objet
             LEFT JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.statut = :statut
             ORDER BY p.date_pret DESC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([':statut' => 'en_attente']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── Write ─────────────────────────────────────────────────

    /**
     * Create a new loan request.
     *
     * @return array{success: bool, message: string}
     */
    public static function createLoan(
        int     $objectId,
        int     $patientId,
        string  $datePret,
        ?string $dateRetourPrevue = null,
        ?string $motifEmprunt     = null,
        ?int    $medecinId        = null
    ): array {
        if ($objectId <= 0) {
            return ['success' => false, 'message' => 'Sélection d\'objet invalide.'];
        }

        $objet = ObjetLoisir::findById($objectId);
        if ($objet === null) {
            return ['success' => false, 'message' => 'Objet introuvable.'];
        }

        if ((int) $objet['quantite'] <= 0 || $objet['disponibilite'] !== 'disponible') {
            return ['success' => false, 'message' => 'Cet objet est actuellement indisponible.'];
        }

        $loanDate   = $datePret !== '' ? $datePret : date('Y-m-d');
        $returnDate = ($dateRetourPrevue && $dateRetourPrevue !== '')
            ? $dateRetourPrevue
            : (new \DateTimeImmutable($loanDate))->modify('+7 days')->format('Y-m-d');

        $stmt = self::db()->prepare(
            'INSERT INTO pret (id_objet, id_patient, id_medecin, motif_emprunt, date_pret, date_retour_prevue, statut)
             VALUES (:id_objet, :id_patient, :id_medecin, :motif_emprunt, :date_pret, :date_retour_prevue, :statut)'
        );

        $success = $stmt->execute([
            ':id_objet'          => $objectId,
            ':id_patient'        => $patientId,
            ':id_medecin'        => $medecinId,
            ':motif_emprunt'     => $motifEmprunt,
            ':date_pret'         => $loanDate,
            ':date_retour_prevue'=> $returnDate,
            ':statut'            => 'en_attente',
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'Loan request created.' : 'Unable to create the loan request.',
        ];
    }

    /**
     * Confirm a pending loan (admin action).
     * Decrements stock inside a transaction.
     *
     * @return array{success: bool, error?: string}
     */
    public static function confirmById(int $id): array
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            $pret = self::findForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if ($pret['statut'] !== 'en_attente') {
                $db->rollBack();
                return ['success' => false, 'error' => 'already_processed'];
            }

            $stockUpdate = $db->prepare(
                "UPDATE objet_loisir
                 SET quantite = quantite - 1,
                     disponibilite = CASE WHEN quantite - 1 > 0 THEN 'disponible' ELSE 'indisponible' END
                 WHERE id_objet = :id_objet AND quantite > 0"
            );
            $stockUpdate->execute([':id_objet' => $pret['id_objet']]);

            if ($stockUpdate->rowCount() !== 1) {
                $db->rollBack();
                return ['success' => false, 'error' => 'stock_unavailable'];
            }

            $pretUpdate = $db->prepare("UPDATE pret SET statut = 'en_cours' WHERE id_pret = :id_pret");
            $pretUpdate->execute([':id_pret' => $id]);

            $db->commit();

            // Send approval email (non-blocking)
            try {
                $full = self::findByCondition('p.id_pret = ' . $id);
                if (!empty($full)) {
                    $row = $full[0];
                    EmailService::notifyLoanApproved(
                        $row['patient_email'], $row['patient_nom'],
                        $row['objet_nom'], $row['date_pret']
                    );
                }
            } catch (\Throwable $e) { /* email failure must not crash */ }

            return ['success' => true];
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    /**
     * Cancel a loan (admin or patient action).
     * Restores stock if the loan was already active.
     *
     * @return array{success: bool, error?: string}
     */
    public static function cancelById(int $id): array
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            $pret = self::findForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if (!in_array($pret['statut'], ['en_attente', 'en_cours'], true)) {
                $db->rollBack();
                return ['success' => false, 'error' => 'invalid_status'];
            }

            $stmt = $db->prepare("UPDATE pret SET statut = 'annule' WHERE id_pret = :id_pret");
            $stmt->execute([':id_pret' => $id]);

            if ($pret['statut'] === 'en_cours') {
                $restore = $db->prepare(
                    "UPDATE objet_loisir
                     SET quantite = quantite + 1,
                         disponibilite = 'disponible'
                     WHERE id_objet = :id_objet"
                );
                $restore->execute([':id_objet' => $pret['id_objet']]);
            }

            $db->commit();
            return ['success' => true];
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    /**
     * Return a loaned object.
     * Restores stock, records effective return date.
     *
     * @return array{success: bool, error?: string}
     */
    public static function returnById(int $id): array
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            $pret = self::findForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if ($pret['statut'] !== 'en_cours') {
                $db->rollBack();
                return ['success' => false, 'error' => 'invalid_status'];
            }

            $pretUpdate = $db->prepare(
                "UPDATE pret
                 SET statut = 'termine',
                     date_retour_effective = NOW()
                 WHERE id_pret = :id_pret"
            );
            $pretUpdate->execute([':id_pret' => $id]);

            $stockUpdate = $db->prepare(
                "UPDATE objet_loisir
                 SET quantite = quantite + 1,
                     disponibilite = 'disponible'
                 WHERE id_objet = :id_objet"
            );
            $stockUpdate->execute([':id_objet' => $pret['id_objet']]);

            $db->commit();
            return ['success' => true];
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    // ─── Status helpers ────────────────────────────────────────

    /**
     * Map a raw status string to a human-readable label.
     */
    public static function statusLabel(string $status): string
    {
        $labels = [
            'en_attente' => 'En attente',
            'en_cours'   => 'En cours',
            'termine'    => 'Terminé',
            'annule'     => 'Annulé',
            'en_retard'  => 'En retard',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Enrich each loan row with a `status_label` field.
     *
     * @param  array<int, array<string, mixed>> $prets
     * @return array<int, array<string, mixed>>
     */
    public static function addStatusLabels(array $prets): array
    {
        foreach ($prets as &$pret) {
            $pret['status_label'] = self::statusLabel($pret['statut'] ?? '');
        }
        unset($pret);

        return $prets;
    }
}
