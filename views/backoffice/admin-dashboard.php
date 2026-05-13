<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../controllers/AdminController.php';
$adminController = new AdminController();
$dashboardData = $adminController->dashboard();

$stats = $dashboardData['stats'] ?? [];
$recentUsers = $dashboardData['recentUsers'] ?? [];
$totalUsers = $stats['total'] ?? 0;
$newThisMonth = $stats['new_this_month'] ?? 0;
$roleStats = $stats['by_role'] ?? [];
$statusStats = $stats['by_status'] ?? [];

$activeUsers = 0; $inactiveUsers = 0;
foreach ($statusStats as $status) {
    if ($status['statut'] === 'actif') $activeUsers = $status['count'];
    if ($status['statut'] === 'inactif') $inactiveUsers = $status['count'];
}

function formatDate($dateString) {
    $date = new DateTime($dateString); $now = new DateTime(); $interval = $now->diff($date);
    if ($interval->days == 0) return "Aujourd'hui à " . $date->format('H:i');
    elseif ($interval->days == 1) return "Hier à " . $date->format('H:i');
    elseif ($interval->days < 7) return "Il y a " . $interval->days . " jours";
    else return $date->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tableau de Bord – Admin – MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="components/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.dashboard-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.dashboard-header h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--navy);}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;}
.stat-card{background:var(--white);border-radius:24px;padding:24px;display:flex;flex-direction:column;gap:12px;border:1px solid rgba(29,158,117,.1);box-shadow:0 4px 20px rgba(0,0,0,0.03);}
.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;}
.stat-icon.primary { background: rgba(29,158,117,0.1); color: var(--green); }
.stat-icon.success { background: rgba(34,197,94,0.1); color: #22C55E; }
.stat-icon.warning { background: rgba(245,158,11,0.1); color: #F59E0B; }
.stat-content h3{font-size:28px;font-weight:800;color:var(--navy);}
.stat-content p{font-size:12px;color:var(--gray-500);text-transform:uppercase;font-weight:600;}
.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid rgba(29,158,117,.15);overflow:hidden;margin-bottom:24px;}
.card-header{padding:18px 24px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;}
.card-header h2{font-size:18px;font-weight:700;color:var(--navy);}
.table{width:100%;border-collapse:collapse;}
.table th{background:#F8FAFC;padding:12px 16px;text-align:left;font-weight:600;color:#64748B;font-size:13px;border-bottom:1px solid var(--gray-200);}
.table td{padding:14px 16px;border-bottom:1px solid var(--gray-200);font-size:14px;}
.role-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.admin { background: #FEE2E2; color: #DC2626; }
.medecin { background: #E0F2FE; color: #0284C7; }
.patient { background: #DCFCE7; color: #16A34A; }
.btn-primary{background:var(--green);color:#fff;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Tableau de Bord</h1>
                <p>Bienvenue, <?= htmlspecialchars($_SESSION['user_prenom']) ?></p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                <div class="stat-content"><h3><?= $totalUsers ?></h3><p>Total Utilisateurs</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-content"><h3><?= $activeUsers ?></h3><p>Actifs</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-person-x-fill"></i></div>
                <div class="stat-content"><h3><?= $inactiveUsers ?></h3><p>Inactifs</p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Dernières Inscriptions</h2>
                <a href="admin-users.php" class="btn-primary">Voir tout</a>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td><?= formatDate($user['date_inscription']) ?></td>
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
