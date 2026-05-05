<?php

class CategorieController
{
    private Categorie $model;

    public function __construct()
    {
        $this->model = new Categorie();
    }

    public function listBack(): void
    {
        $categories = $this->model->getAll();
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
            if ($this->model->nameExists($nom)) {
                $errors['nom_categorie'] = 'Ce nom de catégorie existe déjà.';
            } else {
                $this->model->create($nom, $description, $icone);
                redirectToRoute('categorie', 'list', ['office' => 'back', 'success' => 'added']);
            }
        }

        require BASE_PATH . '/views/back/categorie_add.php';
    }

    public function editFormBack(int $id): void
    {
        $categorie = $this->model->findById($id);
        if ($categorie === null) {
            redirectToRoute('categorie', 'list', ['office' => 'back', 'error' => 'not_found']);
        }
        $errors = [];
        require BASE_PATH . '/views/back/categorie_edit.php';
    }

    public function editBack(int $id): void
    {
        $categorie = $this->model->findById($id);
        if ($categorie === null) {
            redirectToRoute('categorie', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $nom         = trim($_POST['nom_categorie']  ?? '');
        $description = trim($_POST['description']    ?? '');
        $icone       = trim($_POST['icone']          ?? 'bi-tag');
        $errors      = $this->validate($nom, $icone);

        if (empty($errors)) {
            if ($this->model->nameExists($nom, $id)) {
                $errors['nom_categorie'] = 'Ce nom de catégorie existe déjà.';
            } else {
                $this->model->update($id, $nom, $description, $icone);
                redirectToRoute('categorie', 'list', ['office' => 'back', 'success' => 'updated']);
            }
        }

        // Merge posted values back so the form repopulates
        $categorie = array_merge($categorie, [
            'nom_categorie' => $nom,
            'description'   => $description,
            'icone'         => $icone,
        ]);
        require BASE_PATH . '/views/back/categorie_edit.php';
    }

    public function deleteBack(int $id): void
    {
        $result = $this->model->delete($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'deleted' : $result['error'];
        redirectToRoute('categorie', 'list', $params);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
