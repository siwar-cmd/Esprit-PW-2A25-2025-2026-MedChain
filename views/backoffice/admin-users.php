<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$usersResult = $adminController->getAllUsers();
$allUsers = $usersResult['success'] ? $usersResult['users'] : [];

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$statut_filter = $_GET['statut'] ?? '';

$users = $allUsers;

if ($search || $role_filter || $statut_filter) {
    $users = array_filter($allUsers, function($user) use ($search, $role_filter, $statut_filter) {
        $match_search = true;
        $match_role = true;
        $match_statut = true;

        if ($search) {
            $search_term = strtolower(trim($search));
            $nom = strtolower($user['nom'] ?? '');
            $prenom = strtolower($user['prenom'] ?? '');
            $email = strtolower($user['email'] ?? '');
            $match_search = strpos($nom, $search_term) !== false ||
                           strpos($prenom, $search_term) !== false ||
                           strpos($email, $search_term) !== false;
        }
        if ($role_filter) $match_role = ($user['role'] ?? '') === $role_filter;
        if ($statut_filter) $match_statut = ($user['statut'] ?? '') === $statut_filter;
        
        return $match_search && $match_role && $match_statut;
    });
    $users = array_values($users);
}

function escape_data($data) { return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --navy: #1E3A52;
            --gray-500: #6B7280;
            --gray-200: #E5E7EB;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.10);
            --shadow-green: 0 8px 30px rgba(29,158,117,.22);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -120px;
            right: -120px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.10) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -80px;
            left: -80px;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.07) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 2; }

        .card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(29,158,117,.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-green); }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
        }
        .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-green); }
        .logo-icon i { font-size: 24px; color: white; }
        .logo-text { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; color: var(--navy); }
        .logo-text span { color: var(--green); }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px; border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-card h3 { font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .stat-card p { font-size: 13px; color: var(--gray-500); }

        .filter-form {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(29,158,117,.15);
        }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
        .form-group { margin-bottom: 0; }
        label { display: block; margin-bottom: 8px; color: var(--navy); font-weight: 600; font-size: 13px; }
        .form-control { width: 100%; padding: 10px 12px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); font-size: 14px; transition: all 0.3s; }
        .form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }

        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .table td { padding: 16px; border-bottom: 1px solid var(--gray-200); vertical-align: middle; }
        .table tr:hover { background: #F8FAFC; }

        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-badge.admin { background: #FEF2F2; color: #EF4444; }
        .role-badge.user { background: #F0FDF4; color: #22C55E; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.actif { background: #F0FDF4; color: #22C55E; }
        .status-badge.inactif { background: #FEF2F2; color: #EF4444; }

        .btn { padding: 8px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 3px 12px rgba(29,158,117,.30); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(29,158,117,.40); }
        .btn-outline { background: transparent; border: 2px solid var(--gray-200); color: var(--gray-700); }
        .btn-outline:hover { border-color: var(--green); color: var(--green); }
        .btn-danger { background: #EF4444; color: white; }
        .btn-danger:hover { background: #DC2626; transform: translateY(-2px); }

        .actions { display: flex; gap: 12px; margin-top: 32px; flex-wrap: wrap; }

        @media (max-width: 768px) {
            .card { padding: 20px; }
            .filter-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
        @media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <a href="admin-dashboard.php" class="logo">
            <div class="logo-icon"><i class="bi bi-plus-square-fill"></i></div>
            <div class="logo-text">Med<span>Chain</span></div>
        </a>
        
        <div class="header">
            <h1><i class="bi bi-people-fill"></i> Gestion des utilisateurs</h1>
            <a href="admin-create-user.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?= htmlspecialchars($error_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card"><h3><?= count($allUsers) ?></h3><p>Total utilisateurs</p></div>
            <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['statut'] === 'actif')) ?></h3><p>Utilisateurs actifs</p></div>
            <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['statut'] === 'inactif')) ?></h3><p>Utilisateurs inactifs</p></div>
            <div class="stat-card"><h3><?= count(array_filter($allUsers, fn($u) => $u['role'] === 'admin')) ?></h3><p>Administrateurs</p></div>
        </div>
        
        <div class="filter-form">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group"><label><i class="bi bi-search"></i> Recherche</label><input type="text" name="search" class="form-control" value="<?= escape_data($search) ?>" placeholder="Nom, prénom ou email"></div>
                    <div class="form-group"><label><i class="bi bi-person-badge"></i> Rôle</label><select name="role" class="form-control"><option value="">Tous</option><option value="patient" <?= $role_filter === 'patient' ? 'selected' : '' ?>>Patient</option><option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrateur</option></select></div>
                    <div class="form-group"><label><i class="bi bi-toggle-on"></i> Statut</label><select name="statut" class="form-control"><option value="">Tous</option><option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actif</option><option value="inactif" <?= $statut_filter === 'inactif' ? 'selected' : '' ?>>Inactif</option></select></div>
                    <div class="form-group"><button type="submit" class="btn btn-primary" style="width: 100%;"><i class="bi bi-funnel-fill"></i> Filtrer</button></div>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Date inscription</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" style="text-align: center;">Aucun utilisateur trouvé</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php $is_current_user = ($user['id_utilisateur'] == $_SESSION['user_id']); ?>
                            <tr>
                                <td>#<?= $user['id_utilisateur'] ?></td>
                                <td><strong><?= escape_data($user['nom']) ?></strong></td>
                                <td><?= escape_data($user['prenom']) ?></td>
                                <td><?= escape_data($user['email']) ?></td>
                                <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                                <td><span class="status-badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                                <td class="actions">
                                    <a href="admin-edit.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-primary"><i class="bi bi-pencil-fill"></i> Modifier</a>
                                    <?php if (!$is_current_user): ?>
                                        <form method="POST" action="admin-delete.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')"><input type="hidden" name="user_id" value="<?= $user['id_utilisateur'] ?>"><button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill"></i> Supprimer</button></form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="actions">
            <a href="rendezvous/admin-index.php" class="btn btn-primary"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="ficherdv/admin-index.php" class="btn btn-primary"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            <a href="admin-reports-statistics.php" class="btn btn-primary"><i class="bi bi-graph-up"></i> Statistiques</a>
            <a href="../frontoffice/home/index.php" class="btn btn-outline"><i class="bi bi-house-door-fill"></i> Retour au site</a>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.alert').remove());
    });
    
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    
    document.querySelectorAll('a[href*="logout"], form button[type="submit"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.href && this.href.includes('logout') && !confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) e.preventDefault();
        });
    });
</script>
</body>
</html>