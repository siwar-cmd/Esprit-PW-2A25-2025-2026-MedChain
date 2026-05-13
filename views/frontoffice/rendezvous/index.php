<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/RendezVousController.php';

$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();

if (!$isLoggedIn) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') {
    // If admin or medecin, redirect to backoffice
    header('Location: ../../../views/backoffice/admin-dashboard.php');
    exit;
}

$rdvController = new RendezVousController();

// Traitement suppression
if (isset($_POST['delete_id'])) {
    $rdvController->deleteRendezVous($_POST['delete_id']);
    $_SESSION['success_message'] = "Rendez-vous annulé avec succès.";
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'desc';
$filters = ['search' => $search, 'sort' => $sort, 'order' => $order];
$rdvData = $rdvController->getAllRendezVous($filters, 'patient', $currentUser->getId());
$rendezvous = $rdvData['success'] ? $rdvData['rdvs'] : [];

$stats = $rdvController->getStats('patient', $currentUser->getId());

// Pagination Logic
$items_per_page = 5;
$total_items = count($rendezvous);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $items_per_page;
$paginated_rdv = array_slice($rendezvous, $offset, $items_per_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mes Rendez-vous — MedChain</title>
  
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
  <style>
    :root {
      --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
      --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
      --shadow-green: 0 8px 30px rgba(29,158,117,.18);
      --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f0faf6; color: var(--gray-700); }
    .mc-container { max-width: 1200px; margin: 0 auto; }
    .page-title { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--navy); margin-bottom: 20px; }
    .stat-card { background: var(--white); border-radius: var(--radius-md); padding: 20px; box-shadow: var(--shadow-sm); border-left: 4px solid var(--green); }
    .stat-card h3 { font-size: 24px; color: var(--navy); margin: 0; font-family: 'Syne', sans-serif; }
    .stat-card p { margin: 0; color: var(--gray-500); font-size: 14px; }
    .action-bar { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px; flex-wrap: wrap; gap: 15px; }
    .search-box { display: flex; gap: 10px; }
    .btn-mc { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; border: none; padding: 9px 18px; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 12px rgba(29,158,117,.30); transition: all .25s; }
    .btn-mc:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(29,158,117,.40); color: white; }
    .table-custom { background: white; border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); }
    .table-custom th { background: #f3f4f6; color: var(--navy); font-weight: 600; padding: 15px; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid var(--gray-200); }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-planifie { background: #E0F2FE; color: #0284C7; }
    .status-termine { background: #DCFCE7; color: #16A34A; }
    .status-annule { background: #FEE2E2; color: #DC2626; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 15px; padding-bottom: 10px; }
    .page-link { padding: 6px 14px; border-radius: 8px; background: white; border: 1px solid var(--gray-200); color: var(--navy); text-decoration: none; transition: 0.3s; font-weight: 500; font-size: 13px; }
    .page-link:hover { border-color: var(--green); color: var(--green); }
    .page-link.active { background: var(--green); color: white; border-color: var(--green); }
    .page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

    /* ═══════════════════════════════════════════════
       FRONTOFFICE SIDEBAR — Design moderne & distinct
    ═══════════════════════════════════════════════ */
    .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }

    .dashboard-sidebar {
      background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%);
      border-right: 1px solid rgba(29,158,117,.15);
      position: sticky; top: 0; height: 100vh;
      display: flex; flex-direction: column; overflow-y: auto;
      box-shadow: 4px 0 24px rgba(29,158,117,.08);
    }

    /* Logo zone */
    .sidebar-logo-zone {
      padding: 26px 22px 20px;
      border-bottom: 1px solid rgba(29,158,117,.12);
    }
    .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .sidebar-logo-icon {
      width: 42px; height: 42px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(29,158,117,.35);
    }
    .sidebar-logo-icon i { font-size: 20px; color: white; }
    .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.3px; }
    .sidebar-logo-text span { color: var(--green); }
    .sidebar-tagline { font-size: 11px; color: var(--gray-500); margin-top: 3px; letter-spacing: .03em; }

    /* User card */
    .sidebar-user-card {
      margin: 18px 16px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      border-radius: var(--radius-lg);
      padding: 18px 16px;
      box-shadow: var(--shadow-green);
      position: relative; overflow: hidden;
    }
    .sidebar-user-card::before {
      content: '';
      position: absolute; top: -20px; right: -20px;
      width: 90px; height: 90px; border-radius: 50%;
      background: rgba(255,255,255,.1);
    }
    .sidebar-user-card::after {
      content: '';
      position: absolute; bottom: -15px; left: -15px;
      width: 60px; height: 60px; border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .sidebar-user-avatar {
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(255,255,255,.25);
      border: 2.5px solid rgba(255,255,255,.5);
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 12px;
    }
    .sidebar-user-avatar i { font-size: 22px; color: white; }
    .sidebar-user-name { font-size: 15px; font-weight: 700; color: white; margin-bottom: 2px; }
    .sidebar-user-role {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11px; color: rgba(255,255,255,.85);
      background: rgba(255,255,255,.18);
      padding: 3px 10px; border-radius: 20px; margin-top: 4px;
    }
    .sidebar-user-role i { font-size: 10px; }

    /* Health pulse widget */
    .sidebar-health-widget {
      margin: 0 16px 6px;
      background: var(--white);
      border: 1px solid rgba(29,158,117,.15);
      border-radius: var(--radius-md);
      padding: 14px 16px;
    }
    .sidebar-health-label { font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
    .sidebar-health-bar-wrap { background: var(--gray-200); border-radius: 6px; height: 6px; overflow: hidden; }
    .sidebar-health-bar {
      height: 100%; border-radius: 6px; width: 78%;
      background: linear-gradient(90deg, var(--green), #34D399);
      animation: health-grow 1.2s ease-out forwards;
    }
    @keyframes health-grow { from { width: 0; } to { width: 78%; } }
    .sidebar-health-stats { display: flex; justify-content: space-between; margin-top: 8px; }
    .sidebar-health-stat { font-size: 12px; color: var(--gray-500); }
    .sidebar-health-stat strong { color: var(--green); font-weight: 700; }

    /* Nav */
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px 12px 0; }
    .sidebar-nav-section-label {
      font-size: 10.5px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .12em; color: #A0AEC0;
      padding: 14px 12px 6px;
    }
    .sidebar-nav-item {
      display: flex; align-items: center; gap: 13px;
      padding: 11px 14px; color: var(--gray-500);
      text-decoration: none; border-radius: var(--radius-md);
      transition: all 0.25s; font-size: 14px; font-weight: 500;
      position: relative;
    }
    .sidebar-nav-item .nav-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
      background: rgba(29,158,117,.08);
      color: var(--green);
      transition: all 0.25s;
    }
    .sidebar-nav-item:hover {
      background: rgba(29,158,117,.07);
      color: var(--green-dark);
    }
    .sidebar-nav-item:hover .nav-icon {
      background: rgba(29,158,117,.15);
      transform: scale(1.08);
    }
    .sidebar-nav-item.active {
      background: linear-gradient(90deg, rgba(29,158,117,.12), rgba(29,158,117,.04));
      color: var(--green-dark);
      font-weight: 600;
    }
    .sidebar-nav-item.active .nav-icon {
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: white;
      box-shadow: 0 4px 12px rgba(29,158,117,.30);
    }
    .sidebar-nav-item.active::before {
      content: '';
      position: absolute; left: 0; top: 20%; bottom: 20%;
      width: 3px; border-radius: 0 3px 3px 0;
      background: var(--green);
    }
    .sidebar-nav-item.logout {
      color: #E53E3E;
      margin: 0 0 4px;
    }
    .sidebar-nav-item.logout .nav-icon {
      background: rgba(229,62,62,.08);
      color: #E53E3E;
    }
    .sidebar-nav-item.logout:hover {
      background: rgba(229,62,62,.07);
    }
    .sidebar-nav-item.logout:hover .nav-icon {
      background: rgba(229,62,62,.15);
    }

    /* Footer sidebar */
    .sidebar-footer {
      padding: 16px;
      border-top: 1px solid rgba(29,158,117,.10);
      margin-top: auto;
    }
    .sidebar-footer-back {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border-radius: var(--radius-md);
      background: var(--green-pale);
      color: var(--green-dark); font-size: 13px; font-weight: 600;
      text-decoration: none; transition: all .2s;
      border: 1px solid rgba(29,158,117,.2);
    }
    .sidebar-footer-back:hover {
      background: rgba(29,158,117,.15);
      transform: translateX(-3px);
    }

    .dashboard-main { padding: 32px 40px; overflow-y: auto; width: 100%; }

    @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 260px 1fr; } }
    @media (max-width: 768px) {
      .dashboard-container { grid-template-columns: 1fr; }
      .dashboard-sidebar { position: fixed; left: -290px; top: 0; bottom: 0; width: 280px; z-index: 1000; transition: left 0.3s; }
      .dashboard-sidebar.open { left: 0; }
      .dashboard-main { padding: 20px; }
    }

    @media print {
        @page { margin: 1cm; }
        body { margin: 0; padding: 0; visibility: hidden; background: white; }
        .dashboard-sidebar, .action-bar, .actions-col, .pagination, .stats-row, #rdvTable, .alert { display: none !important; }
        #capture-area, #capture-area * { visibility: visible; }
        #capture-area { position: absolute; left: 0; top: 0; width: 100%; padding: 1cm; background: white; }

        #rdvTablePrint { display: table !important; width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #edf2f7; }
        #rdvTablePrint th { background-color: #f7fafc; color: #2d3748; padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
        #rdvTablePrint td { padding: 12px 15px; font-size: 13px; color: #4a5568; border-bottom: 1px solid #edf2f7; vertical-align: middle; line-height: 1.4; }
        #rdvTablePrint tr:nth-child(even) { background-color: #fcfdfe; }

        .date-cell { font-weight: 700; color: var(--navy); display: block; }
        .time-cell { font-size: 11px; color: var(--gray-500); display: block; }
        .doctor-cell { font-weight: 600; color: var(--green-dark); display: block; }
        .type-tag { font-size: 11px; color: var(--gray-500); font-style: italic; display: block; }

        .pdf-badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; display: inline-block; }
        .pdf-badge-planifie { background: #ebf8ff; color: #2b6cb0; border-color: #bee3f8; }
        .pdf-badge-termine { background: #f0fff4; color: #2f855a; border-color: #c6f6d5; }
        .pdf-badge-annule { background: #fff5f5; color: #c53030; border-color: #fed7d7; }

        .print-header { display: flex !important; align-items: center; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .print-logo { width: 45px; height: 45px; background: var(--green); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .print-logo i { color: white; font-size: 20px; }
        .print-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); }
        .print-title span { color: var(--green); }
        .stat-card { border: 1px solid #e2e8f0 !important; background: #fff !important; padding: 15px !important; }
    }

    .print-header { 
        display: flex; 
        opacity: 0; 
        height: 0; 
        overflow: hidden; 
        pointer-events: none;
        transition: none;
    }
    #rdvTablePrint { 
        display: none; 
        opacity: 0; 
        height: 0; 
        overflow: hidden; 
        pointer-events: none;
    }
    .btn-mc { background-color: var(--green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-mc:hover { background-color: var(--green-dark); color: white; transform: translateY(-2px); }
    .btn-stats { background: linear-gradient(135deg, var(--green), var(--navy)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-stats:hover { background: linear-gradient(135deg, var(--green-dark), var(--navy)); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29, 158, 117, 0.3); }
    .sort-select { 
      padding: 10px 35px 10px 15px; 
      border-radius: 12px; 
      border: 1.5px solid var(--gray-200); 
      background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236B7280' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E") no-repeat right 12px center;
      background-size: 14px;
      appearance: none;
      color: var(--navy); 
      font-weight: 600; 
      font-size: 13px; 
      outline: none; 
      cursor: pointer; 
      transition: all 0.3s;
      min-width: 160px;
    }
    .sort-select:focus, .search-box:focus-within { 
      border-color: var(--green); 
      box-shadow: 0 0 0 4px rgba(29, 158, 117, 0.1); 
    }

    .search-box { display: flex; align-items: center; background: white; border: 1.5px solid var(--gray-200); border-radius: 12px; padding: 4px; transition: all 0.3s; gap: 0; }
    .search-box .form-control { border: none !important; padding: 8px 12px; outline: none !important; font-size: 13px; font-weight: 500; min-width: 250px; background: transparent; }
    .search-box .btn-mc { padding: 8px 15px; border-radius: 8px; }

    .btn { padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 13px;}
    .btn-mc { background: var(--green); color: white; }
    .btn-secondary { background: var(--gray-500); color: white; }
  </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar FrontOffice Moderne -->
    <aside class="dashboard-sidebar" id="sidebar">

      <!-- Logo -->
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div>
            <div class="sidebar-logo-text">Med<span>Chain</span></div>
            <div class="sidebar-tagline">Espace Patient</div>
          </div>
        </a>
      </div>

      <!-- User Card -->
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Patient') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Patient</div>
      </div>

      <!-- Health Widget -->
      <div class="sidebar-health-widget">
        <div class="sidebar-health-label"><i class="bi bi-activity" style="color:var(--green);margin-right:5px;"></i>Suivi de santé</div>
        <div class="sidebar-health-bar-wrap"><div class="sidebar-health-bar"></div></div>
        <div class="sidebar-health-stats">
          <span class="sidebar-health-stat">Profil <strong>78%</strong> complet</span>
          <span class="sidebar-health-stat" style="color:var(--green);"><i class="bi bi-shield-check"></i> Actif</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>

        <a href="../home/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-house-door-fill"></i></span>
          Accueil
        </a>
        <a href="../auth/profile.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-person-fill"></i></span>
          Mon Profil
        </a>

        <div class="sidebar-nav-section-label">Mes Services</div>

        <a href="index.php" class="sidebar-nav-item active">
          <span class="nav-icon"><i class="bi bi-calendar-check"></i></span>
          Mes Rendez-vous
        </a>
        <a href="../ficherdv/index.php" class="sidebar-nav-item">
          <span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span>
          Mes Fiches Médicales
        </a>
      </nav>

      <!-- Footer Sidebar -->
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout"
           onclick="confirmSwal(event, this, 'Êtes-vous sûr de vouloir vous déconnecter ?')">
          <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>
          Déconnexion
        </a>
        <div style="margin-top:10px;">
          <a href="../home/index.php" class="sidebar-footer-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Retour au site
          </a>
        </div>
      </div>

    </aside>

    <main class="dashboard-main">
        <div id="capture-area">
            <div class="print-header">
                <div class="print-logo"><i class="bi bi-plus-square-fill"></i></div>
                <div class="print-title">Med<span>Chain</span></div>
                <div style="margin-left: auto; text-align: right; font-size: 12px; color: var(--gray-500);">
                    Document généré par MedChain<br>
                    <?= date('d/m/Y H:i') ?>
                </div>
            </div>

        <h1 class="page-title">Mes Rendez-vous</h1>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Rendez-vous</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #0284C7;">
                <h3><?= $stats['ce_mois'] ?></h3>
                <p>Ce mois-ci</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #16A34A;">
                <?php 
                $term = 0;
                foreach($stats['by_status'] as $s) if($s['statut'] == 'termine') $term = $s['count'];
                ?>
                <h3><?= $term ?></h3>
                <p>Terminés</p>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <form method="GET" class="search-box">
            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable()">
            <button type="submit" class="btn btn-mc"><i class="bi bi-search"></i></button>
        </form>
        <select class="sort-select" onchange="handleSortChange(this)" style="margin-left: 10px;">
            <option value="">Tri par...</option>
            <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>Date & Heure</option>
            <option value="medecin" <?= $sort == 'medecin' ? 'selected' : '' ?>>Médecin</option>
            <option value="type" <?= $sort == 'type' ? 'selected' : '' ?>>Type</option>
            <option value="statut" <?= $sort == 'statut' ? 'selected' : '' ?>>Statut</option>
        </select>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="stats.php" class="btn btn-stats"><i class="bi bi-bar-chart-line-fill"></i> Statistiques</a>
            <button onclick="generatePDF()" class="btn btn-secondary"><i class="bi bi-download"></i> Télécharger PDF</button>
            <a href="create.php" class="btn btn-mc"><i class="bi bi-plus-lg"></i> Nouveau RDV</a>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0" id="rdvTable">
            <thead>
                <tr>
                    <th style="cursor:pointer" onclick="sortTable('date')">Date & Heure <i class="bi bi-arrow-<?= $sort == 'date' ? ($order == 'asc' ? 'up' : 'down') : 'down-up' ?>" style="font-size:10px;"></i></th>
                    <th style="cursor:pointer" onclick="sortTable('medecin')">Médecin <i class="bi bi-arrow-<?= $sort == 'medecin' ? ($order == 'asc' ? 'up' : 'down') : 'down-up' ?>" style="font-size:10px;"></i></th>
                    <th style="cursor:pointer" onclick="sortTable('type')">Type <i class="bi bi-arrow-<?= $sort == 'type' ? ($order == 'asc' ? 'up' : 'down') : 'down-up' ?>" style="font-size:10px;"></i></th>
                    <th>Motif</th>
                    <th style="cursor:pointer" onclick="sortTable('statut')">Statut <i class="bi bi-arrow-<?= $sort == 'statut' ? ($order == 'asc' ? 'up' : 'down') : 'down-up' ?>" style="font-size:10px;"></i></th>
                    <th class="actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($rendezvous)): ?>
                <tr><td colspan="6" class="text-center py-4">Aucun rendez-vous trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach($paginated_rdv as $rdv): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($rdv['dateHeureDebut'])) ?></td>
                        <td>Dr. <?= htmlspecialchars($rdv['medecin_nom'] . ' ' . $rdv['medecin_prenom']) ?></td>
                        <td><?= htmlspecialchars($rdv['typeConsultation']) ?></td>
                        <td><?= htmlspecialchars(substr($rdv['motif'], 0, 30)) ?>...</td>
                        <td>
                            <span class="status-badge status-<?= $rdv['statut'] ?>">
                                <?= ucfirst($rdv['statut']) ?>
                            </span>
                        </td>
                        <td class="actions-col">
                            <a href="edit.php?id=<?= $rdv['idRDV'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="confirmSwal(event, this, 'Voulez-vous vraiment annuler ce rendez-vous ?')">
                                <input type="hidden" name="delete_id" value="<?= $rdv['idRDV'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Hidden table for printing all data -->
    <table id="rdvTablePrint">
        <thead>
            <tr>
                <th>Date / Heure</th>
                <th>Médecin / Spécialité</th>
                <th>Motif de consultation</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rendezvous as $rdv): ?>
            <tr>
                <td>
                    <span class="date-cell"><?= date('d/m/Y', strtotime($rdv['dateHeureDebut'])) ?></span>
                    <span class="time-cell"><?= date('H:i', strtotime($rdv['dateHeureDebut'])) ?></span>
                </td>
                <td>
                    <span class="doctor-cell">Dr. <?= htmlspecialchars($rdv['medecin_nom'] . ' ' . $rdv['medecin_prenom']) ?></span>
                    <span class="type-tag"><?= htmlspecialchars($rdv['typeConsultation']) ?></span>
                </td>
                <td><?= htmlspecialchars($rdv['motif']) ?></td>
                <td style="text-align: center;">
                    <span class="pdf-badge pdf-badge-<?= strtolower($rdv['statut']) ?>">
                        <?= ucfirst($rdv['statut']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </div> <!-- End capture-area -->

    <?php if ($total_pages >= 1): ?>
    <div class="pagination">
        <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
           class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>">
            <i class="bi bi-chevron-left"></i>
        </a>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
               class="page-link <?= $current_page == $i ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
           class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
</main>
</div>

<script>
// Recherche dynamique côté client en plus de la recherche serveur
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("rdvTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j] && !td[j].classList.contains('actions-col')) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }
}

    function handleSortChange(select) {
        if (select.value !== "") {
            window.location.href = "?page=1&search=<?= urlencode($search) ?>&sort=" + select.value + "&order=<?= $order ?>";
        }
    }

    function sortTable(column) {
        let currentSort = "<?= $sort ?>";
        let currentOrder = "<?= $order ?>";
        let newOrder = "asc";
        
        if (currentSort === column) {
            newOrder = currentOrder === "asc" ? "desc" : "asc";
        }
        
        window.location.href = "?page=1&search=<?= urlencode($search) ?>&sort=" + column + "&order=" + newOrder;
    }

async function generatePDF() {
    const element = document.getElementById('capture-area');
    const header = document.querySelector('.print-header');
    const fullTable = document.getElementById('rdvTablePrint');
    const paginatedTable = document.getElementById('rdvTable');
    const actionBar = document.querySelector('.action-bar');
    const statsRow = document.querySelector('.stats-row');
    const pagination = document.querySelector('.pagination');
    const pageTitle = document.querySelector('.page-title');

    // Preparation
    header.style.opacity = '1';
    header.style.height = 'auto';
    header.style.marginBottom = '40px';
    
    fullTable.style.display = 'table';
    fullTable.style.opacity = '1';
    fullTable.style.height = 'auto';
    fullTable.style.marginTop = '30px';

    if(paginatedTable) paginatedTable.style.display = 'none';
    if(actionBar) actionBar.style.display = 'none';
    if(pagination) pagination.style.display = 'none';
    
    const opt = {
        margin: 10,
        filename: 'Mes_Rendezvous_MedChain.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 1, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    setTimeout(() => {
        html2pdf().from(element).set(opt).save().then(() => {
            // Restore
            header.style.opacity = '0';
            header.style.height = '0';
            header.style.marginBottom = '0';
            
            fullTable.style.display = 'none';
            fullTable.style.opacity = '0';
            fullTable.style.height = '0';
            fullTable.style.marginTop = '0';

            if(paginatedTable) paginatedTable.style.display = 'table';
            if(actionBar) actionBar.style.display = 'flex';
            if(pagination) pagination.style.display = 'flex';
        });
    }, 100);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/projet/views/assets/js/swal-utils.js"></script>
</body>
</html>
