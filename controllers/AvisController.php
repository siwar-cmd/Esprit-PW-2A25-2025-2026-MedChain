<?php

class AvisController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── DB methods (moved from AvisObjet model) ───────────────────────────

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

    public function getAverageNote(int $idObjet): ?float
    {
        $stmt = $this->db->prepare(
            'SELECT AVG(note) FROM avis_objet WHERE id_objet = :id'
        );
        $stmt->execute([':id' => $idObjet]);
        $avg = $stmt->fetchColumn();
        return $avg !== false && $avg !== null ? round((float) $avg, 1) : null;
    }

    public function hasReviewed(int $idPatient, int $idObjet): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM avis_objet WHERE id_patient = :p AND id_objet = :o'
        );
        $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function canReview(int $idPatient, int $idObjet): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pret
             WHERE id_patient = :p AND id_objet = :o AND statut = 'termine'"
        );
        $stmt->execute([':p' => $idPatient, ':o' => $idObjet]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function create(int $idPatient, int $idObjet, int $note, string $commentaire): bool
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

    private function delete(int $idAvis): bool
    {
        $stmt = $this->db->prepare('DELETE FROM avis_objet WHERE id_avis = :id');
        $stmt->execute([':id' => $idAvis]);
        return $stmt->rowCount() === 1;
    }

    private function findById(int $idAvis): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM avis_objet WHERE id_avis = :id');
        $stmt->execute([':id' => $idAvis]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getAll(): array
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

    // ── Action methods ────────────────────────────────────────────────────

    public function createForm(): void
    {
        $this->requireLogin();
        $idObjet = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;

        if ($idObjet <= 0) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $objet = (new ObjetController())->findObjectById($idObjet);
        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        $idPatient = (int) $_SESSION['user_id'];

        if (!$this->canReview($idPatient, $idObjet)) {
            redirectToRoute('pret', 'myLoans', ['office' => 'front', 'error' => 'cannot_review']);
        }
        if ($this->hasReviewed($idPatient, $idObjet)) {
            redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'error' => 'already_reviewed']);
        }

        $errors = [];
        require BASE_PATH . '/views/front/avis_create.php';
    }

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

        if ($note < 1 || $note > 5) {
            $errors[] = 'Veuillez sélectionner une note entre 1 et 5 étoiles.';
        }
        if (mb_strlen($comment) < 5) {
            $errors[] = 'Le commentaire doit contenir au moins 5 caractères.';
        }
        if (mb_strlen($comment) > 1000) {
            $errors[] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
        }
        if (!$this->canReview($idPatient, $idObjet)) {
            $errors[] = 'Vous ne pouvez laisser un avis que pour un objet que vous avez emprunté et retourné.';
        }
        if ($this->hasReviewed($idPatient, $idObjet)) {
            $errors[] = 'Vous avez déjà laissé un avis pour cet objet.';
        }

        if (!empty($errors)) {
            require BASE_PATH . '/views/front/avis_create.php';
            return;
        }

        $this->create($idPatient, $idObjet, $note, $comment);
        redirectToRoute('objet', 'detail', ['office' => 'front', 'id' => $idObjet, 'success' => 'avis_added']);
    }

    public function listBack(): void
    {
        $avis   = $this->getAll();
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/avis_list.php';
    }

    public function deleteBack(int $id): void
    {
        $this->delete($id);
        redirectToRoute('avis', 'list', ['office' => 'back', 'success' => 'deleted']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

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
