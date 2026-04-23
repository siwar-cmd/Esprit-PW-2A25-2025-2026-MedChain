<?php

class PretController
{
    public function pendingBack(): void
    {
        $prets = $this->addStatusLabels($this->getLoansByCondition("p.statut = 'en_attente'"));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_pending.php';
    }

    public function confirmedBack(): void
    {
        $prets = $this->addStatusLabels($this->getLoansByCondition("p.statut = 'en_cours'"));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_confirmed.php';
    }

    public function listBack(): void
    {
        $prets = $this->addStatusLabels($this->getLoansByCondition('1 = 1'));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_list.php';
    }

    public function confirmBack(int $id): void
    {
        $result = $this->confirmLoanById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'confirmed';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'pending', $params);
    }

    public function cancelBack(int $id): void
    {
        $result = $this->cancelLoanById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'cancelled';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'list', $params);
    }

    public function returnBack(int $id): void
    {
        $result = $this->returnLoanById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'returned';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'confirmed', $params);
    }

    public function createFront(): void
    {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $objectId = isset($_POST['id_objet']) ? (int) $_POST['id_objet'] : 0;
            $patientName = trim($_POST['nom_patient'] ?? '');
            $datePret = trim($_POST['date_pret'] ?? date('Y-m-d'));

            $result = $this->createLoanRequest($objectId, $patientName, $datePret);

            if ($result['success']) {
                redirectToRoute('objet', 'list', ['office' => 'front', 'success' => 'requested']);
            }

            $errors[] = $result['message'];
            $objet = $this->objetController()->findObjectById($objectId);
        } else {
            $objectId = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;
            $objet = $this->objetController()->findObjectById($objectId);
        }

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        require BASE_PATH . '/views/front/pret_create.php';
    }

    public function myLoansFront(): void
    {
        $nomPatient = trim($_GET['patient'] ?? '');
        $errors = $this->errorsFromQuery();
        $isSearchSubmission = array_key_exists('patient', $_GET);

        if (!$isSearchSubmission && $nomPatient === '') {
            require BASE_PATH . '/views/front/pret_search.php';
            return;
        }

        $errors = array_merge($errors, $this->validatePatientName($nomPatient));

        if (!empty($errors)) {
            require BASE_PATH . '/views/front/pret_search.php';
            return;
        }

        $prets = $this->addStatusLabels($this->getLoansByPatientName($nomPatient));
        require BASE_PATH . '/views/front/pret_myloans.php';
    }

    public function cancelFront(int $id): void
    {
        $patient = trim($_GET['patient'] ?? '');
        $result = $this->cancelLoanById($id);
        $params = ['office' => 'front'];

        if ($patient !== '') {
            $params['patient'] = $patient;
        }

        if ($result['success']) {
            $params['success'] = 'cancelled';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'myLoans', $params);
    }

    public function returnFront(int $id): void
    {
        $patient = trim($_GET['patient'] ?? '');
        $result = $this->returnLoanById($id);
        $params = ['office' => 'front'];

        if ($patient !== '') {
            $params['patient'] = $patient;
        }

        if ($result['success']) {
            $params['success'] = 'returned';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'myLoans', $params);
    }

    private function errorsFromQuery(): array
    {
        $messages = [
            'already_processed' => 'This loan has already been processed.',
            'invalid_status' => 'The requested status change is not allowed.',
            'loan_not_found' => 'Loan not found.',
            'object_not_found' => 'Object not found.',
            'stock_unavailable' => 'The object is out of stock.',
            'update_failed' => 'The action could not be completed.',
        ];

        $errorKey = $_GET['error'] ?? '';

        return isset($messages[$errorKey]) ? [$messages[$errorKey]] : [];
    }

    public function countLoansByStatus(string $status): int
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM pret WHERE statut = :statut');
        $stmt->execute([':statut' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public function getRecentPendingLoans(int $limit = 5): array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, o.nom_objet
             FROM pret p
             LEFT JOIN objet_loisir o ON p.id_objet = o.id_objet
             WHERE p.statut = :statut
             ORDER BY p.date_pret DESC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute([':statut' => 'en_attente']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    private function objetController(): ObjetController
    {
        return new ObjetController();
    }

    private function getLoansByCondition(string $condition): array
    {
        $stmt = $this->db()->query(
            "SELECT p.*, o.nom_objet AS objet_nom, o.type_objet AS objet_type
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             WHERE {$condition}
             ORDER BY p.date_pret DESC, p.id_pret DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLoansByPatientName(string $patientName): array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, o.nom_objet AS objet_nom, o.type_objet AS objet_type
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             WHERE p.nom_patient = :nom_patient
             ORDER BY p.date_pret DESC, p.id_pret DESC'
        );
        $stmt->execute([':nom_patient' => $patientName]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createLoanRequest(int $objectId, string $patientName, string $datePret): array
    {
        if ($objectId <= 0) {
            return ['success' => false, 'message' => 'Invalid object selection.'];
        }

        $patientNameErrors = $this->validatePatientName($patientName);
        if (!empty($patientNameErrors)) {
            return ['success' => false, 'message' => $patientNameErrors[0]];
        }

        if (!$this->isValidLoanDate($datePret)) {
            return ['success' => false, 'message' => 'Loan date is invalid.'];
        }

        $objet = $this->objetController()->findObjectById($objectId);

        if ($objet === null) {
            return ['success' => false, 'message' => 'Object not found.'];
        }

        if ((int) $objet['quantite'] <= 0 || $objet['disponibilite'] !== 'disponible') {
            return ['success' => false, 'message' => 'This object is currently unavailable.'];
        }

        $loanDate = $datePret !== '' ? $datePret : date('Y-m-d');
        $returnDate = (new DateTimeImmutable($loanDate))->modify('+7 days')->format('Y-m-d');

        $stmt = $this->db()->prepare(
            'INSERT INTO pret (id_objet, nom_patient, date_pret, date_retour_prevue, statut)
             VALUES (:id_objet, :nom_patient, :date_pret, :date_retour_prevue, :statut)'
        );

        $success = $stmt->execute([
            ':id_objet' => $objectId,
            ':nom_patient' => $patientName,
            ':date_pret' => $loanDate,
            ':date_retour_prevue' => $returnDate,
            ':statut' => 'en_attente',
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'Loan request created.' : 'Unable to create the loan request.',
        ];
    }

    private function confirmLoanById(int $id): array
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if ($pret['statut'] !== 'en_attente') {
                $db->rollBack();
                return ['success' => false, 'error' => 'already_processed'];
            }

            $stockUpdate = $db->prepare(
                "UPDATE objet_loisir
                 SET quantite = quantite - 1,
                     disponibilite = CASE WHEN quantite - 1 > 0 THEN 'disponible' ELSE 'indisponible' END
                 WHERE id_objet = :id_objet AND quantite > 0"
            );
            $stockUpdate->execute([':id_objet' => $pret['id_objet']]);

            if ($stockUpdate->rowCount() !== 1) {
                $db->rollBack();
                return ['success' => false, 'error' => 'stock_unavailable'];
            }

            $pretUpdate = $db->prepare("UPDATE pret SET statut = 'en_cours' WHERE id_pret = :id_pret");
            $pretUpdate->execute([':id_pret' => $id]);

            $db->commit();
            return ['success' => true];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function cancelLoanById(int $id): array
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if (!in_array($pret['statut'], ['en_attente', 'en_cours'], true)) {
                $db->rollBack();
                return ['success' => false, 'error' => 'invalid_status'];
            }

            $stmt = $db->prepare("UPDATE pret SET statut = 'annule' WHERE id_pret = :id_pret");
            $stmt->execute([':id_pret' => $id]);

            if ($pret['statut'] === 'en_cours') {
                $restore = $db->prepare(
                    "UPDATE objet_loisir
                     SET quantite = quantite + 1,
                         disponibilite = 'disponible'
                     WHERE id_objet = :id_objet"
                );
                $restore->execute([':id_objet' => $pret['id_objet']]);
            }

            $db->commit();
            return ['success' => true];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function returnLoanById(int $id): array
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'error' => 'loan_not_found'];
            }

            if ($pret['statut'] !== 'en_cours') {
                $db->rollBack();
                return ['success' => false, 'error' => 'invalid_status'];
            }

            $pretUpdate = $db->prepare(
                "UPDATE pret
                 SET statut = 'termine',
                     date_retour_effective = NOW()
                 WHERE id_pret = :id_pret"
            );
            $pretUpdate->execute([':id_pret' => $id]);

            $stockUpdate = $db->prepare(
                "UPDATE objet_loisir
                 SET quantite = quantite + 1,
                     disponibilite = 'disponible'
                 WHERE id_objet = :id_objet"
            );
            $stockUpdate->execute([':id_objet' => $pret['id_objet']]);

            $db->commit();
            return ['success' => true];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function findLoanForUpdate(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM pret WHERE id_pret = :id_pret FOR UPDATE');
        $stmt->execute([':id_pret' => $id]);
        $pret = $stmt->fetch(PDO::FETCH_ASSOC);

        return $pret ?: null;
    }

    private function addStatusLabels(array $prets): array
    {
        foreach ($prets as &$pret) {
            $pret['status_label'] = $this->getStatusLabel($pret['statut'] ?? '');
        }

        unset($pret);

        return $prets;
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'en_attente' => 'Pending',
            'en_cours' => 'Active',
            'termine' => 'Returned',
            'annule' => 'Cancelled',
        ];

        return $labels[$status] ?? $status;
    }

    private function validatePatientName(string $patientName): array
    {
        $errors = [];

        if ($patientName === '') {
            $errors[] = 'Patient name is required.';
        } elseif ($this->textLength($patientName) < 2 || $this->textLength($patientName) > 100) {
            $errors[] = 'Patient name must contain between 2 and 100 characters.';
        } elseif (preg_match("/^[\\p{L}\\s'’.\\-]+$/u", $patientName) !== 1) {
            $errors[] = 'Patient name contains invalid characters.';
        }

        return $errors;
    }

    private function isValidLoanDate(string $datePret): bool
    {
        if ($datePret === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $datePret);

        return $date !== false
            && $date->format('Y-m-d') === $datePret
            && $date <= new DateTimeImmutable(date('Y-m-d'));
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
