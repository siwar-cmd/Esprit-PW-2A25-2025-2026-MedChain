<?php

class StatistiqueController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Top N most borrowed objects (all time) */
    public function getTopObjects(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.nom_objet, COUNT(p.id_pret) AS total
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             GROUP BY p.id_objet, o.nom_objet
             ORDER BY total DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Loans grouped by month for the last 12 months */
    public function getLoansByMonth(): array
    {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(date_pret, '%Y-%m') AS mois,
                    COUNT(*) AS total
             FROM pret
             WHERE date_pret >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY mois
             ORDER BY mois ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return rate: on-time vs late.
     */
    public function getReturnRate(): array
    {
        $stmt = $this->db->query(
            "SELECT
                SUM(CASE
                    WHEN statut = 'termine'
                     AND (date_retour_effective IS NULL
                          OR date_retour_effective <= date_retour_prevue)
                    THEN 1 ELSE 0
                END) AS on_time,
                SUM(CASE
                    WHEN statut = 'en_retard'
                     OR (statut = 'termine'
                         AND date_retour_effective IS NOT NULL
                         AND date_retour_effective > date_retour_prevue)
                    THEN 1 ELSE 0
                END) AS en_retard
             FROM pret
             WHERE statut IN ('termine', 'en_retard')"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['on_time' => 0, 'en_retard' => 0];
    }

    /** Global stats for PDF report */
    public function getGlobalStats(): array
    {
        $db = $this->db;
        return [
            'total_prets'    => (int) $db->query('SELECT COUNT(*) FROM pret')->fetchColumn(),
            'en_attente'     => (int) $db->query("SELECT COUNT(*) FROM pret WHERE statut='en_attente'")->fetchColumn(),
            'en_cours'       => (int) $db->query("SELECT COUNT(*) FROM pret WHERE statut='en_cours'")->fetchColumn(),
            'termine'        => (int) $db->query("SELECT COUNT(*) FROM pret WHERE statut='termine'")->fetchColumn(),
            'annule'         => (int) $db->query("SELECT COUNT(*) FROM pret WHERE statut='annule'")->fetchColumn(),
            'en_retard'      => (int) $db->query("SELECT COUNT(*) FROM pret WHERE statut='en_retard'")->fetchColumn(),
            'total_objets'   => (int) $db->query('SELECT COUNT(*) FROM objet_loisir')->fetchColumn(),
            'total_patients' => (int) $db->query("SELECT COUNT(*) FROM utilisateur WHERE role='patient'")->fetchColumn(),
        ];
    }

    /** Overdue loans with patient and object info */
    public function getOverdueLoans(): array
    {
        $stmt = $this->db->query(
            "SELECT p.*,
                    o.nom_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient,
                    u.email AS patient_email,
                    DATEDIFF(CURDATE(), p.date_retour_prevue) AS jours_retard
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE p.statut = 'en_retard'
                OR (p.statut = 'en_cours'
                    AND p.date_retour_prevue < CURDATE())
             ORDER BY jours_retard DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Active loans (en_cours + en_attente) with full patient and object details for PDF */
    public function getActiveLoans(): array
    {
        $stmt = $this->db->query(
            "SELECT
                p.id_pret,
                p.statut,
                p.date_pret,
                p.date_retour_prevue,
                p.motif_emprunt,
                o.nom_objet,
                o.type_objet,
                u.nom          AS patient_nom,
                u.prenom       AS patient_prenom,
                CONCAT(u.prenom, ' ', u.nom) AS nom_patient,
                u.email        AS patient_email
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet  = o.id_objet
             INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.statut IN ('en_cours', 'en_attente')
             ORDER BY
                FIELD(p.statut, 'en_cours', 'en_attente'),
                p.date_pret ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Calendar events: en_cours + en_attente loans */
    public function getCalendarEvents(): array
    {
        $stmt = $this->db->query(
            "SELECT p.id_pret,
                    p.statut,
                    p.date_pret,
                    p.date_retour_prevue,
                    o.nom_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE p.statut IN ('en_cours', 'en_attente')
             ORDER BY p.date_pret ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
