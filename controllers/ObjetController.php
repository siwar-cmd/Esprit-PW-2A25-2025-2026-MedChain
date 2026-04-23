<?php

class ObjetController
{
    public function listBack(): void
    {
        $objets = $this->getAllObjects();
        require BASE_PATH . '/views/back/objet_list.php';
    }

    public function listFront(): void
    {
        $errors = [];
        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $errors = $this->validateObjectSearch($search);
            $objets = empty($errors)
                ? $this->searchObjectsByName($search)
                : $this->getAllObjects();
        } else {
            $objets = $this->getAllObjects();
        }
        require BASE_PATH . '/views/front/objet_list.php';
    }

    public function detailFront(int $id): void
    {
        $objet = $this->findObjectById($id);

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        require BASE_PATH . '/views/front/objet_detail.php';
    }

    public function addFormBack(): void
    {
        $errors = [];
        require BASE_PATH . '/views/back/objet_add.php';
    }

    public function addBack(): void
    {
        $data = $this->sanitizeObjetInput($_POST);
        $errors = $this->validateObjetData($data);

        if (empty($errors)) {
            $created = $this->createObject($data);

            if ($created) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'added']);
            }

            $errors[] = "Unable to add the object.";
        }

        require BASE_PATH . '/views/back/objet_add.php';
    }

    public function editFormBack(int $id): void
    {
        $objet = $this->findObjectById($id);
        $errors = [];

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        require BASE_PATH . '/views/back/objet_edit.php';
    }

    public function editBack(int $id): void
    {
        $objet = $this->findObjectById($id);

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $data = $this->sanitizeObjetInput($_POST);
        $errors = $this->validateObjetData($data);

        if (empty($errors)) {
            $updated = $this->updateObjectById($id, $data);

            if ($updated) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'updated']);
            }

            $errors[] = "Unable to update the object.";
        }

        $objet = array_merge($objet, $data);
        require BASE_PATH . '/views/back/objet_edit.php';
    }

    public function deleteBack(int $id): void
    {
        $result = $this->deleteObjectById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'deleted';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('objet', 'list', $params);
    }

    private function sanitizeObjetInput(array $data): array
    {
        return [
            'nom_objet' => trim($data['nom_objet'] ?? ''),
            'type_objet' => trim($data['type_objet'] ?? ''),
            'quantite' => trim((string) ($data['quantite'] ?? '')),
            'etat' => trim($data['etat'] ?? ''),
            'description' => trim($data['description'] ?? ''),
        ];
    }

    private function validateObjetData(array $data): array
    {
        $errors = [];

        if ($data['nom_objet'] === '') {
            $errors[] = 'Object name is required.';
        } elseif ($this->textLength($data['nom_objet']) < 2 || $this->textLength($data['nom_objet']) > 100) {
            $errors[] = 'Object name must contain between 2 and 100 characters.';
        } elseif (!$this->isValidObjectText($data['nom_objet'])) {
            $errors[] = 'Object name contains invalid characters.';
        }

        if ($data['type_objet'] === '') {
            $errors[] = 'Object type is required.';
        } elseif (!in_array($data['type_objet'], $this->allowedObjectTypes(), true)) {
            $errors[] = 'Selected object type is invalid.';
        }

        if ($data['quantite'] === '') {
            $errors[] = 'Quantity is required.';
        } elseif (!preg_match('/^\d+$/', $data['quantite'])) {
            $errors[] = 'Quantity must be a whole number.';
        } elseif ((int) $data['quantite'] > 9999) {
            $errors[] = 'Quantity must be 9999 or less.';
        }

        if ($data['etat'] === '') {
            $errors[] = 'Object condition is required.';
        } elseif (!in_array($data['etat'], $this->allowedObjectStates(), true)) {
            $errors[] = 'Selected object condition is invalid.';
        }

        if ($data['description'] !== '') {
            if ($this->textLength($data['description']) > 500) {
                $errors[] = 'Description must not exceed 500 characters.';
            } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $data['description'])) {
                $errors[] = 'Description contains invalid characters.';
            }
        }

        return $errors;
    }

    public function findObjectById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM objet_loisir WHERE id_objet = :id');
        $stmt->execute([':id' => $id]);
        $objet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $objet ? $this->hydrateAvailability($objet) : null;
    }

    public function countAllObjects(): int
    {
        return (int) $this->db()->query('SELECT COUNT(*) FROM objet_loisir')->fetchColumn();
    }

    private function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    private function getAllObjects(): array
    {
        $stmt = $this->db()->query('SELECT * FROM objet_loisir ORDER BY nom_objet');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateAvailability'], $rows);
    }

    private function searchObjectsByName(string $search): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM objet_loisir WHERE nom_objet LIKE :search ORDER BY nom_objet');
        $stmt->execute([':search' => '%' . $search . '%']);

        return array_map([$this, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function createObject(array $data): bool
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO objet_loisir (nom_objet, type_objet, quantite, etat, disponibilite, description)
             VALUES (:nom_objet, :type_objet, :quantite, :etat, :disponibilite, :description)'
        );

        return $stmt->execute($this->normalizeObjectData($data));
    }

    private function updateObjectById(int $id, array $data): bool
    {
        $payload = $this->normalizeObjectData($data);
        $payload['id_objet'] = $id;

        $stmt = $this->db()->prepare(
            'UPDATE objet_loisir
             SET nom_objet = :nom_objet,
                 type_objet = :type_objet,
                 quantite = :quantite,
                 etat = :etat,
                 disponibilite = :disponibilite,
                 description = :description
             WHERE id_objet = :id_objet'
        );

        return $stmt->execute($payload);
    }

    private function deleteObjectById(int $id): array
    {
        $db = $this->db();

        $check = $db->prepare('SELECT COUNT(*) FROM pret WHERE id_objet = :id');
        $check->execute([':id' => $id]);

        if ((int) $check->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'linked_to_loans'];
        }

        $stmt = $db->prepare('DELETE FROM objet_loisir WHERE id_objet = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1
            ? ['success' => true]
            : ['success' => false, 'error' => 'not_found'];
    }

    private function normalizeObjectData(array $data): array
    {
        $quantity = max(0, (int) ($data['quantite'] ?? 0));

        return [
            'nom_objet' => trim($data['nom_objet'] ?? ''),
            'type_objet' => trim($data['type_objet'] ?? ''),
            'quantite' => $quantity,
            'etat' => trim($data['etat'] ?? ''),
            'disponibilite' => $quantity > 0 ? 'disponible' : 'indisponible',
            'description' => trim($data['description'] ?? ''),
        ];
    }

    private function hydrateAvailability(array $row): array
    {
        $row['disponibilite'] = ((int) ($row['quantite'] ?? 0) > 0) ? 'disponible' : 'indisponible';

        return $row;
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

    private function isValidObjectText(string $value): bool
    {
        return preg_match("/^[\\p{L}\\p{N}\\s'’().,\\-\\/]+$/u", $value) === 1;
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
