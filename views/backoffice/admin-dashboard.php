<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$dashboardData = $adminController->dashboard();

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stats = $dashboardData['stats'] ?? [];
$recentUsers = $dashboardData['recentUsers'] ?? [];

$totalUsers = $stats['total'] ?? 0;
$newThisMonth = $stats['new_this_month'] ?? 0;
$roleStats = $stats['by_role'] ?? [];
$statusStats = $stats['by_status'] ?? [];

$activeUsers = 0;
$inactiveUsers = 0;

foreach ($statusStats as $status) {
    if ($status['statut'] === 'actif') $activeUsers = $status['count'];
    if ($status['statut'] === 'inactif') $inactiveUsers = $status['count'];
}

function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $interval = $now->diff($date);
    
    if ($interval->days == 0) {
        return "Aujourd'hui à " . $date->format('H:i');
    } elseif ($interval->days == 1) {
        return "Hier à " . $date->format('H:i');
    } elseif ($interval->days < 7) {
        return "Il y a " . $interval->days . " jours";
    } else {
        return $date->format('d/m/Y');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - MedChain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --green: #1D9E75;
            --green-dark: #0F6E56;
            --green-deep: #094D3C;
            --green-light: #E8F7F2;
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
            position: relative;
            overflow-x: hidden;
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

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: var(--green); }

        .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
        .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
        .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

        .dashboard-main { padding: 32px 40px; overflow-y: auto; }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .dashboard-header h1 { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); }
        .dashboard-header p { color: var(--gray-500); font-size: 14px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 16px; border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: rgba(29,158,117,.3); }
        .stat-icon { width: 52px; height: 52px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.primary { background: rgba(29,158,117,0.1); color: var(--green); }
        .stat-icon.success { background: rgba(34,197,94,0.1); color: #22C55E; }
        .stat-icon.warning { background: rgba(245,158,11,0.1); color: #F59E0B; }
        .stat-icon.danger { background: rgba(239,68,68,0.1); color: #EF4444; }
        .stat-content h3 { font-size: 28px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .stat-content p { font-size: 13px; color: var(--gray-500); }

        .card { background: var(--white); border-radius: var(--radius-xl); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 32px; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .card-header h2 { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: var(--navy); display: flex; align-items: center; gap: 10px; }
        .card-header h2 i { color: var(--green); }
        .card-body { padding: 24px; }

        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .table td { padding: 16px; border-bottom: 1px solid var(--gray-200); vertical-align: middle; }
        .table tr:hover { background: #F8FAFC; }

        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; }
        .user-name { font-weight: 600; color: var(--navy); }
        .user-email { font-size: 12px; color: var(--gray-500); }

        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-badge.admin { background: #FEF2F2; color: #EF4444; }
        .role-badge.user { background: #F0FDF4; color: #22C55E; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.actif { background: #F0FDF4; color: #22C55E; }
        .status-badge.inactif { background: #FEF2F2; color: #EF4444; }

        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: #F0FDF4; border-left: 4px solid #22C55E; color: #166534; }
        .alert-error { background: #FEF2F2; border-left: 4px solid #EF4444; color: #B91C1C; }
        .alert-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; }

        .btn { padding: 10px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 3px 12px rgba(29,158,117,.30); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(29,158,117,.40); }
        .btn-outline { background: transparent; border: 2px solid var(--gray-200); color: var(--gray-700); }
        .btn-outline:hover { border-color: var(--green); color: var(--green); }

        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px; }
        .quick-action { background: #F8FAFC; border: 2px dashed var(--gray-200); border-radius: var(--radius-lg); padding: 24px; text-align: center; text-decoration: none; color: var(--navy); transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .quick-action:hover { border-color: var(--green); background: white; transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .quick-action-icon { width: 60px; height: 60px; border-radius: var(--radius-md); background: var(--green); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .dashboard-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-200); }

        @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 240px 1fr; } .dashboard-main { padding: 24px; } }
        @media (max-width: 768px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .dashboard-sidebar { position: fixed; left: -280px; top: 0; bottom: 0; width: 260px; z-index: 1000; transition: left 0.3s; }
            .dashboard-sidebar.open { left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-main { padding: 20px; }
        }
        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: 1fr; }
            .dashboard-header { flex-direction: column; text-align: center; }
            .dashboard-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="admin-users.php" class="dashboard-nav-item">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
            <a href="admin-create-user.php" class="dashboard-nav-item">
                <i class="bi bi-person-plus-fill"></i> Nouvel utilisateur
            </a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
            <a href="rendezvous/admin-index.php" class="dashboard-nav-item"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
            <a href="ficherdv/admin-index.php" class="dashboard-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
            
            <div class="dashboard-nav-title mt-4">Gestion</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="confirmSwal(event, this, '')">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </aside>
    
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Tableau de bord</h1>
                <p>Bienvenue sur votre espace d'administration</p>
            </div>
            <div class="user-info">
                <div class="user-avatar"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] ?? 'Admin') ?></div>
                    <div class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? 'admin@medchain.com') ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($success_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i><div><?= htmlspecialchars($error_message) ?></div><button class="alert-close">&times;</button></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                <div class="stat-content"><h3><?= $totalUsers ?></h3><p>Total utilisateurs</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-person-plus-fill"></i></div>
                <div class="stat-content"><h3><?= $newThisMonth ?></h3><p>Nouveaux ce mois</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-content"><h3><?= $activeUsers ?></h3><p>Comptes actifs</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-person-x-fill"></i></div>
                <div class="stat-content"><h3><?= $inactiveUsers ?></h3><p>Comptes inactifs</p></div>
            </div>
            <?php foreach ($roleStats as $role): ?>
            <div class="stat-card">
                <div class="stat-icon <?= $role['role'] === 'admin' ? 'danger' : 'primary' ?>">
                    <i class="bi <?= $role['role'] === 'admin' ? 'bi-shield-lock-fill' : 'bi-person-fill' ?>"></i>
                </div>
                <div class="stat-content"><h3><?= $role['count'] ?></h3><p><?= ucfirst($role['role']) ?>s</p></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-clock-history"></i> Derniers utilisateurs</h2>
                <a href="admin-users.php" class="btn btn-outline"><i class="bi bi-eye"></i> Voir tous</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentUsers)): ?>
                    <div style="text-align: center; padding: 48px;"><i class="bi bi-people-fill" style="font-size: 48px; opacity: 0.3;"></i><p style="margin-top: 16px;">Aucun utilisateur récent</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                                    <td><span class="status-badge <?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span></td>
                                    <td><?= formatDate($user['date_inscription']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="admin-create-user.php" class="quick-action"><div class="quick-action-icon"><i class="bi bi-person-plus-fill"></i></div><span>Nouvel utilisateur</span></a>
            <a href="admin-users.php" class="quick-action"><div class="quick-action-icon"><i class="bi bi-people-fill"></i></div><span>Gérer utilisateurs</span></a>
            <a href="admin-reports-statistics.php" class="quick-action"><div class="quick-action-icon"><i class="bi bi-graph-up"></i></div><span>Statistiques</span></a>
            <a href="../frontoffice/auth/profile.php" class="quick-action"><div class="quick-action-icon"><i class="bi bi-person-circle"></i></div><span>Mon profil</span></a>
        </div>
        
        <div class="dashboard-actions">
            <a href="admin-users.php" class="btn btn-primary"><i class="bi bi-people-fill"></i> Gérer utilisateurs</a>
            <a href="../frontoffice/home/index.php" class="btn btn-outline"><i class="bi bi-house-door-fill"></i> Retour au site</a>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 20 });
    
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
    
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
    
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', function(e) {
            confirmSwal(e, this, '');
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
