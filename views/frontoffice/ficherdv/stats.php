<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../controllers/FicheRendezVousController.php';

$authController = new AuthController();
if (!$authController->isLoggedIn()) { header('Location: ../auth/login.php'); exit; }
$currentUser = $authController->getCurrentUser();
if ($currentUser->getRole() !== 'patient') { header('Location: index.php'); exit; }

$pdo = config::getConnexion();
$userId = $currentUser->getId();

// Stats by Mode Consultation
$req = $pdo->prepare("SELECT f.modeConsultation, COUNT(*) as count FROM ficherendezvous f JOIN rendezvous r ON f.idRDV = r.idRDV WHERE r.idClient = ? GROUP BY f.modeConsultation");
$req->execute([$userId]);
$byMode = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats by Status Paiement
$req = $pdo->prepare("SELECT f.statutPaiement, COUNT(*) as count FROM ficherendezvous f JOIN rendezvous r ON f.idRDV = r.idRDV WHERE r.idClient = ? GROUP BY f.statutPaiement");
$req->execute([$userId]);
$byPaiement = $req->fetchAll(PDO::FETCH_ASSOC);

$modeLabels = json_encode(array_map(fn($s) => strtoupper($s['modeConsultation'] ?: 'N/A'), $byMode));
$modeData   = json_encode(array_column($byMode, 'count'));
$paiLabels   = json_encode(array_map(fn($s) => strtoupper($s['statutPaiement'] ?: 'N/A'), $byPaiement));
$paiData     = json_encode(array_column($byPaiement, 'count'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiques Fiches Médicales — MedChain</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    :root {
      --green: #1D9E75; --green-dark: #0F6E56; --green-light: #E8F7F2; --green-pale: #F0FDF9;
      --navy: #1E3A52; --gray-700: #374151; --gray-500: #6B7280; --gray-200: #E5E7EB; --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.08); --shadow-md: 0 4px 16px rgba(0,0,0,.08);
      --shadow-green: 0 8px 30px rgba(29,158,117,.18);
      --radius-sm: 8px; --radius-md: 12px; --radius-lg: 20px;
    }
    body { font-family: 'DM Sans', sans-serif; background: #f0faf6; color: var(--gray-700); margin: 0; }
    .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(160deg, #ffffff 0%, #f0fdf9 60%, #e6faf3 100%); border-right: 1px solid rgba(29,158,117,.15); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 4px 0 24px rgba(29,158,117,.08); }
    .sidebar-logo-zone { padding: 26px 22px 20px; border-bottom: 1px solid rgba(29,158,117,.12); }
    .sidebar-logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .sidebar-logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(29,158,117,.35); }
    .sidebar-logo-icon i { font-size: 20px; color: white; }
    .sidebar-logo-text { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -.3px; }
    .sidebar-logo-text span { color: var(--green); }
    .sidebar-tagline { font-size: 11px; color: var(--gray-500); margin-top: 3px; letter-spacing: .03em; }
    .sidebar-user-card { margin: 18px 16px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-lg); padding: 18px 16px; box-shadow: var(--shadow-green); position: relative; overflow: hidden; }
    .sidebar-user-card::before { content: ''; position: absolute; top: -20px; right: -20px; width: 90px; height: 90px; border-radius: 50%; background: rgba(255,255,255,.1); }
    .sidebar-user-avatar { width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.25); border: 2.5px solid rgba(255,255,255,.5); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .sidebar-user-avatar i { font-size: 22px; color: white; }
    .sidebar-user-name { font-size: 15px; font-weight: 700; color: white; margin-bottom: 2px; }
    .sidebar-user-role { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: rgba(255,255,255,.85); background: rgba(255,255,255,.18); padding: 3px 10px; border-radius: 20px; margin-top: 4px; }
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px 12px 0; }
    .sidebar-nav-section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #A0AEC0; padding: 14px 12px 6px; }
    .sidebar-nav-item { display: flex; align-items: center; gap: 13px; padding: 11px 14px; color: var(--gray-500); text-decoration: none; border-radius: var(--radius-md); transition: all 0.25s; font-size: 14px; font-weight: 500; position: relative; }
    .sidebar-nav-item .nav-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; background: rgba(29,158,117,.08); color: var(--green); transition: all 0.25s; }
    .sidebar-nav-item:hover { background: rgba(29,158,117,.07); color: var(--green-dark); }
    .sidebar-nav-item.active { background: linear-gradient(90deg, rgba(29,158,117,.12), rgba(29,158,117,.04)); color: var(--green-dark); font-weight: 600; }
    .sidebar-nav-item.active .nav-icon { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; box-shadow: 0 4px 12px rgba(29,158,117,.30); }
    .sidebar-footer { padding: 16px; border-top: 1px solid rgba(29,158,117,.10); margin-top: auto; }
    .sidebar-footer-back { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: var(--radius-md); background: var(--green-pale); color: var(--green-dark); font-size: 13px; font-weight: 600; text-decoration: none; transition: all .2s; border: 1px solid rgba(29,158,117,.2); }
    
    .dashboard-main { padding: 32px 40px; }
    .stats-container { max-width: 1200px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 25px; border-bottom: 1px solid rgba(29,158,117,.15); }
    .header-title { display: flex; align-items: center; gap: 15px; }
    .header-title h1 { font-family: 'Syne', sans-serif; font-size: 28px; color: var(--green-dark); font-weight: 800; }
    .btn-retour { background: #888; color: white; text-decoration: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
    .btn-retour:hover { background: #666; transform: translateX(-2px); }
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .chart-card { background: white; border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-sm); text-align: center; border: 1px solid rgba(29,158,117,.1); }
    .chart-card h2 { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 30px; font-weight: 700; color: var(--navy); }
    @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 1fr; } .dashboard-sidebar { display: none; } }
    @media (max-width: 992px) { .charts-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="dashboard-sidebar">
      <div class="sidebar-logo-zone">
        <a href="../home/index.php" class="sidebar-logo-link">
          <div class="sidebar-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
          <div><div class="sidebar-logo-text">Med<span>Chain</span></div><div class="sidebar-tagline">Espace Patient</div></div>
        </a>
      </div>
      <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sidebar-user-name"><?= htmlspecialchars($currentUser->getPrenom()) ?> <?= htmlspecialchars($currentUser->getNom()) ?></div>
        <div class="sidebar-user-role"><i class="bi bi-heart-pulse-fill"></i> Patient</div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-nav-section-label">Navigation</div>
        <a href="../home/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-house-door-fill"></i></span> Accueil</a>
        <a href="../auth/profile.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-person-fill"></i></span> Mon Profil</a>
        <div class="sidebar-nav-section-label">Mes Services</div>
        <a href="../rendezvous/index.php" class="sidebar-nav-item"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span> Mes Rendez-vous</a>
        <a href="index.php" class="sidebar-nav-item active"><span class="nav-icon"><i class="bi bi-file-earmark-medical"></i></span> Mes Fiches Médicales</a>
      </nav>
      <div class="sidebar-footer">
        <a href="../../../controllers/logout.php" class="sidebar-nav-item logout"><span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Déconnexion</a>
        <div style="margin-top:10px;"><a href="../home/index.php" class="sidebar-footer-back"><i class="bi bi-arrow-left-circle-fill"></i> Retour au site</a></div>
      </div>
    </aside>

    <main class="dashboard-main">
      <div class="stats-container">
        <div class="header">
          <div class="header-title">
            <div style="font-size: 30px;"><i class="bi bi-file-earmark-bar-graph-fill" style="color: var(--green);"></i></div>
            <h1>Statistiques de mes Fiches Médicales</h1>
          </div>
          <a href="index.php" class="btn-retour">← Retour</a>
        </div>
        <div class="charts-grid">
          <div class="chart-card">
            <h2>Mode de Consultation</h2>
            <div style="position: relative; height: 350px;"><canvas id="modeChart"></canvas></div>
          </div>
          <div class="chart-card">
            <h2>Statut des Paiements</h2>
            <div style="position: relative; height: 350px;"><canvas id="paiementChart"></canvas></div>
          </div>
        </div>
      </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('modeChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $modeLabels ?>,
    datasets: [{
      data: <?= $modeData ?>,
      backgroundColor: ['#4BC0C0', '#36A2EB', '#FFCE56', '#FF6384'],
      borderWidth: 5, borderColor: '#ffffff'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'top' } } }
});
new Chart(document.getElementById('paiementChart'), {
  type: 'bar',
  data: {
    labels: <?= $paiLabels ?>,
    datasets: [{ label: 'Nombre de Fiches', data: <?= $paiData ?>, backgroundColor: '#7EC8E3', borderRadius: 5 }]
  },
  options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
</body>
</html>
