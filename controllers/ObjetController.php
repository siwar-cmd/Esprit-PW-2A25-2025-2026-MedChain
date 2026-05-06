<?php

class ObjetController
{
    public function listBack(): void
    {
        $objets     = $this->getAllObjects();
        $categories = (new Categorie())->getAll();
        require BASE_PATH . '/views/back/objet_list.php';
    }

    public function listFront(): void
    {
        $errors      = [];
        $search      = trim($_GET['search'] ?? '');
        $filterCat   = isset($_GET['categorie']) ? (int) $_GET['categorie'] : 0;
        $categories  = (new Categorie())->getAll();

        if ($search !== '') {
            $errors  = $this->validateObjectSearch($search);
            $objets  = empty($errors)
                ? $this->searchObjectsByName($search, $filterCat)
                : $this->getAllObjects($filterCat);
        } else {
            $objets = $this->getAllObjects($filterCat);
        }

        // ── Recommendation engine ──────────────────────────────────────────────────
        // Only compute recommendations for logged-in users and when
        // no search/filter is active (so the section stays relevant).
        $recommendations = [];
        if (isset($_SESSION['user_id']) && $search === '' && $filterCat === 0) {
            $recommendations = (new Recommendation())->getRecommendations((int) $_SESSION['user_id']);
        }
        // ─────────────────────────────────────────────────────────────────────

        require BASE_PATH . '/views/front/objet_list.php';
    }

    public function detailFront(int $id): void
    {
        $objet = $this->findObjectById($id);

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        $avisModel       = new AvisObjet();
        $avis            = $avisModel->getByObjet($id);
        $averageNote     = $avisModel->getAverageNote($id);
        $canReview       = false;
        $alreadyReviewed = false;

        if (isset($_SESSION['user_id'])) {
            $idPatient       = (int) $_SESSION['user_id'];
            $canReview       = $avisModel->canReview($idPatient, $id);
            $alreadyReviewed = $avisModel->hasReviewed($idPatient, $id);
        }

        $reservationCount = (new Reservation())->countPending($id);

        require BASE_PATH . '/views/front/objet_detail.php';
    }

    public function addFormBack(): void
    {
        $errors     = [];
        $categories = (new Categorie())->getAll();
        require BASE_PATH . '/views/back/objet_add.php';
    }

