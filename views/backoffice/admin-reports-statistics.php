<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}

require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'overview';
$dashboardData = $adminController->dashboard();
$stats = $dashboardData['stats'] ?? [];
$recentUsers = $dashboardData['recentUsers'] ?? [];
$pendingDoctors = $dashboardData['pendingDoctors'] ?? [];
$usersResult = $adminController->getAllUsers();
$allUsers = $usersResult['success'] ? $usersResult['users'] : [];
$totalUsers = $stats['total'] ?? 0;
$newThisMonth = $stats['new_this_month'] ?? 0;
$roleStats = $stats['by_role'] ?? [];
$totalDoctors = 0;
$activeUsers = 0;
$pendingDoctorsCount = 0;
$rolesSummary = [
    'admin' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'medecin' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'patient' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0]
];
$allowedStatuses = ['actif', 'inactif', 'en_attente', 'rejeté', 'suspendu'];
$statusStats = array_fill_keys($allowedStatuses, 0);

foreach ($allUsers as $user) {
    $role = $user['role'] ?? 'patient';
    $status = $user['statut'] ?? 'inactif';
    if (!isset($rolesSummary[$role])) {
        $rolesSummary[$role] = ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0];
    }
    $rolesSummary[$role]['total']++;
    if (in_array($status, $allowedStatuses)) {
        $rolesSummary[$role][$status] = ($rolesSummary[$role][$status] ?? 0) + 1;
        $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
    }
    if ($role === 'medecin') {
        $totalDoctors++;
        if ($status === 'en_attente') {
            $pendingDoctorsCount++;
        }
    }
    if ($status === 'actif') {
        $activeUsers++;
    }
}

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

function formatNumber($number) {
    return number_format($number, 0, ',', ' ');
}

function getRoleColor($role) {
    $colors = [
        'admin' => '#dc3545',
        'medecin' => '#17a2b8',
        'patient' => '#28a745',
        'moderator' => '#6c757d'
    ];
    return $colors[$role] ?? '#007bff';
}

