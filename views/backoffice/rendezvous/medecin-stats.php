<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/RendezVousController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

$authController = new AuthController();
$currentUser = $authController->getCurrentUser();
$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$rdvController = new RendezVousController();
$pdo = config::getConnexion();

// Adapt filters based on role
$cond = "1=1";
$params = [];
if ($role === 'patient') {
    $cond = "idClient = ?";
    $params[] = $userId;
} elseif ($role === 'medecin') {
    $cond = "idMedecin = ?";
    $params[] = $userId;
}

// Stats by Status
$req = $pdo->prepare("SELECT statut, COUNT(*) as count FROM rendezvous WHERE $cond GROUP BY statut");
$req->execute($params);
$byStatus = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats by Type
$req = $pdo->prepare("SELECT typeConsultation, COUNT(*) as count FROM rendezvous WHERE $cond GROUP BY typeConsultation");
$req->execute($params);
$byType = $req->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = json_encode(array_map(fn($s) => strtoupper($s['statut']), $byStatus));
$statusData   = json_encode(array_column($byStatus, 'count'));
$typeLabels   = json_encode(array_column($byType, 'typeConsultation'));
$typeData     = json_encode(array_column($byType, 'count'));

// Sidebar stats for Medecin
$sidebarStats = $rdvController->getStats($role, $userId);

// Determine back link
$backLink = ($role === 'patient') ? "../../frontoffice/rendezvous/index.php" : "medecin-index.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiques Rendez-Vous — MedChain</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
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
    .dashboard-nav-item i { font-size: 18px; width: 24px; text-align: center; }
    .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
    .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
    .dashboard-nav-item.logout { margin-top: auto; margin-bottom: 20px; color: #F87171; }
    .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
    .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }

    .dashboard-main { padding: 32px 40px; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--navy); font-weight: 700; }

    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px; }
    .chart-card { background: white; border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid rgba(29,158,117,.15); text-align: center; }
    .chart-card h2 { font-family: 'Syne', sans-serif; font-size: 20px; margin-bottom: 30px; color: var(--navy); }
    
    .btn-retour { padding: 10px 20px; border-radius: var(--radius-md); background: var(--white); border: 2px solid var(--gray-200); color: var(--navy); text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
    .btn-retour:hover { border-color: var(--green); color: var(--green); }

    @media (max-width: 768px) {
      .dashboard-container { grid-template-columns: 1fr; }
      .dashboard-sidebar { display: none; }
      .charts-grid { grid-template-columns: 1fr; }
    }
        <?php if ($role === 'admin'): ?>
        /* Admin Blue Theme Override */
        .dashboard-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            border-right: none !important;
        }
        .dashboard-logo-icon {
            background: rgba(255,255,255,0.1) !important;
        }
        .dashboard-logo-text span {
            color: #3b82f6 !important;
        }
        .dashboard-nav-item.active {
            background: rgba(59,130,246,0.2) !important;
            color: #3b82f6 !important;
        }
        .dashboard-nav-item:not(.active) {
            color: #94A3B8 !important;
        }
        .dashboard-nav-item:not(.active):hover {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
        }
        .sidebar-user-card {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: white !important;
        }
        .dashboard-nav-title {
            color: #64748B !important;
        }
        <?php endif; ?>
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
  <aside class="dashboard-sidebar">
    <div class="dashboard-logo" style="<?= $role === 'admin' ? 'border-bottom: 1px solid rgba(255,255,255,0.1);' : '' ?>">
      <a href="../../frontoffice/home/index.php">
        <div class="dashboard-logo-icon" style="<?= $role === 'admin' ? 'background: rgba(255,255,255,0.1);' : '' ?>"><i class="<?= $role === 'admin' ? 'fas fa-hospital-alt' : 'bi bi-plus-square-fill' ?>"></i></div>
        <div class="dashboard-logo-text" style="<?= $role === 'admin' ? 'color: white;' : '' ?>">Med<span style="<?= $role === 'admin' ? 'color: #3b82f6;' : '' ?>">Chain</span></div>
      </a>
    </div>
    <div class="sidebar-user-card">
      <div style="font-size: 24px; margin-bottom: 10px;"><i class="<?= $role === 'admin' ? 'fas fa-user-shield' : 'bi bi-person-badge-fill' ?>"></i></div>
      <div style="font-weight: 700;"><?= $role === 'medecin' ? 'Dr. ' : '' ?><?= htmlspecialchars($currentUser->getPrenom()) ?> <?= htmlspecialchars($currentUser->getNom()) ?></div>
      <div style="font-size: 12px; opacity: 0.8;"><?= $role === 'admin' ? 'Administration' : ucfirst($role) ?></div>
    </div>
    <nav class="dashboard-nav">
      <div class="dashboard-nav-title">Navigation</div>
      <?php if ($role === 'admin'): ?>
        <a href="../admin-dashboard.php" class="dashboard-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="medecin-index.php" class="dashboard-nav-item active"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="../ficherdv/medecin-index.php" class="dashboard-nav-item"><i class="fas fa-file-medical-alt"></i> Fiches Médicales</a>
        <a href="../admin-reports-statistics.php" class="dashboard-nav-item"><i class="fas fa-chart-pie"></i> Statistiques</a>
      <?php else: ?>
        <a href="../../frontoffice/home/index.php" class="dashboard-nav-item"><i class="fas fa-home"></i> Accueil</a>
        <a href="medecin-index.php" class="dashboard-nav-item active"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="../ficherdv/medecin-index.php" class="dashboard-nav-item"><i class="fas fa-file-medical-alt"></i> Fiches Médicales</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="../../../controllers/logout.php" class="dashboard-nav-item logout" onclick="confirmSwal(event, this, 'Déconnexion ?', 'Voulez-vous vraiment vous déconnecter ?')">
        <i class="fas fa-sign-out-alt"></i> Déconnexion
      </a>
    </div>
  </aside>

  <main class="dashboard-main">
    <div class="stats-container">
      <div class="header">
        <div class="header-title">
          <div style="font-size: 30px;"><i class="bi bi-bar-chart-fill" style="color: var(--green);"></i></div>
          <h1>Statistiques des Rendez-Vous</h1>
        </div>
        <a href="<?= $backLink ?>" class="btn-retour">← Retour</a>
      </div>

      <div class="charts-grid">
        <div class="chart-card">
          <h2>Statut des RDV</h2>
          <div style="position: relative; height: 350px;"><canvas id="statusChart"></canvas></div>
        </div>
        <div class="chart-card">
          <h2>Par Type de Consultation</h2>
          <div style="position: relative; height: 350px;"><canvas id="typeChart"></canvas></div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusLabels = <?= $statusLabels ?>;
const statusData = <?= $statusData ?>;
const typeLabels = <?= $typeLabels ?>;
const typeData = <?= $typeData ?>;

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: statusLabels,
    datasets: [{
      data: statusData,
      backgroundColor: ['#FF6384', '#CCCCCC', '#36A2EB', '#FFCE56', '#4BC0C0'],
      borderWidth: 5, borderColor: '#ffffff'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'top' } } }
});

new Chart(document.getElementById('typeChart'), {
  type: 'bar',
  data: {
    labels: typeLabels,
    datasets: [{ label: 'Nombre de RDV', data: typeData, backgroundColor: '#7EC8E3', borderRadius: 5 }]
  },
  options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>

</body>
</html>
