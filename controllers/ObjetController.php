<?php
declare(strict_types=1);

/**
 * ObjetController — Handles leisure-object CRUD.
 *
 * Extends BaseController to inherit requireAdmin / requireAuth guards.
 * All SQL lives in ObjetLoisir (model) — zero raw SQL here.
 */
class ObjetController extends BaseController
{
    // ═══════════════════════════════════════════════════════════
    //  BACK-OFFICE (admin only)
    // ═══════════════════════════════════════════════════════════

    public function listBack(): void
    {
        $this->requireAdmin();
        $objets = ObjetLoisir::findAll();
        require VIEWS_BACK . '/objet-list.php';
    }

    public function addFormBack(): void
    {
        $this->requireAdmin();
        $errors = [];
        require VIEWS_BACK . '/objet-add.php';
    }

    public function addBack(): void
    {
        $this->requireAdmin();

        $data   = $this->sanitizeObjetInput($_POST);
        $errors = $this->validateObjetData($data);

        if (empty($errors)) {
            if (ObjetLoisir::create($data)) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'added']);
            }
            $errors[] = "Unable to add the object.";
        }

        require VIEWS_BACK . '/objet-add.php';
    }

    public function editFormBack(int $id): void
    {
        $this->requireAdmin();

        $objet  = ObjetLoisir::findById($id);
        $errors = [];

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        require VIEWS_BACK . '/objet-edit.php';
    }

    public function editBack(int $id): void
    {
        $this->requireAdmin();

        $objet = ObjetLoisir::findById($id);
        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $data   = $this->sanitizeObjetInput($_POST);
        $errors = $this->validateObjetData($data);

        if (empty($errors)) {
            if (ObjetLoisir::updateById($id, $data)) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'updated']);
            }
            $errors[] = "Unable to update the object.";
        }

        $objet = array_merge($objet, $data);
        require VIEWS_BACK . '/objet-edit.php';
    }

    public function deleteBack(int $id): void
    {
        $this->requireAdmin();

        $result = ObjetLoisir::deleteById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'deleted';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('objet', 'list', $params);
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONT-OFFICE (authenticated users)
    // ═══════════════════════════════════════════════════════════

    public function listFront(): void
    {
        $this->requireAuth();

        $errors = [];
        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $errors = $this->validateObjectSearch($search);
            $objets = empty($errors)
                ? ObjetLoisir::searchByName($search)
                : ObjetLoisir::findAll();
        } else {
            $objets = ObjetLoisir::findAll();
        }

        require VIEWS_FRONT . '/loisirs/objet-list.php';
    }

    public function detailFront(int $id): void
    {
        $this->requireAuth();

        $objet = ObjetLoisir::findById($id);

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        require VIEWS_FRONT . '/loisirs/objet-detail.php';
    }

    // ═══════════════════════════════════════════════════════════
    //  VALIDATION
    // ═══════════════════════════════════════════════════════════

    private function sanitizeObjetInput(array $data): array
    {
        return [
            'nom_objet'   => trim($data['nom_objet'] ?? ''),
            'type_objet'  => trim($data['type_objet'] ?? ''),
            'quantite'    => trim((string) ($data['quantite'] ?? '')),
            'etat'        => trim($data['etat'] ?? ''),
            'description' => trim($data['description'] ?? ''),
        ];
    }

    private function validateObjetData(array $data): array
    {
        $errors = [];

        // NAME
        if ($data['nom_objet'] === '') {
            $errors['nom_objet'] = 'Object name is required.';
        } elseif ($this->textLength($data['nom_objet']) < 2 || $this->textLength($data['nom_objet']) > 100) {
            $errors['nom_objet'] = 'Object name must be between 2 and 100 characters.';
        } elseif (!$this->isValidObjectText($data['nom_objet'])) {
            $errors['nom_objet'] = 'Object name contains invalid characters.';
        }

        // TYPE
        if ($data['type_objet'] === '') {
            $errors['type_objet'] = 'Object type is required.';
        } elseif (!in_array($data['type_objet'], $this->allowedObjectTypes(), true)) {
            $errors['type_objet'] = 'Selected object type is invalid.';
        }

        // QUANTITY
        if ($data['quantite'] === '') {
            $errors['quantite'] = 'Quantity is required.';
        } elseif (!preg_match('/^\d+$/', $data['quantite'])) {
            $errors['quantite'] = 'Quantity must be a whole number.';
        } elseif ((int) $data['quantite'] > 9999) {
            $errors['quantite'] = 'Quantity must be 9999 or less.';
        }

        // ETAT
        if ($data['etat'] === '') {
            $errors['etat'] = 'Object condition is required.';
        } elseif (!in_array($data['etat'], $this->allowedObjectStates(), true)) {
            $errors['etat'] = 'Selected object condition is invalid.';
        }

        // DESCRIPTION
        if ($data['description'] !== '') {
            if ($this->textLength($data['description']) > 500) {
                $errors['description'] = 'Description must not exceed 500 characters.';
            } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $data['description'])) {
                $errors['description'] = 'Description contains invalid characters.';
            }
        }

        return $errors;
    }

    private function validateObjectSearch(string $search): array
    {
        $errors = [];

        if ($this->textLength($search) > 100) {
            $errors[] = 'Search text must not exceed 100 characters.';
        } elseif (!$this->isValidObjectText($search)) {
            $errors[] = 'Search text contains invalid characters.';
        }

        return $errors;
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════

    private function isValidObjectText(string $value): bool
    {
        return preg_match("/^[\\p{L}\\p{N}\\s''().,\\-\\/]+$/u", $value) === 1;
    }

    private function allowedObjectTypes(): array
    {
        return ['Livre', 'Jeu de societe', 'Sport', 'Musique', 'Electronique', 'Casse-tete', 'Film'];
    }

    private function allowedObjectStates(): array
    {
        return ['neuf', 'bon', 'acceptable', 'moyen', 'use'];
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
