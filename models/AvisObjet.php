<?php

class AvisObjet
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** All reviews for one object, joined with user name */
    public function getByObjet(int $idObjet): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_nom
             FROM avis_objet a
             INNER JOIN utilisateur u ON a.id_patient = u.id_utilisateur
             WHERE a.id_objet = :id
             ORDER BY a.date_avis DESC"
        );
        $stmt->execute([':id' => $idObjet]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Average rating for one object (null if no reviews) */
    public function getAverageNote(int $idObjet): ?float
    {
        $stmt = $this->db->prepare(
            'SELECT AVG(note) FROM avis_objet WHERE id_objet = :id'
        );
        $stmt->execute([':id' => $idObjet]);
        $avg = $stmt->fetchColumn();
        return $avg !== false && $avg !== null ? round((float) $avg, 1) : null;
    }

    /** Check if patient already reviewed this object */
    public function hasReviewed(int $idPatient, int $idObjet): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM avis_objet WHERE id_patient = :p AND id_objet = :o'
        );
        $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Check if patient has a terminated loan for this object */
    public function canReview(int $idPatient, int $idObjet): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pret
             WHERE id_patient = :p AND id_objet = :o AND statut = 'termine'"
        );
        $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(int $idPatient, int $idObjet, int $note, string $commentaire): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO avis_objet (id_patient, id_objet, note, commentaire, date_avis)
             VALUES (:p, :o, :n, :c, NOW())'
        );
        return $stmt->execute([
            ':p' => $idPatient,
            ':o' => $idObjet,
            ':n' => $note,
            ':c' => $commentaire,
        ]);
    }

    public function delete(int $idAvis): bool
    {
        $stmt = $this->db->prepare('DELETE FROM avis_objet WHERE id_avis = :id');
        $stmt->execute([':id' => $idAvis]);
        return $stmt->rowCount() === 1;
    }

    public function findById(int $idAvis): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM avis_objet WHERE id_avis = :id');
        $stmt->execute([':id' => $idAvis]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** All reviews for admin list */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, o.nom_objet, CONCAT(u.prenom, ' ', u.nom) AS auteur_nom
             FROM avis_objet a
             INNER JOIN objet_loisir o ON a.id_objet = o.id_objet
             INNER JOIN utilisateur u ON a.id_patient = u.id_utilisateur
             ORDER BY a.date_avis DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
