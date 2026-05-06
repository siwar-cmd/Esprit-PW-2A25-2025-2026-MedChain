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
    'user' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0],
    'patient' => ['total' => 0, 'actif' => 0, 'inactif' => 0, 'en_attente' => 0]
];
$allowedStatuses = ['actif', 'inactif', 'en_attente', 'rejeté', 'suspendu'];
$statusStats = array_fill_keys($allowedStatuses, 0);

foreach ($allUsers as $user) {
    $role = $user['role'] ?? 'user';
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
        'user' => '#28a745',
        'patient' => '#ffc107',
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
    <link rel="stylesheet" href="../assets/vendors/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
.dashboard-page {
    min-height: 100vh;
    background: #f8fafc;
}

.dashboard-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    grid-template-rows: 70px 1fr;
    grid-template-areas:
        "sidebar header"
        "sidebar main";
    min-height: 100vh;
}

.dashboard-header {
    grid-area: header;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}

.dashboard-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
}

.dashboard-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
}

.dashboard-subtitle {
    font-size: 0.875rem;
    color: #64748b;
}

.dashboard-user-info {
    display: flex;
    align-items: center;
}

.dashboard-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

.dashboard-user-details {
    line-height: 1.4;
}

.dashboard-user-name {
    font-weight: 600;
    color: #1e293b;
}

.dashboard-user-role {
    font-size: 0.75rem;
    color: #64748b;
}

.dashboard-sidebar {
    grid-area: sidebar;
    background: linear-gradient(180deg, #1E3A52 0%, #0F172A 100%);
    color: white;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    position: sticky;
    top: 0;
    height: 100vh;
}

.dashboard-logo {
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.dashboard-nav {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 0 12px;
}

.dashboard-nav-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #64748B;
    padding: 16px 16px 8px;
    font-weight: 600;
}

.dashboard-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #94A3B8;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 500;
    border-left: none;
    cursor: pointer;
}

.dashboard-nav-item i {
    font-size: 18px;
    width: 24px;
}

.dashboard-nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.dashboard-nav-item.active {
    background: rgba(29, 158, 117, 0.2);
    color: #1D9E75;
}

.dashboard-nav-item.logout {
    margin-top: auto;
    margin-bottom: 20px;
    color: #F87171;
}

.dashboard-nav-item.logout:hover {
    background: rgba(248, 113, 113, 0.1);
}

.dashboard-main {
    grid-area: main;
    padding: 24px;
    overflow-y: auto;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.dashboard-alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.dashboard-alert i {
    margin-top: 2px;
}

.dashboard-alert-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: inherit;
    cursor: pointer;
    margin-left: auto;
    opacity: 0.7;
}

.dashboard-alert-close:hover {
    opacity: 1;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
    overflow: hidden;
}

.dashboard-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-card-body {
    padding: 24px;
}

