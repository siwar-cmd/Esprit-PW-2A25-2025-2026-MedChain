<?php
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../config.php';

class AdminController {
    private $pdo;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        
        if (!$this->isAdmin()) {
            header('Location: ../../frontoffice/auth/sign-in.php');
            exit;
        }
        
        $this->pdo = config::getConnexion();
    }

    public function dashboard(): array {
        $stats = $this->getStats();
        $recentUsers = $this->getRecentUsers(5);
        $pendingUsers = $this->getPendingUsers();
        
        return [
            "success" => true,
            "stats" => $stats,
            "recentUsers" => $recentUsers,
            "pendingUsers" => $pendingUsers
        ];
    }

    private function getStats(): array {
        try {
            $req = $this->pdo->query('SELECT COUNT(*) as total FROM utilisateur');
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];
            
            $req = $this->pdo->query(
                "SELECT COUNT(*) as new_this_month FROM utilisateur 
                 WHERE MONTH(date_inscription) = MONTH(CURRENT_DATE()) 
                 AND YEAR(date_inscription) = YEAR(CURRENT_DATE())"
            );
            $newThisMonth = $req->fetch(PDO::FETCH_ASSOC)['new_this_month'];
            
            $req = $this->pdo->query(
                'SELECT role, COUNT(*) as count FROM utilisateur GROUP BY role'
            );
            $byRole = $req->fetchAll(PDO::FETCH_ASSOC);
            
            $req = $this->pdo->query(
                'SELECT statut, COUNT(*) as count FROM utilisateur GROUP BY statut'
            );
            $byStatus = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total' => $total,
                'new_this_month' => $newThisMonth,
                'by_role' => $byRole,
                'by_status' => $byStatus
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'new_this_month' => 0, 'by_role' => [], 'by_status' => []];
        }
    }

    private function getRecentUsers($limit = 5): array {
        try {
            $req = $this->pdo->prepare(
                "SELECT id_utilisateur, nom, prenom, email, date_inscription, role, statut 
                 FROM utilisateur 
                 ORDER BY date_inscription DESC 
                 LIMIT ?"
            );
            $req->bindValue(1, $limit, PDO::PARAM_INT);
            $req->execute();
            
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getPendingUsers(): array {
        try {
            $req = $this->pdo->prepare(
                'SELECT * FROM utilisateur 
                 WHERE statut = "en_attente" 
                 ORDER BY date_inscription DESC'
            );
            $req->execute();
            
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllUsers($filters = []): array {
        try {
            $sql = 'SELECT * FROM utilisateur WHERE 1=1';
            $params = [];
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['role'])) {
                $sql .= ' AND role = ?';
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['statut'])) {
                $sql .= ' AND statut = ?';
                $params[] = $filters['statut'];
            }
            
            $sql .= ' ORDER BY date_inscription DESC';
            
            $req = $this->pdo->prepare($sql);
            $req->execute($params);
            
            $users = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "users" => $users,
                "count" => count($users)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getUserById($id): ?array {
        try {
            $req = $this->pdo->prepare('SELECT * FROM utilisateur WHERE id_utilisateur = ?');
            $req->execute([$id]);
            $user = $req->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (Exception $e) {
            error_log("Erreur getUserById: " . $e->getMessage());
            return null;
        }
    }

    public function createUser($data): array {
        try {
            $required = ['nom', 'prenom', 'email', 'mot_de_passe'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ["success" => false, "message" => "Le champ $field est obligatoire"];
                }
            }
            
            $check = $this->pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = ?');
            $check->execute([$data['email']]);
            if ($check->fetch()) {
                return ["success" => false, "message" => "Cet email est déjà utilisé"];
            }
            
            $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            
            $sql = 'INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, statut, date_inscription) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())';
            $req = $this->pdo->prepare($sql);
            $success = $req->execute([
                htmlspecialchars($data['nom']),
                htmlspecialchars($data['prenom']),
                htmlspecialchars($data['email']),
                $hashedPassword,
                $data['role'] ?? 'user',
                $data['statut'] ?? 'actif'
            ]);
            
            if ($success) {
                return ["success" => true, "message" => "Utilisateur créé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la création"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateUser($userId, $data): array {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ["success" => false, "message" => "Utilisateur non trouvé"];
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['nom', 'prenom', 'email', 'role', 'statut'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = htmlspecialchars($data[$field]);
                }
            }
            
            if (empty($updates)) {
                return ["success" => false, "message" => "Aucune donnée à mettre à jour"];
            }
            
            $params[] = $userId;
            $sql = "UPDATE utilisateur SET " . implode(', ', $updates) . " WHERE id_utilisateur = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Utilisateur mis à jour avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteUser($userId): array {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ["success" => false, "message" => "Utilisateur non trouvé"];
            }
            
            if ($userId == $_SESSION['user_id']) {
                return ["success" => false, "message" => "Vous ne pouvez pas supprimer votre propre compte"];
            }
            
            if ($user['role'] === 'admin') {
                $adminCount = $this->pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'admin'")->fetchColumn();
                if ($adminCount <= 1) {
                    return ["success" => false, "message" => "Impossible de supprimer le dernier administrateur"];
                }
            }
            
            // Supprimer la photo de profil si elle existe
            if (!empty($user['photo_profil'])) {
                $photoPath = __DIR__ . '/../../uploads/profiles/' . $user['photo_profil'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            $req = $this->pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
            $success = $req->execute([$userId]);
            
            if ($success) {
                return ["success" => true, "message" => "Utilisateur supprimé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la suppression"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function activateUser($userId): array {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ["success" => false, "message" => "Utilisateur non trouvé"];
            }
            
            $req = $this->pdo->prepare("UPDATE utilisateur SET statut = 'actif' WHERE id_utilisateur = ?");
            $success = $req->execute([$userId]);
            
            if ($success) {
                return ["success" => true, "message" => "Utilisateur activé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de l'activation"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deactivateUser($userId): array {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ["success" => false, "message" => "Utilisateur non trouvé"];
            }
            
            if ($userId == $_SESSION['user_id']) {
                return ["success" => false, "message" => "Vous ne pouvez pas désactiver votre propre compte"];
            }
            
            $req = $this->pdo->prepare("UPDATE utilisateur SET statut = 'inactif' WHERE id_utilisateur = ?");
            $success = $req->execute([$userId]);
            
            if ($success) {
                return ["success" => true, "message" => "Utilisateur désactivé avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la désactivation"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function searchUsers($search): array {
        try {
            $req = $this->pdo->prepare(
                'SELECT * FROM utilisateur 
                 WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? 
                 ORDER BY nom, prenom'
            );
            $searchTerm = '%' . $search . '%';
            $req->execute([$searchTerm, $searchTerm, $searchTerm]);
            
            $users = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "users" => $users,
                "count" => count($users)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStatsForDashboard(): array {
        return $this->getStats();
    }

    public function exportUsersToExcel($filters = []): void {
        try {
            $result = $this->getAllUsers($filters);
            
            if (!$result['success']) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
            
            $users = $result['users'];
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_H-i') . '.xls"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            echo '<html><head><meta charset="utf-8"><style>
                td { border: 1px solid #ddd; padding: 5px; }
                th { border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2; }
                .title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 20px; }
            </style></head><body>';
            
            echo '<table border="1">';
            echo '<tr><td colspan="7" class="title">Liste des Utilisateurs</td></tr>';
            echo '<tr><td colspan="7">Généré le : ' . date('d/m/Y H:i') . '</td></tr>';
            echo '<tr><td colspan="7"></td></tr>';
            
            echo '<tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Date d\'inscription</th>
                  </tr>';
            
            foreach ($users as $user) {
                echo '<tr>
                        <td>' . $user['id_utilisateur'] . '</td>
                        <td>' . htmlspecialchars($user['nom']) . '</td>
                        <td>' . htmlspecialchars($user['prenom']) . '</td>
                        <td>' . htmlspecialchars($user['email']) . '</td>
                        <td>' . $user['role'] . '</td>
                        <td>' . $user['statut'] . '</td>
                        <td>' . date('d/m/Y', strtotime($user['date_inscription'])) . '</td>
                      </tr>';
            }
            
            echo '<tr><td colspan="6" style="font-weight: bold; text-align: right;">Total d\'utilisateurs :</td>
                  <td style="font-weight: bold;">' . count($users) . '</td></tr>';
            
            echo '</table></body></html>';
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(["success" => false, "message" => "Erreur lors de l'export : " . $e->getMessage()]);
            exit;
        }
    }

    private function isAdmin(): bool {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>