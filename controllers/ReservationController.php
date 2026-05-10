<?php

class ReservationController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Public action methods (called by router) ──────────────────────────────

    /** Front: patient reserves an unavailable object */
    public function createFront(): void
    {
        $this->requireLogin();

        $idObjet   = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;
        $idPatient = (int) $_SESSION['user_id'];

        if ($idObjet <= 0) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $objet = (new ObjetController())->findObjectById($idObjet);
        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        if ((int) $objet['quantite'] > 0) {
            redirectToRoute('pret', 'create', ['office' => 'front', 'objet_id' => $idObjet]);
        }

        $result = $this->create($idPatient, $idObjet);

        if ($result['success']) {
            redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'success' => 'reserved']);
        } else {
            redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'error' => 'already_reserved']);
        }
    }

    /** Front: patient cancels their own reservation */
    public function cancelFront(int $id): void
    {
        $this->requireLogin();
        $this->cancel($id, (int) $_SESSION['user_id']);
        redirectToRoute('reservation', 'myList', ['office' => 'front', 'success' => 'cancelled']);
    }

    /** Front: show patient's own reservations */
    public function myListFront(): void
    {
        $this->requireLogin();
        $reservations = $this->getByPatient((int) $_SESSION['user_id']);
        $errors       = $this->errorsFromQuery();
        require BASE_PATH . '/views/front/reservation_list.php';
    }

    /** Back: admin sees all waiting lists */
    public function listBack(): void
    {
        $reservations = $this->getAll();
        $errors       = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/reservation_list.php';
    }

    /** Back: admin deletes a reservation entry */
    public function deleteBack(int $id): void
    {
        $this->deleteById($id);
        redirectToRoute('reservation', 'list', ['office' => 'back', 'success' => 'deleted']);
    }

    // ── Business logic & SQL (moved from Reservation model) ──────────────────

    /** Add patient to waiting list (one reservation per patient per object) */
    public function create(int $idPatient, int $idObjet): array
    {
        if ($this->hasReservation($idPatient, $idObjet)) {
            return ['success' => false, 'message' => "Vous êtes déjà sur la liste d'attente pour cet objet."];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reservation (id_patient, id_objet, date_reservation, statut)
             VALUES (:p, :o, NOW(), 'en_attente')"
        );
        $ok = $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return ['success' => $ok, 'message' => $ok ? 'Réservation ajoutée.' : 'Erreur lors de la réservation.'];
    }

    public function hasReservation(int $idPatient, int $idObjet): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM reservation
             WHERE id_patient = :p AND id_objet = :o AND statut = 'en_attente'"
        );
        $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Get the first person in the queue for an object */
    public function getNextInQueue(int $idObjet): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS patient_nom
             FROM reservation r
             INNER JOIN utilisateur u ON r.id_patient = u.id_utilisateur
             WHERE r.id_objet = :o AND r.statut = 'en_attente'
             ORDER BY r.date_reservation ASC
             LIMIT 1"
        );
        $stmt->execute([':o' => $idObjet]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Mark a reservation as fulfilled */
    public function markFulfilled(int $idReservation): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservation SET statut = 'satisfaite' WHERE id_reservation = :id"
        );
        $stmt->execute([':id' => $idReservation]);
        return $stmt->rowCount() === 1;
    }

    /** Cancel a reservation (by patient) */
    public function cancel(int $idReservation, int $idPatient): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservation SET statut = 'annulee'
             WHERE id_reservation = :id AND id_patient = :p"
        );
        $stmt->execute([':id' => $idReservation, ':p' => $idPatient]);
        return $stmt->rowCount() === 1;
    }

    /** All reservations for a patient */
    public function getByPatient(int $idPatient): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, o.nom_objet, o.type_objet
             FROM reservation r
             INNER JOIN objet_loisir o ON r.id_objet = o.id_objet
             WHERE r.id_patient = :p
             ORDER BY r.date_reservation DESC"
        );
        $stmt->execute([':p' => $idPatient]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All reservations for admin */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT r.*, o.nom_objet, o.type_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS patient_nom,
                    (SELECT COUNT(*) FROM reservation r2
                     WHERE r2.id_objet = r.id_objet AND r2.statut = 'en_attente'
                       AND r2.date_reservation <= r.date_reservation) AS position_queue
             FROM reservation r
             INNER JOIN objet_loisir o ON r.id_objet = o.id_objet
             INNER JOIN utilisateur u ON r.id_patient = u.id_utilisateur
             ORDER BY o.nom_objet, r.date_reservation ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Count pending reservations for an object */
    public function countPending(int $idObjet): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM reservation WHERE id_objet = :o AND statut = 'en_attente'"
        );
        $stmt->execute([':o' => $idObjet]);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reservation WHERE id_reservation = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reservation WHERE id_reservation = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }
    }

    private function errorsFromQuery(): array
    {
        $messages = [
            'cancelled' => 'Réservation annulée avec succès.',
            'deleted'   => 'Réservation supprimée.',
        ];
        $key = $_GET['success'] ?? '';
        return isset($messages[$key]) ? ['success' => $messages[$key]] : [];
    }
}
