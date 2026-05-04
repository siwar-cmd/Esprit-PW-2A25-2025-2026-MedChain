<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Pret.php';

/**
 * AdminController — Back-office: user management + platform dashboard.
 *
 * Every public method calls $this->requireAdmin() first.
 * All DB queries are in the Utilisateur/Pret models — no raw SQL here.
 */
class AdminController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ──────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ──────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $this->requireAdmin();

        $stats        = Utilisateur::getStats($this->db);
        $recentUsers  = Utilisateur::getRecent(5, $this->db);
        $pendingUsers = Utilisateur::getPending($this->db);
        $loanStats    = [
            'en_attente' => Pret::countByStatus('en_attente'),
            'en_cours'   => Pret::countByStatus('en_cours'),
            'en_retard'  => Pret::countByStatus('en_retard'),
        ];
        $recentLoans  = Pret::recentPending(5);

        $this->view(VIEWS_BACK . '/admin-dashboard.php', compact(
            'stats', 'recentUsers', 'pendingUsers', 'loanStats', 'recentLoans'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    //  USER MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    public function listUsers(): void
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'role'   => trim($_GET['role']   ?? ''),
            'statut' => trim($_GET['statut'] ?? ''),
        ];

        $users = Utilisateur::getAll($filters, $this->db);

        $this->view(VIEWS_BACK . '/admin-users.php', compact('users', 'filters'));
    }

    public function createUser(): void
    {
        $this->requireAdmin();

        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $_POST;
            $errors = $this->validateUserForm($_POST, null);

            if (empty($errors)) {
                $result = Utilisateur::adminCreate($_POST, $this->db);

                if ($result['success']) {
                    redirectToRoute('admin', 'users', ['office' => 'back', 'success' => 'created']);
                }

                $errors['general'] = $result['message'];
            }
        }

        $this->view(VIEWS_BACK . '/admin-create-user.php', compact('errors', 'old'));
    }

    public function editUser(int $id): void
    {
        $this->requireAdmin();

        $user   = Utilisateur::findById($id, $this->db);
        $errors = [];

        if ($user === null) {
            redirectToRoute('admin', 'users', ['office' => 'back', 'error' => 'not_found']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validateUserForm($_POST, $id);

            if (empty($errors)) {
                $result = Utilisateur::adminUpdate($id, $_POST, $this->db);

                if ($result['success']) {
                    redirectToRoute('admin', 'users', ['office' => 'back', 'success' => 'updated']);
                }

                $errors['general'] = $result['message'];
            }
        }

        $this->view(VIEWS_BACK . '/admin-edit.php', compact('user', 'errors'));
    }

    public function deleteUser(int $id): void
    {
        $this->requireAdmin();

        // Cannot delete yourself
        if ($id === $this->currentUserId()) {
            redirectToRoute('admin', 'users', ['office' => 'back', 'error' => 'cannot_self_delete']);
        }

        $result = Utilisateur::adminDelete($id, $this->db);
        $param  = $result['success'] ? ['success' => 'deleted'] : ['error' => 'delete_failed'];
        redirectToRoute('admin', 'users', array_merge(['office' => 'back'], $param));
    }

    public function activateUser(int $id): void
    {
        $this->requireAdmin();
        Utilisateur::setStatut($id, 'actif', $this->db);
        redirectToRoute('admin', 'users', ['office' => 'back', 'success' => 'activated']);
    }

    public function deactivateUser(int $id): void
    {
        $this->requireAdmin();

        if ($id === $this->currentUserId()) {
            redirectToRoute('admin', 'users', ['office' => 'back', 'error' => 'cannot_self_deactivate']);
        }

        Utilisateur::setStatut($id, 'inactif', $this->db);
        redirectToRoute('admin', 'users', ['office' => 'back', 'success' => 'deactivated']);
    }

    // ──────────────────────────────────────────────────────────────
    //  STATISTICS
    // ──────────────────────────────────────────────────────────────

    public function statistics(): void
    {
        $this->requireAdmin();

        $stats = Utilisateur::getStats($this->db);
        $this->view(VIEWS_BACK . '/admin-reports-statistics.php', compact('stats'));
    }

    // ──────────────────────────────────────────────────────────────
    //  EXPORT
    // ──────────────────────────────────────────────────────────────

    public function exportUsersToExcel(): void
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'role'   => trim($_GET['role']   ?? ''),
            'statut' => trim($_GET['statut'] ?? ''),
        ];

        $users = Utilisateur::getAll($filters, $this->db);

        if (ob_get_level()) ob_end_clean();

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_H-i') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<html><head><meta charset="utf-8"><style>th{background:#eef2ff;padding:6px;border:1px solid #c7d2fe}td{border:1px solid #ddd;padding:5px}</style></head><body>';
        echo '<table border="1"><tr><td colspan="7" style="font-size:18px;font-weight:bold;text-align:center">MedChain — Liste des Utilisateurs</td></tr>';
        echo '<tr><td colspan="7">Généré le : ' . date('d/m/Y H:i') . '</td></tr>';
        echo '<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Inscrit le</th></tr>';

        foreach ($users as $u) {
            echo '<tr>';
            echo '<td>' . (int) $u['id_utilisateur'] . '</td>';
            echo '<td>' . htmlspecialchars($u['nom'],    ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($u['prenom'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($u['email'],  ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($u['role'],   ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($u['statut'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($u['date_inscription'])) . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function validateUserForm(array $data, ?int $editId): array
    {
        $errors = [];

        if (empty(trim($data['nom'] ?? ''))) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }

        if (empty(trim($data['prenom'] ?? ''))) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        } elseif (Utilisateur::emailExists($data['email'], $this->db, $editId)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }

        // Password only required on create
        if ($editId === null && (empty($data['mot_de_passe']) || strlen($data['mot_de_passe']) < 8)) {
            $errors['mot_de_passe'] = 'Mot de passe d\'au moins 8 caractères requis.';
        }

        $allowedRoles = ['admin', 'patient', 'medecin'];
        if (!in_array($data['role'] ?? '', $allowedRoles, true)) {
            $errors['role'] = 'Rôle invalide.';
        }

        return $errors;
    }
}