.dashboard-card-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.dashboard-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dashboard-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.dashboard-stat-icon.primary {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.dashboard-stat-icon.success {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.dashboard-stat-icon.info {
    background: rgba(6, 182, 212, 0.1);
    color: #06b6d4;
}

.dashboard-stat-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.dashboard-stat-icon.danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.dashboard-stat-value {
    font-size: 1.875rem;
    font-weight: bold;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 4px;
}

.dashboard-stat-label {
    color: #64748b;
    font-size: 0.875rem;
}

.dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.dashboard-table-responsive {
    overflow-x: auto;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

.dashboard-table th {
    background: #f8fafc;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.dashboard-table td {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.dashboard-table tbody tr:hover {
    background: #f8fafc;
}

.dashboard-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.dashboard-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.dashboard-user-name {
    font-weight: 600;
    color: #1e293b;
}

.dashboard-user-email {
    font-size: 0.875rem;
    color: #64748b;
}

.dashboard-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.dashboard-badge.role-admin {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.dashboard-badge.role-medecin {
    background: rgba(6, 182, 212, 0.1);
    color: #0891b2;
}

.dashboard-badge.role-user {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.dashboard-badge.role-patient {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.dashboard-badge.status-actif {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.dashboard-badge.status-inactif {
    background: rgba(148, 163, 184, 0.1);
    color: #64748b;
}

.dashboard-badge.status-en_attente {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.dashboard-badge.status-rejeté {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.dashboard-badge.status-suspendu {
    background: rgba(107, 114, 128, 0.1);
    color: #4b5563;
}

.dashboard-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 24px;
}

.dashboard-btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-size: 0.875rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-success {
    background: #22c55e;
    color: white;
}

.btn-success:hover {
    background: #16a34a;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-outline {
    background: white;
    color: #64748b;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f8fafc;
    border-color: #9ca3af;
}

.dashboard-chart-container {
    height: 300px;
    margin-top: 20px;
    position: relative;
}

.dashboard-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
    justify-content: center;
}

.dashboard-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.dashboard-filter-card {
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
    margin-bottom: 24px;
}

.dashboard-filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.dashboard-form-group {
    margin-bottom: 0;
}

.dashboard-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.dashboard-form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: all 0.3s;
}

.dashboard-form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dashboard-export-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 1200px) {
    .dashboard-container {
        grid-template-columns: 200px 1fr;
    }
    
    .dashboard-sidebar {
        width: 200px;
    }
    
    .dashboard-widgets {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        grid-template-columns: 1fr;
        grid-template-areas:
            "header"
            "main";
    }
    
    .dashboard-sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        bottom: 0;
        z-index: 1000;
        width: 250px;
        transition: left 0.3s;
    }
    
    .dashboard-sidebar.active {
        left: 0;
    }
    
    .dashboard-menu-toggle {
        display: block;
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-widgets {
        grid-template-columns: 1fr;
    }
    
    .dashboard-main {
        padding: 16px;
    }
    
    .dashboard-card-body {
        padding: 16px;
    }
    
    .dashboard-actions {
        flex-direction: column;
    }
    
    .dashboard-btn {
        width: 100%;
    }
    
    .dashboard-export-options {
        flex-direction: column;
    }
    
    .dashboard-filter-form {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        padding: 0 16px;
    }
}

.mb-0 {
    margin-bottom: 0;
}

.mb-3 {
    margin-bottom: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}

.text-muted {
    color: #6b7280 !important;
}

.text-danger {
    color: #ef4444 !important;
}

.text-success {
    color: #10b981 !important;
}

.flex-grow-1 {
    flex-grow: 1;
}

.d-flex {
    display: flex;
}

.gap-3 {
    gap: 1rem;
}

.align-items-center {
    align-items: center;
}

.justify-content-between {
    justify-content: space-between;
}

.w-100 {
    width: 100%;
}
    </style>
</head>
<body class="dashboard-page">

    <div class="dashboard-container">
        <header class="dashboard-header">
            <button class="dashboard-menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>
            <div style="display:flex;align-items:center;gap:12px;">
                <h1 class="dashboard-title mb-0"><i class="bi bi-graph-up" style="color:#1D9E75;margin-right:8px;"></i>Rapports Statistiques</h1>
                <span class="dashboard-subtitle">Analyse des données du système</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="../frontoffice/auth/profile.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;padding:8px 14px;border-radius:12px;border:2px solid #E5E7EB;color:#374151;font-size:13px;font-weight:600;transition:all .3s;" onmouseover="this.style.borderColor='#1D9E75';this.style.color='#1D9E75';" onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#374151';">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1D9E75,#0F6E56);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill" style="color:#fff;font-size:16px;"></i>
                    </div>
                    <span><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin')) ?></span>
                </a>
                <a href="../../controllers/logout.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')" style="display:flex;align-items:center;gap:6px;text-decoration:none;padding:8px 14px;border-radius:12px;background:#FEF2F2;color:#EF4444;font-size:13px;font-weight:600;transition:all .3s;" onmouseover="this.style.background='#EF4444';this.style.color='#fff';" onmouseout="this.style.background='#FEF2F2';this.style.color='#EF4444';">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </header>
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="dashboard-logo">
                <a href="admin-dashboard.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#1D9E75,#0F6E56);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-plus-square-fill" style="font-size:18px;color:#fff;"></i>
                    </div>
                    <span style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff;">Med<span style="color:#1D9E75;">Chain</span></span>
                </a>
            </div>
            
            <nav class="dashboard-nav">
                <div class="dashboard-nav-title">Navigation</div>
                <a href="admin-dashboard.php" class="dashboard-nav-item">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="admin-users.php" class="dashboard-nav-item">
                    <i class="bi bi-people-fill"></i> Utilisateurs
                </a>
                <a href="admin-create-user.php" class="dashboard-nav-item">
                    <i class="bi bi-person-plus-fill"></i> Nouvel utilisateur
                </a>
                <a href="admin-reports-statistics.php" class="dashboard-nav-item active">
                    <i class="bi bi-graph-up"></i> Statistiques
                </a>

                <!-- SECTION MÉDICALE AJOUTÉE -->
                <div class="dashboard-nav-title">Médical</div>
                <a href="admin-medecin.php" class="dashboard-nav-item">
                    <i class="bi bi-heart-pulse-fill"></i> Médecins
                </a>
                <a href="admin-patient.php" class="dashboard-nav-item">
                    <i class="bi bi-person-lines-fill"></i> Patients
                </a>
                <a href="admin-medical-dashboard.php" class="dashboard-nav-item">
                    <i class="bi bi-activity"></i> Suivi santé avancé
                </a>
                <!-- FIN SECTION MÉDICALE -->

                <div class="dashboard-nav-title">Outils</div>
                <a href="admin-chatbot.php" class="dashboard-nav-item"><i class="bi bi-robot"></i> Assistant IA</a>
                <div class="dashboard-nav-title">Gestion</div>
                <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item">
                    <i class="bi bi-person-circle"></i> Mon profil
                </a>
                <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
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

       
        document.querySelectorAll('a[href*="logout"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>