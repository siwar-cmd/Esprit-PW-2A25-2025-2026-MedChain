<?php

class AvisController
{
    private AvisObjet $model;

    public function __construct()
    {
        $this->model = new AvisObjet();
    }

    /** Front: show the review form */
    public function createForm(): void
    {
        $this->requireLogin();
        $idObjet = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;

        if ($idObjet <= 0) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $objet  = (new ObjetController())->findObjectById($idObjet);
        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        $idPatient = (int) $_SESSION['user_id'];

        if (!$this->model->canReview($idPatient, $idObjet)) {
            redirectToRoute('pret', 'myLoans', ['office' => 'front', 'error' => 'cannot_review']);
        }
        if ($this->model->hasReviewed($idPatient, $idObjet)) {
            redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'error' => 'already_reviewed']);
        }

        $errors = [];
        require BASE_PATH . '/views/front/avis_create.php';
    }

    /** Front: handle form submission */
    public function store(): void
    {
        $this->requireLogin();

        $idObjet   = isset($_POST['id_objet']) ? (int) $_POST['id_objet'] : 0;
        $idPatient = (int) $_SESSION['user_id'];
        $note      = isset($_POST['note']) ? (int) $_POST['note'] : 0;
        $comment   = trim($_POST['commentaire'] ?? '');
        $errors    = [];

        if ($idObjet <= 0) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $objet = (new ObjetController())->findObjectById($idObjet);
        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        // Validate
        if ($note < 1 || $note > 5) {
            $errors[] = 'Veuillez sélectionner une note entre 1 et 5 étoiles.';
        }
        if (mb_strlen($comment) < 5) {
            $errors[] = 'Le commentaire doit contenir au moins 5 caractères.';
        }
        if (mb_strlen($comment) > 1000) {
            $errors[] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
        }
        if (!$this->model->canReview($idPatient, $idObjet)) {
            $errors[] = 'Vous ne pouvez laisser un avis que pour un objet que vous avez emprunté et retourné.';
        }
        if ($this->model->hasReviewed($idPatient, $idObjet)) {
            $errors[] = 'Vous avez déjà laissé un avis pour cet objet.';
        }

        if (!empty($errors)) {
            require BASE_PATH . '/views/front/avis_create.php';
            return;
        }

        $this->model->create($idPatient, $idObjet, $note, $comment);
        redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'success' => 'avis_added']);
    }

    /** Back: list all reviews */
    public function listBack(): void
    {
        $avis   = $this->model->getAll();
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/avis_list.php';
    }

    /** Back: delete a review */
    public function deleteBack(int $id): void
    {
        $this->model->delete($id);
        redirectToRoute('avis', 'list', ['office' => 'back', 'success' => 'deleted']);
    }

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }
    }

    private function errorsFromQuery(): array
    {
        $map = ['deleted' => 'Avis supprimé avec succès.'];
        $key = $_GET['success'] ?? '';
        return isset($map[$key]) ? ['success' => $map[$key]] : [];
    }
}