    public function addBack(): void
    {
        $categories = (new Categorie())->getAll();
        $data       = $this->sanitizeObjetInput($_POST);
        $errors     = $this->validateObjetData($data);

        if (empty($errors)) {
            // Fetch image automatically from Unsplash before saving
            $data['image_url'] = UnsplashService::fetchImageForObject($data['nom_objet']);

            if ($this->createObject($data)) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'added']);
            }
            $errors[] = 'Impossible d\'ajouter l\'objet.';
        }

        require BASE_PATH . '/views/back/objet_add.php';
    }

    public function editFormBack(int $id): void
    {
        $objet      = $this->findObjectById($id);
        $errors     = [];
        $categories = (new Categorie())->getAll();

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        require BASE_PATH . '/views/back/objet_edit.php';
    }

    public function editBack(int $id): void
    {
        $objet      = $this->findObjectById($id);
        $categories = (new Categorie())->getAll();

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $data   = $this->sanitizeObjetInput($_POST);
        $errors = $this->validateObjetData($data);

        if (empty($errors)) {
            if ($this->updateObjectById($id, $data)) {
                redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'updated']);
            }
            $errors[] = 'Impossible de mettre à jour l\'objet.';
        }

        $objet = array_merge($objet, $data);
        require BASE_PATH . '/views/back/objet_edit.php';
    }

    public function deleteBack(int $id): void
    {
        $result = $this->deleteObjectById($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'deleted' : $result['error'];
        redirectToRoute('objet', 'list', $params);
    }

    /**
     * Regenerate image for an existing object using Unsplash API.
     * Triggered by the "Regenerate Image" button in the admin list.
     */
    public function regenerateImageBack(int $id): void
    {
        $objet = $this->findObjectById($id);

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'back', 'error' => 'not_found']);
        }

        $newImageUrl = UnsplashService::fetchImageForObject($objet['nom_objet']);

        $stmt = $this->db()->prepare(
            'UPDATE objet_loisir SET image_url = :url WHERE id_objet = :id'
        );
        $stmt->execute([':url' => $newImageUrl, ':id' => $id]);

        redirectToRoute('objet', 'list', ['office' => 'back', 'success' => 'image_regenerated']);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function sanitizeObjetInput(array $data): array
    {
        return [
            'nom_objet'    => trim($data['nom_objet']   ?? ''),
            'type_objet'   => trim($data['type_objet']  ?? ''),
            'quantite'     => trim((string) ($data['quantite'] ?? '')),
            'etat'         => trim($data['etat']        ?? ''),
            'description'  => trim($data['description'] ?? ''),
            'id_categorie' => isset($data['id_categorie']) && $data['id_categorie'] !== ''
                                ? (int) $data['id_categorie']
                                : null,
        ];
    }

    private function validateObjetData(array $data): array
    {
        $errors = [];

        if ($data['nom_objet'] === '') {
            $errors['nom_objet'] = 'Le nom de l\'objet est obligatoire.';
        } elseif ($this->textLength($data['nom_objet']) < 2 || $this->textLength($data['nom_objet']) > 100) {
            $errors['nom_objet'] = 'Le nom doit contenir entre 2 et 100 caractères.';
        } elseif (!$this->isValidObjectText($data['nom_objet'])) {
            $errors['nom_objet'] = 'Le nom contient des caractères invalides.';
        }

        if ($data['type_objet'] === '') {
            $errors['type_objet'] = 'Le type est obligatoire.';
        } elseif (!in_array($data['type_objet'], $this->allowedObjectTypes(), true)) {
            $errors['type_objet'] = 'Type invalide.';
        }

        if ($data['quantite'] === '') {
            $errors['quantite'] = 'La quantité est obligatoire.';
        } elseif (!preg_match('/^\d+$/', $data['quantite'])) {
            $errors['quantite'] = 'La quantité doit être un entier.';
        } elseif ((int) $data['quantite'] > 9999) {
            $errors['quantite'] = 'La quantité ne peut pas dépasser 9999.';
        }

        if ($data['etat'] === '') {
            $errors['etat'] = 'L\'état est obligatoire.';
        } elseif (!in_array($data['etat'], $this->allowedObjectStates(), true)) {
            $errors['etat'] = 'État invalide.';
        }

        if ($data['description'] !== '' && $this->textLength($data['description']) > 500) {
            $errors['description'] = 'La description ne doit pas dépasser 500 caractères.';
        }

        return $errors;
    }

    public function findObjectById(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT o.*, c.nom_categorie, c.icone AS categorie_icone
             FROM objet_loisir o
             LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
             WHERE o.id_objet = :id'
        );
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

    private function getAllObjects(int $filterCat = 0): array
    {
        if ($filterCat > 0) {
            $stmt = $this->db()->prepare(
                'SELECT o.*, c.nom_categorie, c.icone AS categorie_icone
                 FROM objet_loisir o
                 LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
                 WHERE o.id_categorie = :cat
                 ORDER BY o.nom_objet'
            );
            $stmt->execute([':cat' => $filterCat]);
        } else {
            $stmt = $this->db()->query(
                'SELECT o.*, c.nom_categorie, c.icone AS categorie_icone
                 FROM objet_loisir o
                 LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
                 ORDER BY o.nom_objet'
            );
        }
        return array_map([$this, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function searchObjectsByName(string $search, int $filterCat = 0): array
    {
        $sql = 'SELECT o.*, c.nom_categorie, c.icone AS categorie_icone
                FROM objet_loisir o
                LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
                WHERE o.nom_objet LIKE :search';
        $params = [':search' => '%' . $search . '%'];

        if ($filterCat > 0) {
            $sql .= ' AND o.id_categorie = :cat';
            $params[':cat'] = $filterCat;
        }

        $sql .= ' ORDER BY o.nom_objet';
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function createObject(array $data): bool
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO objet_loisir
                (nom_objet, type_objet, quantite, etat, disponibilite, description, id_categorie, image_url)
             VALUES
                (:nom_objet, :type_objet, :quantite, :etat, :disponibilite, :description, :id_categorie, :image_url)'
        );
        return $stmt->execute($this->normalizeObjectData($data));
    }

    private function updateObjectById(int $id, array $data): bool
    {
        $payload = $this->normalizeObjectData($data);
        $payload['id_objet'] = $id;

        $stmt = $this->db()->prepare(
            'UPDATE objet_loisir
             SET nom_objet    = :nom_objet,
                 type_objet   = :type_objet,
                 quantite     = :quantite,
                 etat         = :etat,
                 disponibilite= :disponibilite,
                 description  = :description,
                 id_categorie = :id_categorie
             WHERE id_objet = :id_objet'
        );
        return $stmt->execute($payload);
    }

    private function deleteObjectById(int $id): array
    {
        $db    = $this->db();
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
            'nom_objet'    => trim($data['nom_objet']  ?? ''),
            'type_objet'   => trim($data['type_objet'] ?? ''),
            'quantite'     => $quantity,
            'etat'         => trim($data['etat']       ?? ''),
            'disponibilite'=> $quantity > 0 ? 'disponible' : 'indisponible',
            'description'  => trim($data['description'] ?? ''),
            'id_categorie' => $data['id_categorie'] ?: null,
            'image_url'    => $data['image_url'] ?? null,
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
            $errors[] = 'La recherche ne doit pas dépasser 100 caractères.';
        } elseif (!$this->isValidObjectText($search)) {
            $errors[] = 'La recherche contient des caractères invalides.';
        }
        return $errors;
    }

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
