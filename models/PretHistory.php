<?php

class PretHistory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Insert a history entry after a status change.
     */
    public function log(int $idPret, string $ancienStatut, string $nouveauStatut, ?int $changedBy): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pret_history (id_pret, ancien_statut, nouveau_statut, changed_by, date_change)
             VALUES (:id_pret, :ancien, :nouveau, :by, NOW())'
        );
        return $stmt->execute([
            ':id_pret' => $idPret,
            ':ancien'  => $ancienStatut,
            ':nouveau' => $nouveauStatut,
            ':by'      => $changedBy,
        ]);
    }

    /**
     * Fetch the full timeline for one loan, newest first.
     */
    public function getTimeline(int $idPret): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*,
                    CONCAT(u.prenom, ' ', u.nom) AS changed_by_nom
             FROM pret_history h
             LEFT JOIN utilisateur u ON h.changed_by = u.id_utilisateur
             WHERE h.id_pret = :id
             ORDER BY h.date_change ASC, h.id_history ASC"
        );
        $stmt->execute([':id' => $idPret]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
