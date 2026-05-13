<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
$filters = [];
if ($search) $filters['search'] = $search;
if ($role_filter) $filters['role'] = $role_filter;
if ($statut_filter) $filters['statut'] = $statut_filter;

$usersResult = $adminController->getAllUsers($filters);
$users = $usersResult['success'] ? $usersResult['users'] : [];

$items_per_page = 5;
$total_items = count($users);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$paginated_users = array_slice($users, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Utilisateurs – Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="components/admin.css">
<style>
.filter-bar{padding:16px 24px;background:#F8FAFC;border-bottom:1px solid var(--gray-200);display:flex;gap:10px;}
.badge{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;}
.actif{background:#DCFCE7;color:#16A34A;}
.inactif{background:#FEE2E2;color:#DC2626;}
.search-input{padding:8px 12px;border:1px solid var(--gray-200);border-radius:8px;font-size:13px;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Gestion des Utilisateurs</h1>
            <a href="admin-create-user.php" class="btn-primary">Nouveau</a>
        </div>
        <div class="card">
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:10px;">
                    <input type="text" name="search" class="search-input" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    <select name="role" class="search-input"><option value="">Tous les rôles</option><option value="admin">Admin</option><option value="medecin">Médecin</option><option value="patient">Patient</option></select>
                    <button type="submit" class="btn-primary">Filtrer</button>
                </form>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>ID</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($paginated_users as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst($user['role']) ?></td>
                            <td><span class="badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
