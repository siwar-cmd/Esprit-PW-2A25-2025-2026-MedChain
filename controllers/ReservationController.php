<?php

class ReservationController
{
    private Reservation $model;

    public function __construct()
    {
        $this->model = new Reservation();
    }

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

        // Only allow reservation when object is truly unavailable
        if ((int) $objet['quantite'] > 0) {
            redirectToRoute('pret', 'create', ['office' => 'front', 'objet_id' => $idObjet]);
        }

        $result = $this->model->create($idPatient, $idObjet);

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
        $idPatient = (int) $_SESSION['user_id'];
        $this->model->cancel($id, $idPatient);
        redirectToRoute('reservation', 'myList', ['office' => 'front', 'success' => 'cancelled']);
    }

    /** Front: show patient's own reservations */
    public function myListFront(): void
    {
        $this->requireLogin();
        $idPatient    = (int) $_SESSION['user_id'];
        $reservations = $this->model->getByPatient($idPatient);
        $errors       = $this->errorsFromQuery();
        require BASE_PATH . '/views/front/reservation_list.php';
    }

    /** Back: admin sees all waiting lists */
    public function listBack(): void
    {
        $reservations = $this->model->getAll();
        $errors       = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/reservation_list.php';
    }

    /** Back: admin deletes a reservation entry */
    public function deleteBack(int $id): void
    {
        $this->model->deleteById($id);
        redirectToRoute('reservation', 'list', ['office' => 'back', 'success' => 'deleted']);
    }

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
