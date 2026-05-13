<?php

class CategorieController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── DB methods (moved from Categorie model) ───────────────────────────

    public function getAll(): array
    {
        return $this->db
            ->query('SELECT * FROM categorie_objet ORDER BY nom_categorie')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categorie_objet WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insert(string $nom, string $description, string $icone): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categorie_objet (nom_categorie, description, icone)
             VALUES (:nom, :desc, :icone)'
        );
        return $stmt->execute([':nom' => $nom, ':desc' => $description, ':icone' => $icone]);
    }

    private function update(int $id, string $nom, string $description, string $icone): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categorie_objet
             SET nom_categorie = :nom, description = :desc, icone = :icone
             WHERE id_categorie = :id'
        );
        return $stmt->execute([':nom' => $nom, ':desc' => $description, ':icone' => $icone, ':id' => $id]);
    }

    private function delete(int $id): array
    {
        $check = $this->db->prepare('SELECT COUNT(*) FROM objet_loisir WHERE id_categorie = :id');
        $check->execute([':id' => $id]);
        if ((int) $check->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'linked_to_objects'];
        }

        $stmt = $this->db->prepare('DELETE FROM categorie_objet WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 1
            ? ['success' => true]
            : ['success' => false, 'error' => 'not_found'];
    }

    private function nameExists(string $nom, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM categorie_objet
             WHERE nom_categorie = :nom AND id_categorie != :exclude'
        );
        $stmt->execute([':nom' => $nom, ':exclude' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Action methods ────────────────────────────────────────────────────

    public function listBack(): void
    {
        $categories = $this->getAll();
        $errors     = $this->flashFromQuery();
        require BASE_PATH . '/views/back/categorie_list.php';
    }

    public function addFormBack(): void
    {
        $errors = [];
        require BASE_PATH . '/views/back/categorie_add.php';
    }

    public function addBack(): void
    {
        $nom         = trim($_POST['nom_categorie']  ?? '');
        $description = trim($_POST['description']    ?? '');
        $icone       = trim($_POST['icone']          ?? 'bi-tag');
        $errors      = $this->validate($nom, $icone);

        if (empty($errors)) {
            if ($this->nameExists($nom)) {
                $errors['nom_categorie'] = 'Ce nom de catégorie existe déjà.';
            } else {
                $this->insert($nom, $description, $icone);
                redirectToRoute('categorie', 'list', ['office' => 'back', 'success' => 'added']);
            }
        }

        require BASE_PATH . '/views/back/categorie_add.php';
    }

    public function editFormBack(int $id): void
    {
        $categorie = $this->findById($id);
        if ($categorie === null) {
            redirectToRoute('categorie', 'list', ['office' => 'back', 'error' => 'not_found']);
        }
        $errors = [];
        require BASE_PATH . '/views/back/categorie_edit.php';
    }

    public function editBack(int $id): void
    {
        $categorie = $this->findById($id);
        if ($categorie === null) {
            redirectToRoute('categorie', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $nom         = trim($_POST['nom_categorie']  ?? '');
        $description = trim($_POST['description']    ?? '');
        $icone       = trim($_POST['icone']          ?? 'bi-tag');
        $errors      = $this->validate($nom, $icone);

        if (empty($errors)) {
            if ($this->nameExists($nom, $id)) {
                $errors['nom_categorie'] = 'Ce nom de catégorie existe déjà.';
            } else {
                $this->update($id, $nom, $description, $icone);
                redirectToRoute('categorie', 'list', ['office' => 'back', 'success' => 'updated']);
            }
        }

        $categorie = array_merge($categorie, [
            'nom_categorie' => $nom,
            'description'   => $description,
            'icone'         => $icone,
        ]);
        require BASE_PATH . '/views/back/categorie_edit.php';
    }

    public function deleteBack(int $id): void
    {
        $result = $this->delete($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'deleted' : $result['error'];
        redirectToRoute('categorie', 'list', $params);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function validate(string $nom, string $icone): array
    {
        $errors = [];

        if ($nom === '') {
            $errors['nom_categorie'] = 'Le nom de la catégorie est obligatoire.';
        } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $errors['nom_categorie'] = 'Le nom doit contenir entre 2 et 100 caractères.';
        }

        if ($icone === '') {
            $errors['icone'] = 'L\'icône est obligatoire.';
        } elseif (!preg_match('/^bi-[a-z0-9\-]+$/', $icone)) {
            $errors['icone'] = 'Format d\'icône invalide (ex: bi-book-fill).';
        }

        return $errors;
    }

    private function flashFromQuery(): array
    {
        $successMap = [
            'added'   => 'Catégorie ajoutée avec succès.',
            'updated' => 'Catégorie mise à jour avec succès.',
            'deleted' => 'Catégorie supprimée avec succès.',
        ];
        $errorMap = [
            'linked_to_objects' => 'Cette catégorie est liée à des objets et ne peut pas être supprimée.',
            'not_found'         => 'Catégorie introuvable.',
        ];

        $flash = [];
        if (isset($_GET['success'], $successMap[$_GET['success']])) {
            $flash['success'] = $successMap[$_GET['success']];
        }
        if (isset($_GET['error'], $errorMap[$_GET['error']])) {
            $flash['error'] = $errorMap[$_GET['error']];
        }
        return $flash;
    }
}
