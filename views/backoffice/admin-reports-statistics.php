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
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
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
}

.dashboard-logo-img {
    max-height: 40px;
    margin-right: 12px;
}

.dashboard-logo-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.dashboard-nav {
    flex: 1;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dashboard-nav-section {
    margin-bottom: 20px;
}

.dashboard-nav-title {
    padding: 0 20px 8px;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.dashboard-nav-item {
    padding: 12px 20px;
    color: #cbd5e1;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s;
    border-left: 3px solid transparent;
    cursor: pointer;
}

.dashboard-nav-item:hover,
.dashboard-nav-item.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #3b82f6;
}

.dashboard-nav-item.logout {
    color: #f87171;
    margin-top: auto;
}

.dashboard-nav-item.logout:hover {
    background: rgba(248, 113, 113, 0.1);
}

.dashboard-nav-item i {
    width: 20px;
    text-align: center;
}

.dashboard-badge {
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 8px;
}

.with-submenu {
    flex-direction: column;
    padding: 0;
}

.submenu-toggle {
    transition: transform 0.3s;
}

.submenu-toggle.open {
    transform: rotate(180deg);
}

.dashboard-submenu {
    display: none;
    background: rgba(0, 0, 0, 0.2);
    border-left: 3px solid #3b82f6;
}

.dashboard-submenu-item {
    padding: 10px 20px 10px 45px;
    color: #cbd5e1;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s;
    font-size: 0.875rem;
}

.dashboard-submenu-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: white;
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
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-flex align-items-center gap-3">
                <h1 class="dashboard-title mb-0">Rapports Statistiques</h1>
                <div class="dashboard-subtitle">Analyse des données du système</div>
            </div>
            <div class="dashboard-user-info">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                        <div class="dashboard-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="dashboard-user-details ms-2">
                            <div class="dashboard-user-name">Admin</div>
                            <div class="dashboard-user-role">Administrateur</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../frontoffice/auth/profile.php"><i class="fas fa-user me-2"></i> Mon profil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../../../controllers/logout.php" 
                               onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="dashboard-logo">
                <a href="../home/index.php" class="text-white text-decoration-none">
                    <span class="dashboard-logo-text">Medsense Medical</span>
                </a>
            </div>
            
            <nav class="dashboard-nav">
                <div class="dashboard-nav-section">
                    <div class="dashboard-nav-title">Tableau de Bord</div>
                    <a class="dashboard-nav-item" href="admin-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="dashboard-nav-section">
                    <div class="dashboard-nav-title">Gestion Médicale</div>
                    
                    <div class="dashboard-nav-item with-submenu">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div>
                                <i class="fas fa-calendar-check"></i>
                                <span>Rendez-vous</span>
                            </div>
                            <i class="fas fa-chevron-down submenu-toggle"></i>
                        </div>
                        <div class="dashboard-submenu">
                            <a class="dashboard-submenu-item" href="admin-appointments.php">
                                <i class="fas fa-list"></i>
                                <span>Tous les rendez-vous</span>
                            </a>
                            <a class="dashboard-submenu-item" href="admin-patient-appointments.php">
                                <i class="fas fa-user-injured"></i>
                                <span>Rendez-vous patients</span>
                            </a>
                            <a class="dashboard-submenu-item" href="admin-new-appointment.php">
                                <i class="fas fa-plus-circle"></i>
                                <span>Nouveau rendez-vous</span>
                            </a>
                        </div>
                    </div>
                    
                    <a class="dashboard-nav-item" href="admin-patients.php">
                        <i class="fas fa-user-injured"></i>
                        <span>Patients</span>
                    </a>
                    
                    <div class="dashboard-nav-item with-submenu">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div>
                                <i class="fas fa-user-md"></i>
                                <span>Médecins</span>
                                <?php if ($pendingDoctorsCount > 0): ?>
                                    <span class="dashboard-badge"><?= $pendingDoctorsCount ?></span>
                                <?php endif; ?>
                            </div>
                            <i class="fas fa-chevron-down submenu-toggle"></i>
                        </div>
                        <div class="dashboard-submenu">
                            <a class="dashboard-submenu-item" href="admin-doctors.php">
                                <i class="fas fa-list"></i>
                                <span>Tous les médecins</span>
                            </a>
                            <a class="dashboard-submenu-item" href="admin-doctor-availability.php">
                                <i class="fas fa-clock"></i>
                                <span>Disponibilité</span>
                            </a>
                        </div>
                    </div>
                    
                    <a class="dashboard-nav-item" href="admin-users.php">
                        <i class="fas fa-users"></i>
                        <span>Utilisateurs</span>
                    </a>
                    
                    <a class="dashboard-nav-item" href="admin-complaints.php">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Réclamations</span>
                    </a>
                </div>
                
                <div class="dashboard-nav-section">
                    <div class="dashboard-nav-title">Rapports</div>
                    
                    <a class="dashboard-nav-item active" href="admin-reports-statistics.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Statistiques</span>
                    </a>
                    
                    <a class="dashboard-nav-item" href="admin-reports-financial.php">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Financiers</span>
                    </a>
                    
                    <a class="dashboard-nav-item" href="admin-audit.php">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Audit médical</span>
                    </a>
                </div>
                
                <div class="dashboard-nav-section mt-auto">
                    <a class="dashboard-nav-item logout" href="../../../controllers/logout.php" 
                       onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
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