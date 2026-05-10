<?php

class PretController
{
    public function pendingBack(): void
    {
        $prets  = $this->addStatusLabels($this->getLoansByCondition("p.statut = 'en_attente'"));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_pending.php';
    }

    public function confirmedBack(): void
    {
        $prets  = $this->addStatusLabels($this->getLoansByCondition("p.statut = 'en_cours'"));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_confirmed.php';
    }

    public function listBack(): void
    {
        $prets  = $this->addStatusLabels($this->getLoansByCondition('1 = 1'));
        $errors = $this->errorsFromQuery();
        require BASE_PATH . '/views/back/pret_list.php';
    }

    public function calendarBack(): void
    {
        require BASE_PATH . '/views/back/pret_calendar.php';
    }

    public function calendarEventsJson(): void
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }

        $events = [];
        $rows   = (new StatistiqueController())->getCalendarEvents();

        foreach ($rows as $row) {
            $isEnCours  = $row['statut'] === 'en_cours';
            $events[] = [
                'id'              => (int) $row['id_pret'],
                'title'           => htmlspecialchars($row['nom_objet'] . ' — ' . $row['nom_patient'], ENT_QUOTES, 'UTF-8'),
                'start'           => $row['date_pret'],
                'end'             => $row['date_retour_prevue'] ?? $row['date_pret'],
                'backgroundColor' => $isEnCours ? '#1D9E75' : '#F59E0B',
                'borderColor'     => $isEnCours ? '#0F6E56' : '#D97706',
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'statut'  => $row['statut'],
                    'patient' => $row['nom_patient'],
                    'objet'   => $row['nom_objet'],
                ],
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($events, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function timelineBack(int $id): void
    {
        $pret = $this->db()->prepare(
            "SELECT p.*, o.nom_objet AS objet_nom,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE p.id_pret = :id"
        );
        $pret->execute([':id' => $id]);
        $pret = $pret->fetch(PDO::FETCH_ASSOC);

        if (!$pret) {
            redirectToRoute('pret', 'list', ['office' => 'back', 'error' => 'loan_not_found']);
        }

        $timeline = (new PretHistoryController())->getTimeline($id);
        require BASE_PATH . '/views/back/pret_timeline.php';
    }

    public function confirmBack(int $id): void
    {
        $result = $this->confirmLoanById($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'confirmed' : $result['error'];
        redirectToRoute('pret', 'pending', $params);
    }

    public function cancelBack(int $id): void
    {
        $result = $this->cancelLoanById($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'cancelled' : $result['error'];
        redirectToRoute('pret', 'list', $params);
    }

    public function returnBack(int $id): void
    {
        $result = $this->returnLoanById($id);
        $params = ['office' => 'back'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'returned' : $result['error'];
        redirectToRoute('pret', 'confirmed', $params);
    }

    public function createFront(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $objectId         = isset($_POST['id_objet']) ? (int) $_POST['id_objet'] : 0;
            $patientId        = (int) $_SESSION['user_id'];
            $datePret         = trim($_POST['date_pret'] ?? date('Y-m-d'));
            $motifEmprunt     = trim($_POST['motif_emprunt'] ?? '');
            $dateRetourPrevue = trim($_POST['date_retour_prevue'] ?? '');

            $result = $this->createLoanRequest($objectId, $patientId, $datePret, $motifEmprunt, $dateRetourPrevue);

            if ($result['success']) {
                redirectToRoute('pret', 'myLoans', ['office' => 'front', 'success' => 'requested']);
            }

            $errors[] = $result['message'];
            $objet    = $this->objetController()->findObjectById($objectId);
        } else {
            $objectId = isset($_GET['objet_id']) ? (int) $_GET['objet_id'] : 0;
            $objet    = $this->objetController()->findObjectById($objectId);
        }

        if ($objet === null) {
            redirectToRoute('objet', 'list', ['office' => 'front', 'error' => 'not_found']);
        }

        require BASE_PATH . '/views/front/pret_create.php';
    }

    public function myLoansFront(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $this->checkOverdueAndReminders();

        $errors         = $this->errorsFromQuery();
        $filterStatut   = $_GET['statut'] ?? '';
        $allowedStatuts = ['en_attente', 'en_cours', 'termine', 'annule', 'en_retard'];
        $userId         = (int) $_SESSION['user_id'];

        // Flash messages from session (used by renewLoan)
        if (!empty($_SESSION['pret_flash'])) {
            $flash = $_SESSION['pret_flash'];
            unset($_SESSION['pret_flash']);
        } else {
            $flash = [];
        }

        $condition = 'p.id_patient = :id_patient';
        $params    = [':id_patient' => $userId];

        if (in_array($filterStatut, $allowedStatuts, true)) {
            $condition        .= ' AND p.statut = :statut';
            $params[':statut'] = $filterStatut;
        }

        $prets    = $this->addStatusLabels($this->getLoansByConditionParams($condition, $params));
        $userName = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
        require BASE_PATH . '/views/front/pret_myloans.php';
    }

    public function cancelFront(int $id): void
    {
        $result = $this->cancelLoanById($id);
        $params = ['office' => 'front'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'cancelled' : $result['error'];
        redirectToRoute('pret', 'myLoans', $params);
    }

    public function returnFront(int $id): void
    {
        $result = $this->returnLoanById($id);
        $params = ['office' => 'front'];
        $params[$result['success'] ? 'success' : 'error'] = $result['success'] ? 'returned' : $result['error'];
        redirectToRoute('pret', 'myLoans', $params);
    }

    // ── FEATURE 1: Loan Renewal ───────────────────────────────────────────────

    public function renewFront(int $id): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $result = $this->renewLoanById($id, (int) $_SESSION['user_id']);

        if ($result['success']) {
            $_SESSION['pret_flash'] = ['type' => 'success', 'message' => 'Prêt renouvelé de 3 jours avec succès.'];
        } else {
            $_SESSION['pret_flash'] = ['type' => 'error', 'message' => $result['message']];
        }

        redirectToRoute('pret', 'myLoans', ['office' => 'front']);
    }

    private function renewLoanById(int $id, int $patientId): array
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);

            if ($pret === null) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Prêt introuvable.'];
            }

            // Must belong to the requesting patient
            if ((int) $pret['id_patient'] !== $patientId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Action non autorisée.'];
            }

            if ($pret['statut'] !== 'en_cours') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Seuls les prêts en cours peuvent être renouvelés.'];
            }

            // Condition 1: object must still have stock
            $objStmt = $db->prepare('SELECT quantite FROM objet_loisir WHERE id_objet = :id');
            $objStmt->execute([':id' => $pret['id_objet']]);
            $quantite = (int) $objStmt->fetchColumn();

            if ($quantite <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Renouvellement impossible : l\'objet n\'est plus disponible en stock.'];
            }

            // Condition 2: no pending loan requests for this object
            $pendingStmt = $db->prepare(
                "SELECT COUNT(*) FROM pret
                 WHERE id_objet = :o AND statut = 'en_attente' AND id_pret != :id"
            );
            $pendingStmt->execute([':o' => $pret['id_objet'], ':id' => $id]);

            if ((int) $pendingStmt->fetchColumn() > 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Renouvellement impossible : d\'autres patients attendent cet objet.'];
            }

            // Extend date_retour_prevue by +3 days
            $currentDate = $pret['date_retour_prevue'] ?? date('Y-m-d');
            $newDate     = (new DateTimeImmutable($currentDate))->modify('+3 days')->format('Y-m-d');

            $db->prepare(
                'UPDATE pret SET date_retour_prevue = :new_date WHERE id_pret = :id'
            )->execute([':new_date' => $newDate, ':id' => $id]);

            // Log the renewal in history as a special entry
            (new PretHistoryController())->log($id, 'en_cours', 'en_cours_renouvele', $patientId);

            $db->commit();
            return ['success' => true];

        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'message' => 'Une erreur est survenue lors du renouvellement.'];
        }
    }

    // ── FEATURE: Overdue checker + 24h reminder (cron simulation) ─────────────

    public function checkOverdueAndReminders(): void
    {
        // Rate-limit: run at most once per hour per session
        $lastRun = $_SESSION['overdue_check_ts'] ?? 0;
        if ((time() - $lastRun) < 3600) {
            return;
        }
        $_SESSION['overdue_check_ts'] = time();

        $db           = $this->db();
        $notification = new NotificationController();
        $today        = date('Y-m-d');
        $tomorrow     = date('Y-m-d', strtotime('+1 day'));

        // 1. Mark overdue loans as 'en_retard' + notify patient
        $overdueStmt = $db->prepare(
            "SELECT p.id_pret, p.id_patient,
                    o.nom_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet  = o.id_objet
             INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.statut = 'en_cours'
               AND p.date_retour_prevue < :today"
        );
        $overdueStmt->execute([':today' => $today]);

        foreach ($overdueStmt->fetchAll(PDO::FETCH_ASSOC) as $pret) {
            $db->prepare("UPDATE pret SET statut = 'en_retard' WHERE id_pret = :id")
               ->execute([':id' => $pret['id_pret']]);

            (new PretHistoryController())->log((int) $pret['id_pret'], 'en_cours', 'en_retard', null);

            $notification->create(
                (int) $pret['id_patient'],
                '⚠️ Votre prêt de "' . $pret['nom_objet'] . '" est en retard. Merci de le retourner dès que possible.'
            );
        }

        // 2. Send 24h reminder for loans due tomorrow
        $reminderStmt = $db->prepare(
            "SELECT p.id_pret, p.id_patient, p.date_retour_prevue,
                    o.nom_objet,
                    u.email AS patient_email,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet  = o.id_objet
             INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
             WHERE p.statut IN ('en_cours', 'en_retard')
               AND p.date_retour_prevue = :tomorrow"
        );
        $reminderStmt->execute([':tomorrow' => $tomorrow]);

        foreach ($reminderStmt->fetchAll(PDO::FETCH_ASSOC) as $pret) {
            $notification->create(
                (int) $pret['id_patient'],
                '🔔 Rappel : votre prêt de "' . $pret['nom_objet'] . '" est dû demain ('
                . date('d/m/Y', strtotime($pret['date_retour_prevue'])) . ').'
            );

            if (!empty($pret['patient_email'])) {
                Mailer::sendDueTomorrowReminder(
                    $pret['patient_email'],
                    $pret['nom_patient'],
                    $pret['nom_objet'],
                    $pret['date_retour_prevue']
                );
            }
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function errorsFromQuery(): array
    {
        $messages = [
            'already_processed' => 'Ce prêt a déjà été traité.',
            'invalid_status'    => 'Ce changement de statut n\'est pas autorisé.',
            'loan_not_found'    => 'Prêt introuvable.',
            'object_not_found'  => 'Objet introuvable.',
            'stock_unavailable' => 'L\'objet n\'est plus disponible en stock.',
            'update_failed'     => 'L\'action n\'a pas pu être effectuée.',
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
            "SELECT p.*, o.nom_objet,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             LEFT JOIN objet_loisir o ON p.id_objet = o.id_objet
             LEFT JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE p.statut = :statut
             ORDER BY p.date_pret DESC
             LIMIT " . max(1, $limit)
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

    private function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private function getLoansByCondition(string $condition): array
    {
        $stmt = $this->db()->query(
            "SELECT p.*,
                    o.nom_objet AS objet_nom,
                    o.type_objet AS objet_type,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE {$condition}
             ORDER BY p.date_pret DESC, p.id_pret DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLoansByConditionParams(string $condition, array $params): array
    {
        $stmt = $this->db()->prepare(
            "SELECT p.*,
                    o.nom_objet AS objet_nom,
                    o.type_objet AS objet_type,
                    CONCAT(u.prenom, ' ', u.nom) AS nom_patient
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN utilisateur u ON p.id_patient = u.id_utilisateur
             WHERE {$condition}
             ORDER BY p.date_pret DESC, p.id_pret DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createLoanRequest(int $objectId, int $patientId, string $datePret, string $motifEmprunt = '', string $dateRetourPrevue = ''): array
    {
        if ($objectId <= 0) {
            return ['success' => false, 'message' => 'Sélection d\'objet invalide.'];
        }
        if ($patientId <= 0) {
            return ['success' => false, 'message' => 'Utilisateur non identifié.'];
        }
        if ($motifEmprunt === '') {
            return ['success' => false, 'message' => 'Le motif d\'emprunt est obligatoire.'];
        }
        if (mb_strlen($motifEmprunt) > 500) {
            return ['success' => false, 'message' => 'Le motif ne doit pas dépasser 500 caractères.'];
        }

        // Quota check
        $quotaStmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM pret WHERE id_patient = :p AND statut IN ('en_attente', 'en_cours')"
        );
        $quotaStmt->execute([':p' => $patientId]);
        if ((int) $quotaStmt->fetchColumn() >= 2) {
            return ['success' => false, 'message' => 'Quota atteint : Vous ne pouvez pas avoir plus de 2 prêts actifs.', 'quota' => true];
        }

        $loanDate = ($datePret !== '' && $this->isValidLoanDate($datePret)) ? $datePret : date('Y-m-d');

        if ($dateRetourPrevue !== '') {
            $retourDate = DateTimeImmutable::createFromFormat('Y-m-d', $dateRetourPrevue);
            if ($retourDate === false || $retourDate->format('Y-m-d') !== $dateRetourPrevue) {
                return ['success' => false, 'message' => 'La date de retour prévue est invalide.'];
            }
            if ($retourDate <= new DateTimeImmutable($loanDate)) {
                return ['success' => false, 'message' => 'La date de retour doit être postérieure à la date de prêt.'];
            }
            $returnDate = $dateRetourPrevue;
        } else {
            $returnDate = (new DateTimeImmutable($loanDate))->modify('+7 days')->format('Y-m-d');
        }

        $objet = $this->objetController()->findObjectById($objectId);
        if ($objet === null) {
            return ['success' => false, 'message' => 'Objet introuvable.'];
        }
        if ((int) $objet['quantite'] <= 0 || $objet['disponibilite'] !== 'disponible') {
            return ['success' => false, 'message' => 'Cet objet est actuellement indisponible.'];
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO pret (id_objet, id_patient, motif_emprunt, date_pret, date_retour_prevue, statut)
             VALUES (:id_objet, :id_patient, :motif_emprunt, :date_pret, :date_retour_prevue, :statut)'
        );
        $success = $stmt->execute([
            ':id_objet'           => $objectId,
            ':id_patient'         => $patientId,
            ':motif_emprunt'      => $motifEmprunt,
            ':date_pret'          => $loanDate,
            ':date_retour_prevue' => $returnDate,
            ':statut'             => 'en_attente',
        ]);

        if ($success) {
            $newId = (int) $this->db()->lastInsertId();
            (new PretHistoryController())->log($newId, '', 'en_attente', $patientId);
        }

        return [
            'success' => $success,
            'message' => $success ? 'Demande de prêt créée.' : 'Impossible de créer la demande de prêt.',
        ];
    }

    private function confirmLoanById(int $id): array
    {
        $db = $this->db();
        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) { $db->rollBack(); return ['success' => false, 'error' => 'loan_not_found']; }
            if ($pret['statut'] !== 'en_attente') { $db->rollBack(); return ['success' => false, 'error' => 'already_processed']; }

            $ancienStatut = $pret['statut'];

            $stockUpdate = $db->prepare(
                "UPDATE objet_loisir
                 SET quantite = quantite - 1,
                     disponibilite = CASE WHEN quantite - 1 > 0 THEN 'disponible' ELSE 'indisponible' END
                 WHERE id_objet = :id_objet AND quantite > 0"
            );
            $stockUpdate->execute([':id_objet' => $pret['id_objet']]);
            if ($stockUpdate->rowCount() !== 1) { $db->rollBack(); return ['success' => false, 'error' => 'stock_unavailable']; }

            $db->prepare("UPDATE pret SET statut = 'en_cours' WHERE id_pret = :id")->execute([':id' => $id]);

            (new PretHistoryController())->log($id, $ancienStatut, 'en_cours', $this->currentUserId());

            $db->commit();

            // Notification + email to patient
            $patientRow = $db->prepare(
                "SELECT p.id_patient, p.date_pret, p.date_retour_prevue,
                        o.nom_objet,
                        u.email AS patient_email,
                        CONCAT(u.prenom, ' ', u.nom) AS nom_patient
                 FROM pret p
                 INNER JOIN objet_loisir o ON p.id_objet  = o.id_objet
                 INNER JOIN utilisateur  u ON p.id_patient = u.id_utilisateur
                 WHERE p.id_pret = :id"
            );
            $patientRow->execute([':id' => $id]);
            $info = $patientRow->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                (new NotificationController())->create(
                    (int) $info['id_patient'],
                    '✅ Votre demande de prêt pour "' . $info['nom_objet'] . '" a été confirmée. Vous pouvez récupérer l\'objet.'
                );
                if (!empty($info['patient_email'])) {
                    Mailer::sendLoanConfirmation(
                        $info['patient_email'],
                        $info['nom_patient'],
                        $id,
                        $info['nom_objet'],
                        $info['date_pret'],
                        $info['date_retour_prevue']
                    );
                }
            }

            return ['success' => true];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function cancelLoanById(int $id): array
    {
        $db = $this->db();
        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) { $db->rollBack(); return ['success' => false, 'error' => 'loan_not_found']; }
            if (!in_array($pret['statut'], ['en_attente', 'en_cours'], true)) { $db->rollBack(); return ['success' => false, 'error' => 'invalid_status']; }

            $ancienStatut = $pret['statut'];

            $db->prepare("UPDATE pret SET statut = 'annule' WHERE id_pret = :id")->execute([':id' => $id]);

            if ($pret['statut'] === 'en_cours') {
                $db->prepare("UPDATE objet_loisir SET quantite = quantite + 1, disponibilite = 'disponible' WHERE id_objet = :id")
                   ->execute([':id' => $pret['id_objet']]);
            }

            (new PretHistoryController())->log($id, $ancienStatut, 'annule', $this->currentUserId());

            $db->commit();

            // Notify patient of cancellation
            $infoStmt = $db->prepare(
                "SELECT p.id_patient, o.nom_objet
                 FROM pret p
                 INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
                 WHERE p.id_pret = :id"
            );
            $infoStmt->execute([':id' => $id]);
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                (new NotificationController())->create(
                    (int) $info['id_patient'],
                    '❌ Votre prêt de "' . $info['nom_objet'] . '" a été annulé.'
                );
            }

            return ['success' => true];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function returnLoanById(int $id): array
    {
        $db = $this->db();
        try {
            $db->beginTransaction();

            $pret = $this->findLoanForUpdate($id);
            if ($pret === null) { $db->rollBack(); return ['success' => false, 'error' => 'loan_not_found']; }
            if (!in_array($pret['statut'], ['en_cours', 'en_retard'], true)) { $db->rollBack(); return ['success' => false, 'error' => 'invalid_status']; }

            $ancienStatut = $pret['statut'];

            $db->prepare("UPDATE pret SET statut = 'termine', date_retour_effective = NOW() WHERE id_pret = :id")->execute([':id' => $id]);

            (new PretHistoryController())->log($id, $ancienStatut, 'termine', $this->currentUserId());

            // Check reservation queue
            $reservation = new ReservationController();
            $next        = $reservation->getNextInQueue((int) $pret['id_objet']);

            if ($next !== null) {
                $db->prepare(
                    "UPDATE objet_loisir SET quantite = quantite + 1, disponibilite = 'reserve' WHERE id_objet = :id"
                )->execute([':id' => $pret['id_objet']]);

                $db->prepare(
                    "INSERT INTO pret (id_objet, id_patient, motif_emprunt, date_pret, date_retour_prevue, statut)
                     VALUES (:o, :p, 'Réservation automatique', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'en_attente')"
                )->execute([':o' => $pret['id_objet'], ':p' => $next['id_patient']]);

                $newPretId = (int) $db->lastInsertId();
                (new PretHistoryController())->log($newPretId, '', 'en_attente', null);

                $reservation->markFulfilled((int) $next['id_reservation']);

                $db->prepare(
                    "UPDATE objet_loisir SET quantite = quantite - 1,
                     disponibilite = CASE WHEN quantite - 1 > 0 THEN 'disponible' ELSE 'indisponible' END
                     WHERE id_objet = :id AND quantite > 0"
                )->execute([':id' => $pret['id_objet']]);
            } else {
                $db->prepare(
                    "UPDATE objet_loisir SET quantite = quantite + 1, disponibilite = 'disponible' WHERE id_objet = :id"
                )->execute([':id' => $pret['id_objet']]);
            }

            $db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'error' => 'update_failed'];
        }
    }

    private function findLoanForUpdate(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM pret WHERE id_pret = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
        return [
            'en_attente'         => 'En attente',
            'en_cours'           => 'En cours',
            'en_cours_renouvele' => 'En cours (renouvelé)',
            'termine'            => 'Terminé',
            'annule'             => 'Annulé',
            'en_retard'          => 'En retard',
        ][$status] ?? $status;
    }

    private function isValidLoanDate(string $datePret): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $datePret);
        return $date !== false
            && $date->format('Y-m-d') === $datePret
            && $date <= new DateTimeImmutable(date('Y-m-d'));
    }
}
