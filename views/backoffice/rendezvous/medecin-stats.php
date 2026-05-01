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
      --green: #1D9E75; --green-dark: #0F6E56; --green-pale: #F0FDF9;
      --navy: #1E3A52; --white: #ffffff; --gray-bg: #f8f9fa; --gray-200: #E5E7EB; --gray-500: #6B7280;
      --radius: 12px; --radius-lg: 20px; --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: var(--gray-bg); color: var(--navy); }
    
    .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%); border-right: 1px solid rgba(29,158,117,.15); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; box-shadow: 4px 0 24px rgba(29,158,117,.08); }
    .sidebar-logo-zone { padding: 26px 22px 20px; border-bottom: 1px solid rgba(29,158,117,.12); }
    .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .sidebar-logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 13px; display: flex; align-items: center; justify-content: center; color: white; }
    .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); }
    .sidebar-logo-text span { color: var(--green); }
    .sidebar-user-card { margin: 18px 16px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-lg); padding: 18px 16px; color: white; }
    .sidebar-nav { flex: 1; padding: 12px; display: flex; flex-direction: column; gap: 4px; }
    .sidebar-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: var(--gray-500); text-decoration: none; border-radius: 12px; transition: 0.3s; }
    .sidebar-nav-item:hover { background: rgba(29,158,117,.08); color: var(--green-dark); }
    .sidebar-nav-item.active { background: rgba(29,158,117,.12); color: var(--green-dark); font-weight: 600; }
    .sidebar-footer { padding: 16px; border-top: 1px solid rgba(29,158,117,.1); }

    .dashboard-main { padding: 32px 40px; }
    .stats-container { max-width: 1200px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 25px; border-bottom: 1px solid #ddd; }
    .header-title { display: flex; align-items: center; gap: 15px; }
    .header-title h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--green-dark); font-weight: 800; }
    .btn-retour { background: #888; color: white; text-decoration: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .chart-card { background: white; border-radius: var(--radius); padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }
    .chart-card h2 { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 30px; font-weight: 700; }
    canvas { max-width: 100%; height: auto !important; }
    @media (max-width: 992px) { .charts-grid { grid-template-columns: 1fr; } .dashboard-container { grid-template-columns: 1fr; } .dashboard-sidebar { display: none; } }
  </style>
</head>
<body>

<div class="dashboard-container">
  <aside class="dashboard-sidebar">
    <div class="sidebar-logo-zone">
      <a href="../../frontoffice/home/index.php" class="sidebar-logo-link">
        <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
        <div><div class="sidebar-logo-text">Med<span>Chain</span></div><div class="sidebar-tagline"><?= $role === 'admin' ? 'Espace Admin' : 'Espace Médecin' ?></div></div>
      </a>
    </div>
    <div class="sidebar-user-card">
      <div style="font-size: 24px; margin-bottom: 10px;"><i class="bi bi-person-badge-fill"></i></div>
      <div style="font-weight: 700;"><?= $role === 'medecin' ? 'Dr. ' : '' ?><?= htmlspecialchars($currentUser->getPrenom()) ?> <?= htmlspecialchars($currentUser->getNom()) ?></div>
      <div style="font-size: 12px; opacity: 0.8;"><?= ucfirst($role) ?></div>
    </div>
    <nav class="sidebar-nav">
      <div style="font-size: 11px; font-weight: 700; color: #A0AEC0; padding: 10px; text-transform: uppercase;">Navigation</div>
      <?php if ($role === 'admin'): ?>
        <a href="../admin-dashboard.php" class="sidebar-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="medecin-index.php" class="sidebar-nav-item active"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
        <a href="../ficherdv/medecin-index.php" class="sidebar-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      <?php else: ?>
        <a href="../../frontoffice/home/index.php" class="sidebar-nav-item"><i class="bi bi-house-door-fill"></i> Accueil</a>
        <a href="medecin-index.php" class="sidebar-nav-item active"><i class="bi bi-calendar-check"></i> Rendez-vous</a>
        <a href="../ficherdv/medecin-index.php" class="sidebar-nav-item"><i class="bi bi-file-earmark-medical"></i> Fiches Médicales</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="../../../controllers/logout.php" class="sidebar-nav-item logout"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
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