function getStatusColor($status) {
    $colors = [
        'actif' => '#28a745',
        'inactif' => '#6c757d',
        'en_attente' => '#ffc107',
        'rejeté' => '#dc3545',
        'suspendu' => '#343a40'
    ];
    return $colors[$status] ?? '#007bff';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Statistiques - Medsense Medical</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
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
            margin: 0;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .dashboard-logo-icon { width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .dashboard-logo-icon i { font-size: 18px; color: white; }
        .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
        .dashboard-logo-text span { color: #3b82f6; }

        .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .dashboard-nav-item i { font-size: 18px; width: 24px; }
        .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .dashboard-nav-item.active { background: rgba(59,130,246,0.2); color: #3b82f6; }
        .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
        .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
        .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }


        .dashboard-main { padding: 32px 40px; overflow-y: auto; }

        .dashboard-card { background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid rgba(29,158,117,.15); margin-bottom: 24px; overflow: hidden; }
        .dashboard-card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .dashboard-card-title { font-size: 1.125rem; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 8px; margin: 0; }
        .dashboard-card-body { padding: 24px; }

        .dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .dashboard-stat-card { background: white; padding: 24px; border-radius: var(--radius-md); border: 1px solid rgba(29,158,117,.15); display: flex; align-items: center; gap: 16px; transition: all 0.3s; }
        .dashboard-stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .dashboard-stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .dashboard-stat-icon.primary { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .dashboard-stat-icon.success { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .dashboard-stat-icon.info { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
        .dashboard-stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .dashboard-stat-value { font-size: 1.5rem; font-weight: bold; color: var(--navy); margin-bottom: 2px; }
        .dashboard-stat-label { color: var(--gray-500); font-size: 0.875rem; }

        .dashboard-widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 30px; }
        .dashboard-chart-container { height: 300px; margin-top: 20px; position: relative; }
        
        .dashboard-filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
        .dashboard-form-control { width: 100%; padding: 10px 12px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); font-size: 0.875rem; transition: all 0.3s; }
        .dashboard-form-control:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.15); }
        .dashboard-form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--navy); font-size: 0.875rem; }

        .dashboard-table-responsive { overflow-x: auto; }
        .dashboard-table { width: 100%; border-collapse: collapse; }
        .dashboard-table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: #64748B; border-bottom: 1px solid var(--gray-200); }
        .dashboard-table td { padding: 16px; border-bottom: 1px solid var(--gray-200); vertical-align: middle; }

        .mb-0 { margin-bottom: 0; }
        .mt-4 { margin-top: 1.5rem; }
        .me-1 { margin-right: 0.25rem; }
        .me-2 { margin-right: 0.5rem; }
        .w-100 { width: 100%; }

        /* Button Styles */
        .dashboard-btn { 
            padding: 10px 20px; 
            border-radius: var(--radius-md); 
            font-size: 0.875rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s; 
            border: none; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 8px; 
            text-decoration: none; 
            color: white;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #22c55e; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--gray-200); color: var(--navy); }
        .btn-outline:hover { background: var(--gray-200); }
        .dashboard-btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .dashboard-legend { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 20px; justify-content: center; }
        .dashboard-legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: var(--gray-500); }
        .dashboard-legend-color { width: 12px; height: 12px; border-radius: 3px; }
        
        .role-admin { background: #FEE2E2; color: #DC2626; }
        .role-medecin { background: #E0F2FE; color: #0284C7; }
        .role-patient { background: #F0FDF4; color: #16A34A; }
        .status-actif { background: #F0FDF4; color: #16A34A; }
        .status-inactif { background: #F3F4F6; color: #4B5563; }
        .status-en_attente { background: #FEF3C7; color: #D97706; }
    </style>
</head>
<body class="dashboard-page">

<div class="dashboard-container">
    <aside class="dashboard-sidebar" id="sidebar">
        <div class="dashboard-logo">
            <a href="admin-dashboard.php">
                <div class="dashboard-logo-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="dashboard-logo-text">Med<span>Chain</span></div>
            </a>
        </div>
        
        <nav class="dashboard-nav">
            <div class="dashboard-nav-title">Navigation</div>
            <a href="admin-dashboard.php" class="dashboard-nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="admin-users.php" class="dashboard-nav-item">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <a href="admin-create-user.php" class="dashboard-nav-item">
                <i class="fas fa-user-plus"></i> Nouvel utilisateur
            </a>
            <a href="rendezvous/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-calendar-check"></i> Rendez-vous
            </a>
            <a href="ficherdv/admin-index.php" class="dashboard-nav-item">
                <i class="fas fa-file-medical-alt"></i> Fiches Médicales
            </a>
            <a href="admin-reports-statistics.php" class="dashboard-nav-item active">
                <i class="fas fa-chart-pie"></i> Statistiques
            </a>
            
            <div class="dashboard-nav-title">Personnel</div>
            <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                <i class="fas fa-user-circle"></i> Mon profil
            </a>
            <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
            <?php if ($success_message): ?>
                <div class="dashboard-alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($success_message) ?></div>
                    <button type="button" class="dashboard-alert-close">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="dashboard-alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error_message) ?></div>
                    <button type="button" class="dashboard-alert-close">&times;</button>
                </div>
            <?php endif; ?>
            <div class="dashboard-card dashboard-filter-card">
                <div class="dashboard-card-header">
                    <h3 class="dashboard-card-title">
                        <i class="fas fa-filter me-2"></i>Filtres de Rapport
                    </h3>
                </div>
                <div class="dashboard-card-body">
                    <form method="GET" action="" class="dashboard-filter-form">
                        <div class="dashboard-form-group">
                            <label class="dashboard-form-label">Date de début</label>
                            <input type="date" class="dashboard-form-control" name="start_date" 
                                   value="<?= htmlspecialchars($startDate) ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="dashboard-form-group">
                            <label class="dashboard-form-label">Date de fin</label>
                            <input type="date" class="dashboard-form-control" name="end_date" 
                                   value="<?= htmlspecialchars($endDate) ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="dashboard-form-group">
                            <label class="dashboard-form-label">Type de rapport</label>
                            <select class="dashboard-form-control" name="report_type">
                                <option value="overview" <?= $reportType === 'overview' ? 'selected' : '' ?>>Vue d'ensemble</option>
                                <option value="users" <?= $reportType === 'users' ? 'selected' : '' ?>>Utilisateurs</option>
                                <option value="doctors" <?= $reportType === 'doctors' ? 'selected' : '' ?>>Médecins</option>
                            </select>
                        </div>
                        
                        <div class="dashboard-form-group">
                            <button type="submit" class="dashboard-btn btn-primary w-100">
                                <i class="fas fa-chart-bar me-1"></i> Générer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-stats">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-value"><?= formatNumber($totalUsers) ?></div>
                        <div class="dashboard-stat-label">Total Utilisateurs</div>
                    </div>
                </div>
                
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-icon success">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-value"><?= formatNumber($newThisMonth) ?></div>
                        <div class="dashboard-stat-label">Nouveaux ce mois</div>
                    </div>
                </div>
                
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-icon info">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-value"><?= formatNumber($totalDoctors) ?></div>
                        <div class="dashboard-stat-label">Médecins</div>
                    </div>
                </div>
                
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-value"><?= formatNumber($pendingDoctorsCount) ?></div>
                        <div class="dashboard-stat-label">En attente</div>
                    </div>
                </div>
                
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-value"><?= formatNumber($activeUsers) ?></div>
                        <div class="dashboard-stat-label">Utilisateurs actifs</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-widgets">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-chart-pie me-2"></i>Répartition par Rôle
                        </h3>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="dashboard-chart-container">
                            <canvas id="roleChart"></canvas>
                        </div>
                        <div class="dashboard-legend" id="roleLegend"></div>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">
                            <i class="fas fa-chart-bar me-2"></i>Répartition par Statut
                        </h3>
                    </div>
                    <div class="dashboard-card-body">
                        <div class="dashboard-chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="dashboard-legend" id="statusLegend"></div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3 class="dashboard-card-title">
                        <i class="fas fa-table me-2"></i>Statistiques Détaillées par Rôle
                    </h3>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-table-responsive">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Rôle</th>
                                    <th>Total</th>
                                    <th>Actifs</th>
                                    <th>Inactifs</th>
                                    <th>En attente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($rolesSummary as $role => $stats):
                                    if ($stats['total'] > 0):
                                ?>
                                <tr>
                                    <td>
                                        <span class="dashboard-badge role-<?= $role ?>">
                                            <?= ucfirst($role) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= formatNumber($stats['total']) ?></strong></td>
                                    <td>
                                        <span class="dashboard-badge status-actif">
                                            <?= formatNumber($stats['actif'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dashboard-badge status-inactif">
                                            <?= formatNumber($stats['inactif'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dashboard-badge status-en_attente">
                                            <?= formatNumber($stats['en_attente'] ?? 0) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3 class="dashboard-card-title">
                        <i class="fas fa-file-export me-2"></i>Export des Données
                    </h3>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-export-options">
                        <a href="admin-export-excel.php?type=statistics&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" 
                           class="dashboard-btn btn-success">
                            <i class="fas fa-file-excel me-1"></i> Exporter en Excel
                        </a>
                        <a href="admin-export-pdf.php?type=statistics&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" 
                           class="dashboard-btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i> Exporter en PDF
                        </a>
                        <a href="javascript:void(0);" onclick="window.print()" class="dashboard-btn btn-primary">
                            <i class="fas fa-print me-1"></i> Imprimer
                        </a>
                        <a href="admin-reports-statistics.php" class="dashboard-btn btn-outline">
                            <i class="fas fa-redo me-1"></i> Actualiser
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-actions">
                <a href="admin-dashboard.php" class="dashboard-btn btn-outline">
                    <i class="fas fa-tachometer-alt me-2"></i> Retour au Dashboard
                </a>
                <a href="admin-reports-financial.php" class="dashboard-btn btn-success">
                    <i class="fas fa-money-bill-wave me-2"></i> Rapports Financiers
                </a>
                <a href="admin-audit.php" class="dashboard-btn btn-primary">
                    <i class="fas fa-clipboard-list me-2"></i> Audit Médical
                </a>
            </div>
        </main>
    </div>

 
    <script src="../assets/js/jquery-2.2.4.min.js"></script>
    <script src="../assets/js/popper.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    
    <script>
       
        document.getElementById('menuToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        });

      
        document.querySelectorAll('.with-submenu').forEach(item => {
            const toggle = item.querySelector('.submenu-toggle');
            const submenu = item.querySelector('.dashboard-submenu');
            
            if (toggle && submenu) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    document.querySelectorAll('.with-submenu').forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.querySelector('.dashboard-submenu').style.display = 'none';
                            otherItem.querySelector('.submenu-toggle').classList.remove('open');
                        }
                    });
                    
                    if (submenu.style.display === 'block') {
                        submenu.style.display = 'none';
                        toggle.classList.remove('open');
                    } else {
                        submenu.style.display = 'block';
                        toggle.classList.add('open');
                    }
                });
            }
        });

       
        document.querySelectorAll('.dashboard-alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.dashboard-alert').style.display = 'none';
            });
        });

        setTimeout(() => {
            const alerts = document.querySelectorAll('.dashboard-alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        const roleData = {
            labels: [
                <?php 
                $roleLabels = [];
                $roleCounts = [];
                $roleColors = [];
                
                
                foreach ($rolesSummary as $role => $stats):
                    if ($stats['total'] > 0):
                        $roleLabels[] = ucfirst($role);
                        $roleCounts[] = $stats['total'];
                        $roleColors[] = getRoleColor($role);
                    endif;
                endforeach;
                
                echo '"' . implode('","', $roleLabels) . '"';
                ?>
            ],
            datasets: [{
                data: [<?php echo implode(',', $roleCounts); ?>],
                backgroundColor: [<?php echo '"' . implode('","', $roleColors) . '"'; ?>],
                borderWidth: 1
            }]
        };

       
        const statusData = {
            labels: [
                <?php 
                $statusLabels = [];
                $statusCounts = [];
                $statusColors = [];
                
                foreach ($statusStats as $status => $count):
                    if ($count > 0):
                        $statusLabels[] = ucfirst($status);
                        $statusCounts[] = $count;
                        $statusColors[] = getStatusColor($status);
                    endif;
                endforeach;
                
                echo '"' . implode('","', $statusLabels) . '"';
                ?>
            ],
            datasets: [{
                data: [<?php echo implode(',', $statusCounts); ?>],
                backgroundColor: [<?php echo '"' . implode('","', $statusColors) . '"'; ?>],
                borderWidth: 1
            }]
        };

       
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            label += context.formattedValue + ' (' + percentage + '%)';
                            return label;
                        }
                    }
                }
            }
        };

       
        document.addEventListener('DOMContentLoaded', function() {
            
            const roleCtx = document.getElementById('roleChart').getContext('2d');
            const roleChart = new Chart(roleCtx, {
                type: 'pie',
                data: roleData,
                options: chartOptions
            });

            
            const roleLegend = document.getElementById('roleLegend');
            roleData.labels.forEach((label, index) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'dashboard-legend-item';
                legendItem.innerHTML = `
                    <div class="dashboard-legend-color" style="background-color: ${roleData.datasets[0].backgroundColor[index]}"></div>
                    <span>${label}</span>
                `;
                roleLegend.appendChild(legendItem);
            });

           
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: statusData,
                options: chartOptions
            });

            const statusLegend = document.getElementById('statusLegend');
            statusData.labels.forEach((label, index) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'dashboard-legend-item';
                legendItem.innerHTML = `
                    <div class="dashboard-legend-color" style="background-color: ${statusData.datasets[0].backgroundColor[index]}"></div>
                    <span>${label}</span>
                `;
                statusLegend.appendChild(legendItem);
            });
        });

       
        
    </script>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>

