<?php
declare(strict_types=1);

/**
 * PretController — Handles leisure-object loan workflow.
 *
 * Back-office methods require admin role.
 * Front-office methods require any authenticated user and pull
 * `id_patient` from `$_SESSION['user_id']` automatically — patients
 * no longer need to type their name.
 *
 * All SQL has been extracted into Pret (model).
 */
class PretController extends BaseController
{
    // ═══════════════════════════════════════════════════════════
    //  BACK-OFFICE (admin only)
    // ═══════════════════════════════════════════════════════════

    public function pendingBack(): void
    {
        $this->requireAdmin();
        $prets  = Pret::addStatusLabels(Pret::findByCondition("p.statut = 'en_attente'"));
        $errors = $this->errorsFromQuery();
        require VIEWS_BACK . '/pret-pending.php';
    }

    public function confirmedBack(): void
    {
        $this->requireAdmin();
        $prets  = Pret::addStatusLabels(Pret::findByCondition("p.statut = 'en_cours'"));
        $errors = $this->errorsFromQuery();
        require VIEWS_BACK . '/pret-confirmed.php';
    }

    public function listBack(): void
    {
        $this->requireAdmin();
        $prets  = Pret::addStatusLabels(Pret::findByCondition('1 = 1'));
        $errors = $this->errorsFromQuery();
        require VIEWS_BACK . '/pret-list.php';
    }

    public function confirmBack(int $id): void
    {
        $this->requireAdmin();

        $result = Pret::confirmById($id);
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
        $this->requireAdmin();

        $result = Pret::cancelById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'cancelled';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'list', $params);
    }

    public function rejectBack(int $id): void
    {
        $this->requireAdmin();

        $motif  = trim($_POST['motif_annulation'] ?? '');
        $result = Pret::rejectById($id, $motif);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'rejected';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'pending', $params);
    }

    public function returnBack(int $id): void
    {
        $this->requireAdmin();

        $result = Pret::returnById($id);
        $params = ['office' => 'back'];

        if ($result['success']) {
            $params['success'] = 'returned';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'confirmed', $params);
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONT-OFFICE (authenticated users)
    // ═══════════════════════════════════════════════════════════

    /**
     * Create a new loan request.
     *
     * The patient ID is automatically taken from the session —
     * no more "type your name" field.
     */
    public function createFront(): void
    {
        $this->requireAuth();

        $errors    = [];
        $patientId = $this->currentUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $objectId      = isset($_POST['id_objet']) ? (int) $_POST['id_objet'] : 0;
            $datePret      = trim($_POST['date_pret'] ?? date('Y-m-d'));
            $motifEmprunt  = trim($_POST['motif_emprunt'] ?? '');

            // Validate loan date
            if (!$this->isValidLoanDate($datePret)) {
                $errors[] = 'Loan date is invalid.';
            }

            // Validate motif length
            if ($this->textLength($motifEmprunt) > 500) {
                $errors[] = 'Loan reason must not exceed 500 characters.';
            }

            if (empty($errors)) {
                $result = Pret::createLoan(
                    $objectId,
                    $patientId,
                    $datePret,
                    $motifEmprunt !== '' ? $motifEmprunt : null
                );

                if ($result['success']) {
                    redirectToRoute('objet', 'list', ['office' => 'front', 'success' => 'requested']);
                }

                $errors[] = $result['message'];
            }

            $objet = ObjetLoisir::findById($objectId);
        } else {
            $objectId = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;
            $objet    = ObjetLoisir::findById($objectId);
        }

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        require VIEWS_FRONT . '/loisirs/pret-create.php';
    }

    /**
     * List the current patient's own loans.
     *
     * No more search-by-name: we use the session-based ID directly.
     */
    public function myLoansFront(): void
    {
        $this->requireAuth();

        $patientId  = $this->currentUserId();
        $nomPatient = $this->currentUserName();
        $errors     = $this->errorsFromQuery();

        $prets = Pret::addStatusLabels(Pret::findByPatientId($patientId));

        require VIEWS_FRONT . '/loisirs/pret-myloans.php';
    }

    /**
     * Cancel a loan (front-office – patient can cancel own pending loans).
     */
    public function cancelFront(int $id): void
    {
        $this->requireAuth();

        $result = Pret::cancelById($id);
        $params = ['office' => 'front'];

        if ($result['success']) {
            $params['success'] = 'cancelled';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'myLoans', $params);
    }

    /**
     * Return a loan (front-office – patient returns their object).
     */
    public function returnFront(int $id): void
    {
        $this->requireAuth();

        $result = Pret::returnById($id);
        $params = ['office' => 'front'];

        if ($result['success']) {
            $params['success'] = 'returned';
        } else {
            $params['error'] = $result['error'];
        }

        redirectToRoute('pret', 'myLoans', $params);
    }

    // ═══════════════════════════════════════════════════════════
    //  PUBLIC STAT ACCESSORS (used by AdminController / dashboard)
    // ═══════════════════════════════════════════════════════════

    public function countLoansByStatus(string $status): int
    {
        return Pret::countByStatus($status);
    }

    public function getRecentPendingLoans(int $limit = 5): array
    {
        return Pret::recentPending($limit);
    }

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════

    private function errorsFromQuery(): array
    {
        $messages = [
            'already_processed' => 'This loan has already been processed.',
            'invalid_status'    => 'The requested status change is not allowed.',
            'loan_not_found'    => 'Loan not found.',
            'object_not_found'  => 'Object not found.',
            'stock_unavailable' => 'The object is out of stock.',
            'update_failed'     => 'The action could not be completed.',
        ];

        $errorKey = $_GET['error'] ?? '';

        return isset($messages[$errorKey]) ? [$messages[$errorKey]] : [];
    }

    private function isValidLoanDate(string $datePret): bool
    {
        if ($datePret === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $datePret);

        return $date !== false
            && $date->format('Y-m-d') === $datePret
            && $date <= new \DateTimeImmutable(date('Y-m-d'));
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
